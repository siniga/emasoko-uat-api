<?php

namespace Tests\Unit;

use App\Models\DebitOrder;
use App\Services\PaymentMandateQueryService;
use Illuminate\Database\Eloquent\Builder;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentMandateQueryServiceTest extends TestCase
{
    #[DataProvider('summaryStatuses')]
    public function test_summary_statuses_filter_the_legacy_approved_column(string $status, int $approved): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')
            ->once()
            ->with('debit_orders.approved', $approved)
            ->andReturnSelf();

        $service = new class extends PaymentMandateQueryService
        {
            public function applyStatus(Builder $query, string $status): void
            {
                $this->applyLifecycleStatusFilter($query, $status);
            }
        };

        $service->applyStatus($query, $status);
    }

    public static function summaryStatuses(): array
    {
        return [
            'pending' => [DebitOrder::SUMMARY_PENDING, 0],
            'approved' => [DebitOrder::SUMMARY_APPROVED, 1],
            'failed' => [DebitOrder::SUMMARY_FAILED, 2],
        ];
    }
}
