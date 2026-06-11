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
use Throwable;

class ApiCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    public function retryUntil()
    {
        return now()->addDay();
    }

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
        if (!isset($this->data['callback_log_id'])) {
            Log::warning('External callback skipped: callback_log_id is required.', [
                'payload_keys' => array_keys((array) $this->data),
            ]);
            return;
        }

        $log = ExternalCallbackLog::find($this->data['callback_log_id']);

        if (!$log) {
            return;
        }

        $payload = $log->payload;
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($log->partner?->callback_secret) {
            $headers['X-Signature'] = 'sha256=' . hash_hmac('sha256', $body, $log->partner->callback_secret);
            $headers['X-Signature-Algorithm'] = 'HMAC-SHA256';
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($log->callback_url);
        } catch (Throwable $exception) {
            $log->attempts = (int) $log->attempts + 1;
            $log->last_attempt_at = now();
            $log->status = 'FAILED';
            $log->last_error = $exception->getMessage();
            $log->save();

            throw $exception;
        }

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

        if ($this->shouldRetryResponse($response->status())) {
            throw new \RuntimeException("External callback failed with retryable status [{$response->status()}].");
        }
    }

    protected function shouldRetryResponse(int $statusCode): bool
    {
        return $statusCode === 408 || $statusCode === 429 || $statusCode >= 500;
    }
}
