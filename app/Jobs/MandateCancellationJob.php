<?php

namespace App\Jobs;

use App\Classes\Authentication;
use App\Classes\Checksum;
use App\Models\DebitOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class MandateCancellationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    private $auth;
    /**
     * Create a new job instance.
     *
     * @return void
     */
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
        //update the debit order
        $debitOrder = DebitOrder::where('reference', '=', $this->data['mandateReferenceNumber'])->first();
        if (!$debitOrder) {
            Log::warning('Debit order not found for mandateReferenceNumber', [
                'mandateReferenceNumber' => $this->data['mandateReferenceNumber'] ?? null
            ]);
            // Bail early; nothing to update
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
            "targetService" => "MandateCancellation",
            "status" => "Create",
            "data" => $this->data
        ];

        //Create Payload
        Log::debug('== MandateCancellation Request == ');
        Log::debug($payload);

        //API URL 
        $API_URL = config('services.nmb.api_url');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //decode the response 
        $response = json_decode($response, true);

        if (!$response) {
            Log::error('MandateCancellation failed: HTTP error or invalid JSON', [
                'json_error' => json_last_error_msg()
            ]);
            return;
        }

        //logging response
        Log::debug('== MandateCancellation Response  ==');
        Log::debug($response);

        // get variables from response
        $httpStatus = $response['status'];
        $responseCode = $response['data']['response'][0]['responseCode'];
        $responseDescription = $response['data']['response'][0]['responseDescription'];

        // Check if response is null or not
        // Separate status code
        if (strtolower($httpStatus) == "success") {
            //update debit order => approved
            if ($responseCode == 201) {
                $debitOrder->status = 405;
                $debitOrder->cancelled = 1; // Mark as cancelled
                $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_CANCELLED;
            }
        } else if (strtolower($httpStatus) == "fail") {
            //update debit order => rejected
            $debitOrder->status = 404;
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_FAILED;
        }

        $debitOrder->remarks = $responseDescription;
        $debitOrder->save();

        Log::debug('== NMB Mandate registered successfully ===');
    }
}
