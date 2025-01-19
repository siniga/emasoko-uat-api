<?php

namespace App\Jobs;

use App\Classes\Authentication;
use App\Models\CollectionBatch;
use App\Models\Installment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PushCollectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $data;
    protected $installments;
    private $auth;


    public function __construct($data, $installments)
    {
        $this->data = $data;
        $this->installments = $installments;
        $this->auth = new Authentication();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "CollectionCreate",
            "status" => "Create",
            "data" => $this->data
        ];

        //Create Payload
        Log::debug('== CollectionCreate Request ==');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update collection batch 
        $collection_batch = CollectionBatch::where('collectionReference', '=', $payload['data']['collectionReference'])->first();
        $collection_batch->http_response_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== CollectionCreate Response ==');
        Log::debug($response);

        if ($response) {
            if (strtolower($response['status']) == "success") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update collection batch
                $collection_batch->status = $response['data']['response'][0]['responseCode'];
                $collection_batch->http_response_code = $response['data']['response'][0]['responseCode'];
                $collection_batch->remarks = $response['data']['response'][0]['responseDescription'];
                $collection_batch->pushed = 1;
                $collection_batch->save();

                //update debit order => approved
                if ($response_code == 200 || $response_code == 201) {
                    //update installments
                    foreach ($this->installments as $key => $v) {
                        $installment = Installment::findOrFail($v);
                        $installment->pushed = 1;
                        $installment->save();
                    }
                }
            } else if (strtolower($response['status']) == "fail") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update collection batch
                $collection_batch->status = 500;
                $collection_batch->http_response_code = $response['data']['response'][0]['responseCode'];
                $collection_batch->remarks = $response['data']['response'][0]['responseDescription'];
                $collection_batch->save();

                Log::debug('== Failed to create collection batch ==');
            }
        }
    }
}
