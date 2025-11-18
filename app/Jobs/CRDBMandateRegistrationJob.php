<?php

namespace App\Jobs;

use App\Classes\Checksum;
use App\Models\DebitOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CRDBMandateRegistrationJob implements ShouldQueue
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
        $this->auth = new Checksum();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //update the debit order
        $debit_order = DebitOrder::where('reference', '=', $this->data['mandateReferenceNumber'])->first();
        if (!$debit_order) {
            Log::warning('Debit order not found for mandateReferenceNumber', [
                'mandateReferenceNumber' => $this->data['mandateReferenceNumber'] ?? null
            ]);
            // Bail early; nothing to update
            return;
        }

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();
        Log::debug("== CRDB Auth Token ==");
        Log::debug($AUTH_BEARER_TOKEN);

        //headers
        $headers = [
            'Channel' => env('CRDB_CHANNEL_ID'),
            'AppId' => env('CRDB_APP_ID'),
            'AppVersion' => '1.0.0',
            "RequestId" => $this->data['requestId'],
            'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        //Payload
        Log::debug("== CRDB Payload ==");
        Log::debug($this->data['payload']);

        //API URL
        $API_URL = env('CRDB_API_URL');

        //push payload => NMB Direct Debits
        $httpResponse = Http::withHeaders($headers)->post($API_URL, $this->data['payload']);

        Log::debug('== Raw Response Body ==');
        Log::debug($httpResponse->body());

        //http response
        $raw = $httpResponse->body();

        // strip UTF-8 BOM + trim
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', trim($raw ?? ''));

        // remove trailing commas before } or ] (JSON5-style but not valid JSON)
        $sanitized = preg_replace('/,\s*(\}|\])/m', '$1', $raw);

        // now decode with error reporting
        $response = json_decode($sanitized, true);

        if (!is_array($response)) {
            $debit_order->status = 403;
            $debit_order->http_response_code = $httpResponse->status();
            $debit_order->remarks = 'HTTP error or invalid JSON from gateway';
            $debit_order->save();

            Log::error('MandateRegistration failed: HTTP error or invalid JSON', [
                'json_error' => json_last_error_msg(),
                'sample'     => Str::limit($raw, 800),
            ]);
            return;
        }

        // safely read values
        $statusCode = data_get($response, 'ResponseHeader.StatusCode');
        $statusDesc = data_get($response, 'ResponseHeader.StatusDesc');
        $responseId = data_get($response, 'ResponseBody.RequestId');
        $reference  = data_get($response, 'ResponseBody.Reference');
        Log::debug('Parsed header', compact('statusCode', 'statusDesc', 'reference'));

        // Handle business logic only if StatusCode is present
        if ($statusCode === 1000) {
            $debit_order->status = 201;  // success
            if ($reference) {
                $debit_order->crdb_request_id = $responseId;
                $debit_order->crdb_reference = $reference;
            }
        } elseif ($statusCode === 1001) {
            $debit_order->status = 403;  // failure
        } else {
            // Unknown/other code: record it for investigation
            Log::warning('MandateRegistration returned unexpected StatusCode', [
                'StatusCode' => $statusCode,
                'response'    => $response,
            ]);
        }

        // Persist shared fields (guard nulls)
        $debit_order->http_response_code = $statusCode ?? $httpResponse->status();
        $debit_order->remarks = $statusDesc ?: 'No StatusDesc in response';
        $debit_order->save();

        Log::debug('== CRDB Mandate registered successfully ===');
    }
}
