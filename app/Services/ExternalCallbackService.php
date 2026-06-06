<?php

namespace App\Services;

use App\Jobs\ApiCallbackJob;
use App\Http\Resources\MandateResource;
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

        $mandate = (new MandateResource($debitOrder))->resolve();

        $payload = [
            'event_id' => (string) Str::uuid(),
            'event_type' => $eventType,
            'entity_type' => 'MANDATE',
            'event_time' => now()->toIso8601String(),
            'provider_reference' => $debitOrder->partner?->reference,
            'channel' => $debitOrder->channel,
            'mandate' => $mandate,
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
