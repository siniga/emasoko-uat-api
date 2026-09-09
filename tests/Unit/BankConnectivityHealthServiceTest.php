<?php

namespace Tests\Unit;

use App\Services\BankConnectivityHealthService;
use Tests\TestCase;

class BankConnectivityHealthServiceTest extends TestCase
{
    public function test_overall_status_is_healthy_when_all_targets_are_reachable(): void
    {
        $service = new BankConnectivityHealthService();

        $status = $service->overallStatus(collect([
            (object) ['status' => 'healthy'],
            (object) ['status' => 'healthy'],
        ]));

        $this->assertSame('healthy', $status);
    }

    public function test_overall_status_is_down_when_any_target_is_down(): void
    {
        $service = new BankConnectivityHealthService();

        $status = $service->overallStatus(collect([
            (object) ['status' => 'healthy'],
            (object) ['status' => 'down'],
        ]));

        $this->assertSame('down', $status);
    }

    public function test_overall_status_is_degraded_when_a_target_is_not_configured(): void
    {
        $service = new BankConnectivityHealthService();

        $status = $service->overallStatus(collect([
            (object) ['status' => 'healthy'],
            (object) ['status' => 'unknown'],
        ]));

        $this->assertSame('degraded', $status);
    }

    public function test_overall_status_is_unknown_before_any_configured_check(): void
    {
        $service = new BankConnectivityHealthService();

        $this->assertSame('unknown', $service->overallStatus(collect()));
        $this->assertSame('unknown', $service->overallStatus(collect([
            (object) ['status' => 'unknown'],
        ])));
    }
}
