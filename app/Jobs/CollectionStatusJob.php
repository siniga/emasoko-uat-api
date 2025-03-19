<?php

namespace App\Jobs;

use App\Classes\Authentication;
use App\Models\CollectionBatch;
use App\Services\DeductionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CollectionStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $data;
    private $auth;

    public function __construct($data)
    {
        $this->data = $data;
        $this->auth = new Authentication();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //initialize deduction service
        $deduction_service = new DeductionService();

        Log::info('Current directory: ' . getcwd());
        Log::info(app()->environment());

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "CollectionStatus",
            "status" => "Check",
            "data" => $this->data
        ];

        //Create Payload
        Log::debug('== CollectionStatus Request ==');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //collection reference
        $collection_reference = $payload['data']['collectionReference'];

        //update collection batch 
        $collection_batch = CollectionBatch::where('collectionReference', '=', $collection_reference)->first();

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== CollectionStatus Response ==');
        Log::debug($response);

        if ($response) {
            if (strtolower($response['status']) == "success") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update batch status
                $collection_batch->status = $response_code;
                $collection_batch->http_response_code = $response_code;
                $collection_batch->remarks = $remarks;
                $collection_batch->processed = 1;

                //check for batch processing or batch complete
                if ($response_code == 230) {
                    //1. Batch Complete
                    //1.1: read csv file
                    $data = base64_to_content($response['data']['file']['fileData']);
                    Log::debug("== Complete Collection CSV ==");
                    Log::debug($data);

                    //1.2: response
                    $response = $deduction_service->updateInstallments($data, $collection_reference);

                    //1.3: process batch
                    $collection_batch->number_of_success += $response[0];
                    $collection_batch->number_of_failed += $response[1];
                    $collection_batch->save();
                } else if ($response_code == 235) {
                    //1. Batch Complete
                    //1.1: read csv file
                    $data = base64_to_content($response['data']['file']['fileData']);
                    Log::debug("== Failed Collection CSV ==");
                    Log::debug($data);

                    $response = $deduction_service->updateFailedInstallments($data);

                    //send SMS when batch failed
                    $message = "Collection batch number " . $collection_reference . " failed from NMB end. Review and resend the batch.";
                    $arr_phone = [255717705746, 255656314330];

                    foreach ($arr_phone as $phone) {
                        //send sms
                        $data = ["phone" => $phone, "message" => $message];
                        dispatch(new SendSMSJob($data));
                    }

                    //1.3: process batch
                    $collection_batch->number_of_failed += $response[0];
                    $collection_batch->failed = 1;
                    $collection_batch->save();
                } else if ($response_code == 239) {
                    //3.Batch Processing
                    //1.1: read csv file
                    $data = base64_to_content($response['data']['file']['fileData']);
                    Log::debug("== Processing Collection CSV ==");
                    Log::debug($data);

                    //1.2: response
                    $response = $deduction_service->updateInstallments($data, $collection_reference);

                    //1.3: process batch
                    $collection_batch->number_of_success += $response[0];
                    $collection_batch->number_of_failed += $response[1];
                    $collection_batch->save();
                }
            } else if (strtolower($response['status']) == "fail") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update collection status
                $collection_batch->status = 501;
                $collection_batch->http_response_code = $response_code;
                $collection_batch->remarks = $remarks;

                if ($response_code == 235 || $response_code == 404)
                    $collection_batch->processed = 1; //batch processed and failed

                //save batch    
                $collection_batch->save();

                //send SMS when batch failed
                $message = "Collection batch number " . $collection_reference . " failed from NMB end. Review and resend the batch.";
                $arr_phone = [255717705746, 255656314330, 255629602290, 255684512512];

                foreach ($arr_phone as $phone) {
                    //send sms
                    $data = ["phone" => $phone, "message" => $message];
                    dispatch(new SendSMSJob($data));
                }
            }
        }
    }
}
