<?php

namespace App\Services;

use App\Jobs\SendSMSJob;
use App\Models\CollectionBatch;
use App\Models\CollectionDebitOrder;
use App\Models\DebitOrder;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomerSmsNotificationService
{
    public function __construct(
        protected MessagingService $messagingService,
        protected SmsTemplateService $smsTemplateService
    ) {
    }

    public function queueMandateCreated(DebitOrder $debitOrder): ?SmsLog
    {
        return $this->queueMandateEvent(
            $debitOrder,
            'mandate_created',
            'MANDATE',
            'Habari, huduma ya makato kwenda {service_provider} imeanzishwa kikamilifu kwenye akaunti yako inayoishia {account_last_digits}. Asante kwa kutumia eMakato.'
        );
    }

    public function queueMandateCancellation(DebitOrder $debitOrder): ?SmsLog
    {
        return $this->queueMandateEvent(
            $debitOrder,
            'mandate_cancelled',
            'CANCEL',
            'Habari, makato yenye namba ya kumbukumbu {mandate_reference} kwenda kwa {service_provider} yamefungwa kikamilifu. Asante kwa kutumia eMakato.'
        );
    }

    public function queueMandateClosed(DebitOrder $debitOrder): ?SmsLog
    {
        return $this->queueMandateEvent(
            $debitOrder,
            'mandate_closed',
            'CLOSED',
            'Habari, makato yenye namba ya kumbukumbu {mandate_reference} kwenda kwa {service_provider} yamefungwa kikamilifu. Asante kwa kutumia eMakato.'
        );
    }


    public function queueCollectionBatchUploaded(CollectionBatch $batch): int
    {
        $batch->loadMissing(['debits.debitOrder.customerAccount', 'debits.debitOrder.partner', 'debits.installment']);
        $queued = 0;

        foreach ($batch->debits as $line) {
            if ($this->queueCollectionLineUploaded($batch, $line)) {
                $queued++;
            }
        }

        return $queued;
    }

    protected function queueCollectionLineUploaded(CollectionBatch $batch, CollectionDebitOrder $line): ?SmsLog
    {
        $debitOrder = $line->debitOrder;
        $installment = $line->installment;

        if (!$debitOrder || !$installment) {
            Log::info('Collection batch uploaded SMS skipped: missing debit order or installment.', [
                'collection_batch_id' => $batch->id,
                'collection_reference' => $batch->collectionReference,
                'collection_debit_order_id' => $line->id,
            ]);

            return null;
        }

        $debitOrder->loadMissing(['customerAccount', 'partner']);
        $phone = $debitOrder->customerAccount?->phone;

        if (!$phone) {
            Log::info('Collection batch uploaded SMS skipped: customer phone missing.', [
                'collection_batch_id' => $batch->id,
                'collection_reference' => $batch->collectionReference,
                'debit_order_id' => $debitOrder->id,
                'mandate_reference' => $debitOrder->reference,
            ]);

            return null;
        }

        $businessName = $debitOrder->partner?->business_name ?? 'mtoa huduma';
        $accountLastDigits = $this->smsTemplateService->accountLastDigits($debitOrder->customerAccount?->account_number);
        $amount = number_format((float) ($installment->installment_amount ?? $installment->outstanding_amount ?? 0));
        $paymentDate = $line->payment_at ?: $installment->effective_payment_date ?: $installment->payment_date;
        $fallback = 'Habari, ombi la makato la TSh {amount} la tarehe {payment_date} kutoka kwenye akaunti yako inayoishia {account_last_digits} kwenda {service_provider} limewasilishwa Benki.';
        $message = $this->smsTemplateService->render('collection_batch_uploaded', $debitOrder->channel ?: 'NMB', [
            'amount' => $amount,
            'payment_date' => $paymentDate,
            'service_provider' => $businessName,
            'account_last_digits' => $accountLastDigits,
            'mandate_reference' => $debitOrder->reference,
            'collection_reference' => $batch->collectionReference,
        ], $fallback);

        if ($message === '') {
            return null;
        }

        $messageId = $this->eventMessageId('BATCH', $batch->id . '-' . $line->id);
        $phone = $this->messagingService->castPhone(trim($phone));
        $existing = SmsLog::where('message_id', $messageId)->where('phone', $phone)->first();

        if ($existing) {
            Log::info('Collection batch uploaded SMS already queued; skipping duplicate.', [
                'collection_batch_id' => $batch->id,
                'collection_reference' => $batch->collectionReference,
                'collection_debit_order_id' => $line->id,
                'message_id' => $messageId,
            ]);

            return $existing;
        }

        $smsLog = new SmsLog();
        $smsLog->uuid = (string) Str::uuid();
        $smsLog->message_id = $messageId;
        $smsLog->debit_order_id = $debitOrder->id;
        $smsLog->phone = $phone;
        $smsLog->message = $message;
        $smsLog->number_of_sms = $this->messagingService->countNumberOfSms($message);
        $smsLog->sender = 'Emakato';
        $smsLog->status = 'PENDING';
        $smsLog->created_by = $batch->createdBy ?? $debitOrder->created_by;
        $smsLog->save();

        SendSMSJob::dispatch(['messageId' => $messageId])->afterCommit();

        return $smsLog;
    }

    protected function queueMandateEvent(DebitOrder $debitOrder, string $templateCode, string $messagePrefix, string $fallback): ?SmsLog
    {
        $debitOrder->loadMissing(['customerAccount', 'partner']);

        $phone = $debitOrder->customerAccount?->phone;

        if (!$phone) {
            Log::info('Customer SMS skipped: customer phone missing.', [
                'debit_order_id' => $debitOrder->id,
                'mandate_reference' => $debitOrder->reference,
                'template_code' => $templateCode,
            ]);

            return null;
        }

        $businessName = $debitOrder->partner?->business_name ?? 'mtoa huduma';
        $accountLastDigits = $this->smsTemplateService->accountLastDigits($debitOrder->customerAccount?->account_number);
        $message = $this->smsTemplateService->render($templateCode, $debitOrder->channel, [
            'service_provider' => $businessName,
            'account_last_digits' => $accountLastDigits,
            'mandate_reference' => $debitOrder->reference,
        ], $fallback);

        if ($message === '') {
            return null;
        }

        $messageId = $this->eventMessageId($messagePrefix, $debitOrder->id);
        $phone = $this->messagingService->castPhone(trim($phone));
        $existing = SmsLog::where('message_id', $messageId)->where('phone', $phone)->first();

        if ($existing) {
            Log::info('Customer SMS already queued; skipping duplicate.', [
                'debit_order_id' => $debitOrder->id,
                'mandate_reference' => $debitOrder->reference,
                'message_id' => $messageId,
                'template_code' => $templateCode,
            ]);

            return $existing;
        }

        $smsLog = new SmsLog();
        $smsLog->uuid = (string) Str::uuid();
        $smsLog->message_id = $messageId;
        $smsLog->debit_order_id = $debitOrder->id;
        $smsLog->phone = $phone;
        $smsLog->message = $message;
        $smsLog->number_of_sms = $this->messagingService->countNumberOfSms($message);
        $smsLog->sender = 'Emakato';
        $smsLog->status = 'PENDING';
        $smsLog->created_by = $debitOrder->created_by;
        $smsLog->save();

        SendSMSJob::dispatch(['messageId' => $messageId])->afterCommit();

        return $smsLog;
    }

    protected function eventMessageId(string $prefix, mixed $id): string
    {
        return $prefix . '-' . substr(md5((string) $id), 0, 22);
    }
}
