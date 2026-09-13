<?php

namespace Tests\Unit;

use App\Classes\Authentication;
use App\Models\CollectionBatch;
use App\Models\CustomerAccount;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Services\BankPayloadService;
use App\Services\CollectionBatchPushService;
use App\Services\CollectionBatchService;
use App\Services\CustomerSmsNotificationService;
use App\Services\DeductionService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class CollectionBatchWorkflowTest extends TestCase
{
    public function test_bank_acceptance_requires_success_and_supported_response_code(): void
    {
        $service = new CollectionBatchPushService(
            Mockery::mock(Authentication::class),
            Mockery::mock(BankPayloadService::class),
            Mockery::mock(CustomerSmsNotificationService::class)
        );

        $this->assertTrue($service->isAcceptedResponse([
            'status' => 'success',
            'data' => ['response' => [['responseCode' => 201]]],
        ], 200));

        $this->assertFalse($service->isAcceptedResponse([
            'status' => 'success',
            'data' => ['response' => [['responseCode' => 500]]],
        ], 200));

        $this->assertFalse($service->isAcceptedResponse([
            'status' => 'fail',
            'data' => ['response' => [['responseCode' => 201]]],
        ], 200));
    }

    public function test_only_ready_batches_can_be_claimed_for_push(): void
    {
        $batch = new CollectionBatch(['request_csv' => 'csv']);

        $batch->lifecycle_status = CollectionBatch::LIFECYCLE_READY;
        $this->assertTrue($batch->canBePushed());

        $batch->lifecycle_status = CollectionBatch::LIFECYCLE_QUEUED_FOR_PUSH;
        $this->assertFalse($batch->canBePushed());

        $batch->lifecycle_status = CollectionBatch::LIFECYCLE_PUSHED;
        $this->assertFalse($batch->canBePushed());
    }

    public function test_effective_bank_partner_prefers_sponsor_but_duplicate_owner_remains_provider(): void
    {
        $service = $this->batchService();
        $mandate = new DebitOrder([
            'partner_id' => 10,
            'bank_sponsor_partner_id' => 20,
            'bank_partner_sponsor_id' => 30,
        ]);

        $this->assertSame(20, $service->effectivePartner($mandate));
        $this->assertSame(10, (int) $mandate->partner_id);
    }

public function test_batch_creation_date_must_be_inside_mandate_window_inclusively(): void
{
    $service = $this->batchService();
    $mandate = new DebitOrder([
        'start_date' => '2026-09-13',
        'end_date' => '2026-09-13',
    ]);

    $this->assertTrue($service->activeOn($mandate, Carbon::parse('2026-09-13')));
    $this->assertFalse($service->activeOn($mandate, Carbon::parse('2026-09-12')));
    $this->assertFalse($service->activeOn($mandate, Carbon::parse('2026-09-14')));
}

    public function test_repeated_accounts_are_partitioned_into_different_batches(): void
    {
        $service = $this->batchService();
        $first = $this->installmentWithAccount('001 234');
        $second = $this->installmentWithAccount('001234');
        $third = $this->installmentWithAccount('999');

        $buckets = $service->partition(collect([$first, $second, $third]), 250);

        $this->assertCount(2, $buckets);
        $this->assertCount(2, $buckets->first());
        $this->assertCount(1, $buckets->last());
    }

    private function batchService(): CollectionBatchService
    {
        return new class(Mockery::mock(DeductionService::class)) extends CollectionBatchService
        {
            public function effectivePartner(DebitOrder $debitOrder): int
            {
                return $this->effectiveBankPartnerId($debitOrder);
            }

            public function activeOn(DebitOrder $debitOrder, Carbon $date): bool
            {
                return $this->isMandateActiveOnDate($debitOrder, $date);
            }

            public function partition(Collection $installments, int $chunkSize): Collection
            {
                return $this->partitionByUniqueAccount($installments, $chunkSize);
            }
        };
    }

    private function installmentWithAccount(string $accountNumber): Installment
    {
        $account = new CustomerAccount(['account_number' => $accountNumber]);
        $mandate = new DebitOrder();
        $mandate->setRelation('customerAccount', $account);
        $installment = new Installment();
        $installment->setRelation('debitOrder', $mandate);

        return $installment;
    }
}
