<?php

namespace Tests\Unit;

use App\Models\DebitOrder;
use App\Services\PaymentMandateQueryService;
use Illuminate\Database\Eloquent\Builder;
use Mockery;
use Tests\TestCase;

class PaymentMandateQueryServiceTest extends TestCase
{
    public function test_pending_filters_all_pending_lifecycle_statuses(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('whereIn')
            ->once()
            ->with(
                'debit_orders.lifecycle_status',
                DebitOrder::lifecycleStatusesForSummary(DebitOrder::SUMMARY_PENDING)
            )
            ->andReturnSelf();

        $this->service()->applyStatus($query, DebitOrder::SUMMARY_PENDING);
    }

    public function test_active_and_failed_filter_lifecycle_status_directly(): void
    {
        foreach ([DebitOrder::LIFECYCLE_ACTIVE, DebitOrder::LIFECYCLE_FAILED] as $status) {
            $query = Mockery::mock(Builder::class);
            $query->shouldReceive('where')
                ->once()
                ->with('debit_orders.lifecycle_status', $status)
                ->andReturnSelf();

            $this->service()->applyStatus($query, $status);
        }
    }

    public function test_approved_remains_a_legacy_alias_for_active(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')
            ->once()
            ->with('debit_orders.lifecycle_status', DebitOrder::LIFECYCLE_ACTIVE)
            ->andReturnSelf();

        $this->service()->applyStatus($query, DebitOrder::SUMMARY_APPROVED);
    }

    private function service(): PaymentMandateQueryService
    {
        return new class extends PaymentMandateQueryService
        {
            public function applyStatus(Builder $query, string $status): void
            {
                $this->applyLifecycleStatusFilter($query, $status);
            }
        };
    }
}
