<?php

namespace App\Console\Commands;

use App\Jobs\SendSMSJob;
use App\Models\Installment;
use App\Models\SmsLog;
use App\Services\MessagingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ResendMonthlyCollectionSms extends Command
{
    protected $signature = 'collections:resend-corrected-sms
                            {--month= : Payment month in YYYY-MM format (defaults to the current month)}
                            {--send : Create SMS logs and dispatch messages}';

    protected $description = 'Resend corrected balance SMS messages for customers collected through batches';

    public function __construct(protected MessagingService $messagingService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $month = $this->resolveMonth();

        if (!$month) {
            return self::FAILURE;
        }

        $send = (bool) $this->option('send');
        $installments = $this->installmentsForMonth($month)->get();
        $groups = $installments->groupBy('debit_order_id');

        $prepared = 0;
        $dispatched = 0;
        $duplicates = 0;
        $missingPhone = 0;

        foreach ($groups as $debitOrderId => $paidInstallments) {
            $debitOrder = $paidInstallments->first()?->debitOrder;
            $phone = $debitOrder?->customerAccount?->phone;

            if (!$debitOrder || !$phone) {
                $missingPhone++;
                $this->components->warn("Skipping debit order {$debitOrderId}: customer phone is missing.");

                continue;
            }

            $prepared++;
            $messageId = $this->messageId($month, (int) $debitOrderId);
            $normalizedPhone = $this->messagingService->castPhone(trim($phone));

            if (SmsLog::where('message_id', $messageId)->where('phone', $normalizedPhone)->exists()) {
                $duplicates++;
                continue;
            }

            if (!$send) {
                continue;
            }

            $paidAmount = (float) $paidInstallments->sum(function (Installment $installment) {
                return (float) ($installment->last_paid_amount ?: $installment->current_balance);
            });
            $outstandingAmount = (float) $debitOrder->fresh()->outstanding_amount;
            $businessName = $debitOrder->partner?->business_name ?? 'mtoa huduma';
            $monthLabel = $month->locale('sw')->translatedFormat('F Y');
            $message = "Makato yako ya {$monthLabel} yenye jumla ya Tsh "
                . number_format($paidAmount)
                . " yamekamilishwa kwa {$businessName}. Salio ni Tsh "
                . number_format($outstandingAmount)
                . '.';

            $smsLog = new SmsLog();
            $smsLog->uuid = (string) Str::uuid();
            $smsLog->message_id = $messageId;
            $smsLog->debit_order_id = $debitOrder->id;
            $smsLog->phone = $normalizedPhone;
            $smsLog->message = $message;
            $smsLog->number_of_sms = $this->messagingService->countNumberOfSms($message);
            $smsLog->sender = 'EMAKATO';
            $smsLog->status = 'PENDING';
            $smsLog->created_by = 1;
            $smsLog->save();

            SendSMSJob::dispatch(['messageId' => $messageId])->afterCommit();
            $dispatched++;
        }

        $this->table(
            ['Result', 'Count'],
            [
                ['Paid installments found', $installments->count()],
                ['Customer mandates prepared', $prepared],
                ['SMS messages dispatched', $dispatched],
                ['Already processed', $duplicates],
                ['Missing customer phone', $missingPhone],
            ]
        );

        if (!$send) {
            $this->components->warn(
                "Dry run only. Run with --month={$month->format('Y-m')} --send after reviewing the totals."
            );
        }

        return self::SUCCESS;
    }

    private function installmentsForMonth(Carbon $month): Builder
    {
        return Installment::query()
            ->with([
                'debitOrder:id,partner_id,customer_account_id,outstanding_amount',
                'debitOrder.customerAccount:id,phone',
                'debitOrder.partner:id,business_name',
            ])
            ->whereIn('status', ['CLOSED', 'PARTIAL_PAID'])
            ->where('current_balance', '>', 0)
            ->whereNotNull('effective_payment_date')
            ->whereYear('effective_payment_date', $month->year)
            ->whereMonth('effective_payment_date', $month->month)
            ->orderBy('debit_order_id')
            ->orderBy('effective_payment_date')
            ->orderBy('id');
    }

    private function resolveMonth(): ?Carbon
    {
        $value = $this->option('month');

        if (!$value) {
            return now()->startOfMonth();
        }

        try {
            $month = Carbon::createFromFormat('!Y-m', (string) $value);
        } catch (\Throwable) {
            $month = null;
        }

        if (!$month || $month->format('Y-m') !== $value) {
            $this->components->error('The --month option must use the YYYY-MM format.');

            return null;
        }

        return $month->startOfMonth();
    }

    private function messageId(Carbon $month, int $debitOrderId): string
    {
        return strtoupper(substr(hash('sha256', "corrected-collection|{$month->format('Y-m')}|{$debitOrderId}"), 0, 11));
    }
}
