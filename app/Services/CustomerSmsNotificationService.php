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
        $debitOrder->loadMissing(['customerAccount', 'partner']);

        $phone = $debitOrder->customerAccount?->phone;

        if (!$phone) {
            Log::info('Mandate created SMS skipped: customer phone missing.', [
                'debit_order_id' => $debitOrder->id,
                'mandate_reference' => $debitOrder->reference,
            ]);

            return null;
        }

        $businessName = $debitOrder->partner?->business_name ?? 'mtoa huduma';
        $accountLastDigits = $this->smsTemplateService->accountLastDigits($debitOrder->customerAccount?->account_number);
        $fallback = 'Habari, huduma ya makato kwenda {service_provider} imeanzishwa kikamilifu kwenye akaunti yako inayoishia {account_last_digits}. Asante kwa kutumia eMakato.';
        $message = $this->smsTemplateService->render('mandate_created', $debitOrder->channel, [
            'service_provider' => $businessName,
            'account_last_digits' => $accountLastDigits,
            'mandate_reference' => $debitOrder->reference,
        ], $fallback);

        if ($message === '') {
            return null;
        }

        $messageId = $this->eventMessageId('MANDATE', $debitOrder->id);
        $phone = $this->messagingService->castPhone(trim($phone));
        $alreadyQueued = SmsLog::where('message_id', $messageId)->where('phone', $phone)->exists();

        if ($alreadyQueued) {
            Log::info('Mandate created SMS already queued; skipping duplicate.', [
                'debit_order_id' => $debitOrder->id,
                'mandate_reference' => $debitOrder->reference,
                'message_id' => $messageId,
            ]);

            return SmsLog::where('message_id', $messageId)->where('phone', $phone)->first();
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
