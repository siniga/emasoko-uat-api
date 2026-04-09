<?php

namespace App\Services;

use App\Jobs\ApiCallbackJob;
use App\Models\DebitOrder;
use App\Models\ExternalCallbackLog;
use Illuminate\Support\Str;

class ExternalCallbackService
{
    public function dispatchMandateStatus(DebitOrder $debitOrder, string $eventType, array $extra = []): ?ExternalCallbackLog
    {
        $callbackUrl = $debitOrder->partner?->callback_url;

        if (!$callbackUrl) {
            return null;
        }

        $payload = [
            'event_id' => (string) Str::uuid(),
            'event_type' => $eventType,
            'entity_type' => 'MANDATE',
            'event_time' => now()->toIso8601String(),
            'provider_reference' => $debitOrder->partner?->reference,
            'channel' => $debitOrder->channel,
            'mandate' => [
                'reference' => $debitOrder->reference,
                'uuid' => $debitOrder->uuid,
                'external_reference' => $debitOrder->otp_reference,
                'lifecycle_status' => $debitOrder->resolved_lifecycle_status,
                'contract_status' => $debitOrder->contract_status,
                'approved' => (int) $debitOrder->approved,
                'bank_status_code' => $debitOrder->status,
                'http_response_code' => $debitOrder->http_response_code,
                'message' => $debitOrder->remarks,
                'total_amount' => $debitOrder->total_amount,
                'outstanding_amount' => $debitOrder->outstanding_amount,
                'next_debit_date' => $debitOrder->next_debit_date,
            ],
            'meta' => $extra,
        ];

        $log = ExternalCallbackLog::create([
            'event_id' => $payload['event_id'],
            'partner_id' => $debitOrder->partner_id,
            'debit_order_id' => $debitOrder->id,
            'callback_type' => 'MANDATE_STATUS',
            'event_type' => $eventType,
            'callback_url' => $callbackUrl,
            'payload' => $payload,
            'status' => 'PENDING',
            'attempts' => 0,
        ]);

        ApiCallbackJob::dispatch([
            'callback_log_id' => $log->id,
        ]);

        return $log;
    }
}
