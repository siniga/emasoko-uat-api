<?php

namespace App\Jobs;

use App\Models\DebitOrder;
use App\Services\BankApiService;
use App\Services\BankPayloadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CRDBMandateCancellationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(BankApiService $bankApi, BankPayloadService $payloadService): void
    {
        $mandateReferenceNumber = $this->data['mandateReferenceNumber'] ?? null;

        if (!$mandateReferenceNumber) {
            Log::warning('CRDB mandate cancellation job missing mandateReferenceNumber.', [
                'data' => $this->safeLogData(),
            ]);

            return;
        }

        $debitOrder = DebitOrder::where('reference', $mandateReferenceNumber)->first();

        if (!$debitOrder) {
            Log::warning('Debit order not found for CRDB mandate cancellation job.', [
                'mandateReferenceNumber' => $mandateReferenceNumber,
                'requestId' => $this->data['requestId'] ?? null,
            ]);

            return;
        }

        $requestId = $this->data['requestId'] ?? $debitOrder->request_id ?? Str::uuid()->toString();
        $payload = $this->data['payload'] ?? $payloadService->CRDBMandateCancellationPayload($debitOrder, $requestId);

        Log::info('Processing CRDB mandate cancellation job.', [
            'debit_order_id' => $debitOrder->id,
            'mandate_reference' => $mandateReferenceNumber,
            'request_id' => $requestId,
        ]);

        try {
            $response = $bankApi->crdbMandateCancellationRequest([
                'payload' => $payload,
                'requestId' => $requestId,
                'mandateReferenceNumber' => $mandateReferenceNumber,
            ]);
        } catch (\Throwable $exception) {
            $debitOrder->cancelled = 0;
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_FAILED;
            $debitOrder->remarks = 'CRDB mandate cancellation job failed: ' . $exception->getMessage();
            $debitOrder->save();

            Log::error('CRDB mandate cancellation job threw an exception.', [
                'debit_order_id' => $debitOrder->id,
                'mandate_reference' => $mandateReferenceNumber,
                'request_id' => $requestId,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        Log::info('CRDB mandate cancellation job completed.', [
            'debit_order_id' => $debitOrder->id,
            'mandate_reference' => $mandateReferenceNumber,
            'request_id' => $requestId,
            'response_code' => $response[0] ?? null,
            'remarks' => $response[1] ?? null,
            'provider_response_code' => $response[2] ?? null,
        ]);
    }

    protected function safeLogData(): array
    {
        return [
            'requestId' => $this->data['requestId'] ?? null,
            'mandateReferenceNumber' => $this->data['mandateReferenceNumber'] ?? null,
            'hasPayload' => array_key_exists('payload', $this->data),
        ];
    }
}
