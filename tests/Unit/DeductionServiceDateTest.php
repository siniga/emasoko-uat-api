<?php

namespace Tests\Unit;

use App\Services\DeductionService;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class DeductionServiceDateTest extends TestCase
{
    public function test_weekly_and_daily_date_advancement(): void
    {
        $service = new DeductionService();
        $method = new ReflectionMethod($service, 'advanceInstallmentDate');
        $method->setAccessible(true);

        $weekly = $method->invoke($service, Carbon::parse('2026-03-08'), 'WEEKLY', 8);
        $daily = $method->invoke($service, Carbon::parse('2026-03-08'), 'DAILY', 8);

        $this->assertSame('2026-03-15', $weekly->toDateString());
        $this->assertSame('2026-03-09', $daily->toDateString());
    }

    public function test_monthly_date_advancement_keeps_day_or_caps_at_month_end(): void
    {
        $service = new DeductionService();
        $method = new ReflectionMethod($service, 'advanceInstallmentDate');
        $method->setAccessible(true);

        $monthly = $method->invoke($service, Carbon::parse('2026-01-31'), 'MONTHLY', 31);

        $this->assertSame('2026-02-28', $monthly->toDateString());
    }
}
