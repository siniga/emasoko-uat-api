<?php

namespace Tests\Unit;

use App\Models\DebitOrder;
use App\Services\DashboardReportingQueryService;
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

    public function test_active_filters_the_lifecycle_status_directly(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')
            ->once()
            ->with('debit_orders.lifecycle_status', DebitOrder::LIFECYCLE_ACTIVE)
            ->andReturnSelf();

        $this->service()->applyStatus($query, DebitOrder::LIFECYCLE_ACTIVE);
    }

    public function test_failed_and_approved_filters_use_their_summary_lifecycle_groups(): void
    {
        foreach ([DebitOrder::SUMMARY_FAILED, DebitOrder::SUMMARY_APPROVED] as $status) {
            $query = Mockery::mock(Builder::class);
            $query->shouldReceive('whereIn')
                ->once()
                ->with(
                    'debit_orders.lifecycle_status',
                    DebitOrder::lifecycleStatusesForSummary($status)
                )
                ->andReturnSelf();

            $this->service()->applyStatus($query, $status);
        }
    }

    private function service(): PaymentMandateQueryService
    {
        return new class(app(DashboardReportingQueryService::class)) extends PaymentMandateQueryService
        {
            public function applyStatus(Builder $query, string $status): void
            {
                $this->applyLifecycleStatusFilter($query, $status);
            }
        };
    }
}
