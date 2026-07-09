<?php

namespace App\Services;

use App\Jobs\SendSMSJob;
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
