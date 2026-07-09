<?php

namespace App\Jobs;

use App\Classes\Authentication;
use App\Models\CollectionBatch;
use App\Models\CollectionDebitOrder;
use App\Services\CustomerSmsNotificationService;
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

class PushCollectionJob implements ShouldQueue, ShouldBeUnique
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
    public function handle(CustomerSmsNotificationService $customerSmsNotificationService)
    {
        //initialize deduction service
        $deduction_service = new DeductionService();
        $collection_batch = CollectionBatch::find($this->batchId);

        if (!$collection_batch || !$collection_batch->canBePushed()) {
            return;
        }

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        $correlationId = Str::uuid()->toString();

        //Create Payload
        $payloadData = [
            "collectionReference" => $collection_batch->collectionReference,
            "corporateReference" => $collection_batch->corporateReference,
            "selectedPostingDate" => \Carbon\Carbon::parse($collection_batch->selectedPostingDate)->format('Y-m-d H:i:s'),
            "createdBy" => \App\Services\BankPayloadService::userNameFormat($collection_batch->createdUser),
            "verifiedBy" => \App\Services\BankPayloadService::userNameFormat($collection_batch->createdUser),
            "createdDate" => \Carbon\Carbon::parse($collection_batch->createdDate)->format('Y-m-d H:i:s'),
            "verifyDate" => \Carbon\Carbon::parse($collection_batch->verifyDate)->format('Y-m-d H:i:s'),
            "file" => [
                "fileName" => "Payment_Collection_{$collection_batch->id}",
                "fileType" => "CSV",
                "fileData" => content_to_base64($collection_batch->request_csv),
            ],
        ];

        $payload = [
            "user" => "EMakato",
            "correlationId" => $correlationId,
            "targetService" => "CollectionCreate",
            "status" => "Create",
            "data" => $payloadData
        ];

        //Create Payload
        Log::debug('== CollectionCreate Request ==');
        Log::debug($payload);

        //API URL 
        $API_URL = config('services.nmb.api_url');

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
        Log::debug('== CollectionCreate Response ==');
        Log::debug($response);

        if ($response) {
            if (strtolower($response['status']) == "success") {
                $response_code = $response['data']['response'][0]['responseCode'];

                //update collection batch
                $collection_batch->status = $response['data']['response'][0]['responseCode'];
                $collection_batch->http_response_code = $response['data']['response'][0]['responseCode'];
                $collection_batch->remarks = $response['data']['response'][0]['responseDescription'];
                $collection_batch->pushed = 1;
                $collection_batch->last_pushed_at = now();
                $collection_batch->push_correlation_id = $correlationId;
                $collection_batch->lifecycle_status = CollectionBatch::LIFECYCLE_PUSHED;
                $collection_batch->save();

                //update debit order => approved
                if ($response_code == 200 || $response_code == 201) {
                    CollectionDebitOrder::where('collection_batch_id', $collection_batch->id)->update([
                        'status' => 'PUSHED',
                        'bank_status' => 'PUSHED',
                        'pushed_at' => now(),
                    ]);

                    $customerSmsNotificationService->queueCollectionBatchUploaded($collection_batch->fresh());
                }
            } else if (strtolower($response['status']) == "fail") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update collection batch
                $collection_batch->status = 500;
                $collection_batch->http_response_code = $response_code;
                $collection_batch->remarks = $remarks;
                $collection_batch->lifecycle_status = CollectionBatch::LIFECYCLE_READY;
                $collection_batch->save();

                Log::debug('== Failed to create collection batch ==');
            }
        }
    }
}
