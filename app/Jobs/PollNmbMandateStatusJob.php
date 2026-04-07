<?php

namespace App\Jobs;

use App\Models\DebitOrder;
use App\Services\BankApiService;
use App\Services\BankPayloadService;
use App\Services\DeductionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollNmbMandateStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $debitOrderId;
    protected int $pollNumber;
    protected int $maxPolls;

    public function __construct(int $debitOrderId, int $pollNumber = 1, int $maxPolls = 5)
    {
        $this->debitOrderId = $debitOrderId;
        $this->pollNumber = $pollNumber;
        $this->maxPolls = $maxPolls;
    }

    public function handle(BankPayloadService $payloadService, BankApiService $bankApi, DeductionService $deductionService): void
    {
        $debitOrder = DebitOrder::find($this->debitOrderId);

        if (!$debitOrder || $debitOrder->channel !== 'NMB') {
            return;
        }

        if (
            (int) $debitOrder->approved === 1 ||
            $debitOrder->resolved_lifecycle_status === DebitOrder::LIFECYCLE_ACTIVE ||
            in_array($debitOrder->resolved_lifecycle_status, [
                DebitOrder::LIFECYCLE_FAILED,
                DebitOrder::LIFECYCLE_CANCELLED,
                DebitOrder::LIFECYCLE_CLOSED,
                DebitOrder::LIFECYCLE_ARCHIVED,
            ], true)
        ) {
            return;
        }

        Log::debug('== NMB Mandate Status Auto Poll ==', [
            'reference' => $debitOrder->reference,
            'poll_number' => $this->pollNumber,
        ]);

        $payload = $payloadService->mandateStatusPayload($debitOrder);
        $arrResponse = $bankApi->mandateStatusRequest($payload);

        if ($arrResponse) {
            $responseCode = (int) $arrResponse[0];

            if ($responseCode === 240) {
                if ($debitOrder->installments()->count() === 0) {
                    $deductionService->createInstallment($debitOrder);
                }

                return;
            }

            if (in_array($responseCode, [201, 202, 200, 249], true) && $this->pollNumber < $this->maxPolls) {
                self::dispatch($this->debitOrderId, $this->pollNumber + 1, $this->maxPolls)
                    ->delay(now()->addSeconds($this->nextDelaySeconds()));
            }
        }
    }

    protected function nextDelaySeconds(): int
    {
        return match ($this->pollNumber) {
            1 => 30,
            2 => 60,
            3 => 120,
            default => 180,
        };
    }
}
