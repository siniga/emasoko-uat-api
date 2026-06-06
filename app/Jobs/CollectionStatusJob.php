<?php

namespace App\Jobs;

use App\Classes\Authentication;
use App\Events\CollectionStatus;
use App\Models\CollectionBatch;
use App\Models\CollectionDebitOrder;
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

class CollectionStatusJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $batchId;
    private $auth;

    public function __construct(string $batchId)
    {
        $this->batchId = $batchId;
        $this->auth = new Authentication();
    }

    public function uniqueId(): string
    {
        return $this->batchId;
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
        $collection_batch = CollectionBatch::find($this->batchId);

        if (!$collection_batch || !$collection_batch->canFetchStatus()) {
            return;
        }

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
            "data" => [
                "collectionReference" => $collection_batch->collectionReference,
            ]
        ];

        //Create Payload
        Log::debug('== CollectionStatus Request ==');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        // Log response metadata BEFORE decoding
        Log::debug('== CollectionStatus HTTP Response ==', [
            'status'  => $response->status(),
            'headers' => $response->headers(),
            'body'    => $response->body(),   // raw string
        ]);

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
                $collection_batch->last_status_checked_at = now();

                //check for batch processing or batch complete
                if ($response_code == 230) {
                    //1.0: Complete Success Batch 
                    $data = base64_to_content($response['data']['file']['fileData']);
                    Log::debug("== Complete Collection CSV ==");
                    Log::debug($data);

                    //1.1: response
                    $response = $deduction_service->updateInstallments($collection_batch, $data);

                    //1.3: process batch
                    $collection_batch->number_of_success = $response[0];
                    $collection_batch->number_of_failed = $response[1];
                    $collection_batch->lifecycle_status = CollectionBatch::LIFECYCLE_PROCESSED_SUCCESS;
                    $collection_batch->save();
                } else if ($response_code == 235) {
                    //2.0: Complete Failed Batch
                    $data = base64_to_content($response['data']['file']['fileData']);
                    Log::debug("== Failed Collection CSV ==");
                    Log::debug($data);

                    //2.1: response
                    $response = $deduction_service->updateFailedInstallments($collection_batch, $data);

                    //1.3: process batch
                    $collection_batch->number_of_failed = $response[0];
                    $collection_batch->failed = 1;
                    $collection_batch->lifecycle_status = CollectionBatch::LIFECYCLE_PROCESSED_FAILED;
                    $collection_batch->save();
                } else if ($response_code == 239) {
                    //3.0: Process Ongoing Batch
                    $data = base64_to_content($response['data']['file']['fileData']);
                    Log::debug("== Processing Collection CSV ==");
                    Log::debug($data);

                    //3.1: response
                    $response = $deduction_service->updateInstallments($collection_batch, $data);

                    //1.3: process batch
                    $collection_batch->number_of_success += $response[0];
                    $collection_batch->number_of_failed += $response[1];
                    $collection_batch->lifecycle_status = CollectionBatch::LIFECYCLE_PARTIALLY_PROCESSED;
                    $collection_batch->save();
                }
            } else if (strtolower($response['status']) == "fail") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update collection status
                $collection_batch->status = 501;
                $collection_batch->http_response_code = $response_code;
                $collection_batch->remarks = $remarks;
                $collection_batch->last_status_checked_at = now();

                if ($response_code == 235 || $response_code == 404)
                    $collection_batch->processed = 1; //batch processed and failed

                if ($response_code == 235) {
                    $collection_batch->lifecycle_status = CollectionBatch::LIFECYCLE_PROCESSED_FAILED;
                }

                //save batch    
                $collection_batch->save();

                //TODO: Messaging Module
            }
        }

        // Broadcast an event with the result
        event(new CollectionStatus($collection_batch));
    }
}
