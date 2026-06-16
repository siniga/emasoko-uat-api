<?php

namespace App\Services;

use App\Jobs\ApiCallbackJob;
use App\Http\Resources\InstallmentResource;
use App\Http\Resources\MandateResource;
use App\Models\DebitOrder;
use App\Models\ExternalCallbackLog;
use App\Models\Installment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExternalCallbackService
{
    public function dispatchMandateStatus(DebitOrder $debitOrder, string $eventType, array $extra = []): ?ExternalCallbackLog
    {
        $callbackUrl = $debitOrder->partner?->callback_url;

        if (!$callbackUrl) {
            Log::warning('External mandate callback skipped: partner callback_url is not configured.', [
                'debit_order_id' => $debitOrder->id,
                'partner_id' => $debitOrder->partner_id,
                'event_type' => $eventType,
            ]);

            return null;
        }

        $mandate = (new MandateResource($debitOrder))->resolve();

        $payload = [
            'event_id' => (string) Str::uuid(),
            'event_type' => $eventType,
            'callback_type' => 'MANDATE_STATUS',
            'event_time' => now()->toIso8601String(),
            'corporate_reference' => $debitOrder->partner?->reference,
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

        Log::info('External mandate callback queued.', [
            'callback_log_id' => $log->id,
            'debit_order_id' => $debitOrder->id,
            'partner_id' => $debitOrder->partner_id,
            'event_type' => $eventType,
        ]);

        return $log;
    }

    public function dispatchCollectionStatus(Installment $installment, string $eventType, array $extra = []): ?ExternalCallbackLog
    {
        $installment->loadMissing('debitOrder.partner');

        $debitOrder = $installment->debitOrder;
        $callbackUrl = $debitOrder?->partner?->callback_url;

        if (!$debitOrder) {
            Log::warning('External collection callback skipped: installment has no debit order.', [
                'installment_id' => $installment->id,
                'event_type' => $eventType,
            ]);

            return null;
        }

        if (!$callbackUrl) {
            Log::warning('External collection callback skipped: partner callback_url is not configured.', [
                'installment_id' => $installment->id,
                'debit_order_id' => $debitOrder->id,
                'partner_id' => $debitOrder->partner_id,
                'event_type' => $eventType,
            ]);

            return null;
        }

        $collection = (new InstallmentResource($installment))->resolve();
        $callbackChannel = $extra['channel'] ?? $debitOrder->channel;
        unset($extra['channel']);

        $payload = [
            'event_id' => (string) Str::uuid(),
            'event_type' => $eventType,
            'callback_type' => 'COLLECTION_STATUS',
            'event_time' => now()->toIso8601String(),
            'corporate_reference' => $debitOrder->partner?->reference,
            'channel' => $callbackChannel,
            'collection' => $collection,
        ];

        if (!empty($extra)) {
            $payload['meta'] = $extra;
        }

        $log = ExternalCallbackLog::create([
            'event_id' => $payload['event_id'],
            'partner_id' => $debitOrder->partner_id,
            'debit_order_id' => $debitOrder->id,
            'callback_type' => 'COLLECTION_STATUS',
            'event_type' => $eventType,
            'callback_url' => $callbackUrl,
            'payload' => $payload,
            'status' => 'PENDING',
            'attempts' => 0,
        ]);

        ApiCallbackJob::dispatch([
            'callback_log_id' => $log->id,
        ]);

        Log::info('External collection callback queued.', [
            'callback_log_id' => $log->id,
            'installment_id' => $installment->id,
            'debit_order_id' => $debitOrder->id,
            'partner_id' => $debitOrder->partner_id,
            'event_type' => $eventType,
        ]);

        return $log;
    }
}
