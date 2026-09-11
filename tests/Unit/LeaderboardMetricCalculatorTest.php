<?php

namespace Tests\Unit;

use App\Services\LeaderboardMetricCalculator;
use PHPUnit\Framework\TestCase;

class LeaderboardMetricCalculatorTest extends TestCase
{
    public function test_it_builds_consistent_performance_metrics(): void
    {
        $calculator = new LeaderboardMetricCalculator();
        $row = $calculator->row(
            ['id' => 10, 'name' => 'Provider A'],
            ['mandates_created' => 20, 'mandates_approved' => 15],
            ['expected_amount' => 100000],
            ['actual_amount' => 72500, 'successful_collections' => 8]
        );

        $this->assertSame(75.0, $row['approval_rate']);
        $this->assertSame(72.5, $row['collection_success_rate']);
        $this->assertSame(72500.0, $row['actual_collection']);
        $this->assertSame(8, $row['successful_collections']);
    }

    public function test_it_ranks_by_the_selected_metric_and_assigns_positions(): void
    {
        $calculator = new LeaderboardMetricCalculator();
        $rows = collect([
            ['name' => 'Provider A', 'actual_collection' => 100, 'mandates_created' => 4],
            ['name' => 'Provider B', 'actual_collection' => 300, 'mandates_created' => 2],
        ]);

        $ranked = $calculator->rank($rows, 'actual_collection');

        $this->assertSame('Provider B', $ranked->first()['name']);
        $this->assertSame(1, $ranked->first()['rank']);
        $this->assertSame(2, $ranked->last()['rank']);
    }
}
