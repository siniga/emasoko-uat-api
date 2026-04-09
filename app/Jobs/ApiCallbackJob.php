<?php

namespace App\Jobs;

use App\Models\ExternalCallbackLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (isset($this->data['callback_log_id'])) {
            $log = ExternalCallbackLog::find($this->data['callback_log_id']);

            if (!$log) {
                return;
            }

            $headers = ['Content-Type' => 'application/json'];
            $payload = $log->payload;
            $response = Http::withHeaders($headers)->post($log->callback_url, $payload);
            $decodedResponse = json_decode($response->body(), true);

            $log->attempts = (int) $log->attempts + 1;
            $log->last_response_code = $response->status();
            $log->response_body = $decodedResponse;
            $log->last_attempt_at = now();
            $log->status = $response->successful() ? 'DELIVERED' : 'FAILED';
            $log->last_error = $response->successful() ? null : $response->body();

            if ($response->successful()) {
                $log->delivered_at = now();
            }

            $log->save();

            Log::debug('== External Callback Payload Request ==');
            Log::debug($payload);
            Log::debug('== External Callback Response ==');
            Log::debug($decodedResponse);

            return;
        }

        //Create Headers
        $headers = ['Content-Type' => 'application/json'];

        //Create Payload
        $payload["callback_type"] = $this->data['callback_type'];

        if ($this->data['callback_type'] == "MANDATE_STATUS")
            $payload['mandate'] = $this->data['mandate'];
        else if ($this->data['callback_type'] == "COLLECTION_STATUS")
            $payload['collection'] = $this->data['collection'];

        //Create Payload
        Log::debug('== Callback Payload Request ==');
        Log::debug($payload);

        //API URL 
        $API_URL = $this->data['callback_url'];

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== Callback Response ==');
        Log::debug($response);
    }
}
