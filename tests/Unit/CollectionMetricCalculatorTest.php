<?php

namespace Tests\Unit;

use App\Services\CollectionMetricCalculator;
use PHPUnit\Framework\TestCase;

class CollectionMetricCalculatorTest extends TestCase
{
    private CollectionMetricCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CollectionMetricCalculator();
    }

    public function test_partial_payments_are_accumulated_without_duplicating_expected_amount(): void
    {
        $result = $this->calculator->installment('100000.00', ['30000.00', '25000.00']);

        $this->assertSame(100000.0, $result['expected_amount']);
        $this->assertSame(55000.0, $result['schedule_paid_amount']);
        $this->assertSame(45000.0, $result['outstanding_amount']);
        $this->assertSame(2, $result['payment_event_count']);
        $this->assertTrue($result['is_partial']);
        $this->assertSame(0.0, $result['identity_difference']);
    }

    public function test_reversal_is_a_negative_event_and_restores_outstanding(): void
    {
        $result = $this->calculator->installment('100000.00', ['100000.00', '-100000.00']);

        $this->assertSame(0.0, $result['net_paid_amount']);
        $this->assertSame(0.0, $result['schedule_paid_amount']);
        $this->assertSame(100000.0, $result['outstanding_amount']);
        $this->assertSame(2, $result['payment_event_count']);
        $this->assertFalse($result['is_fully_paid']);
    }

    public function test_overpayment_is_visible_in_cash_but_capped_for_schedule_performance(): void
    {
        $result = $this->calculator->installment('100000.00', ['120000.00']);

        $this->assertSame(120000.0, $result['net_paid_amount']);
        $this->assertSame(100000.0, $result['schedule_paid_amount']);
        $this->assertSame(0.0, $result['outstanding_amount']);
        $this->assertTrue($result['is_fully_paid']);
        $this->assertSame(0.0, $result['identity_difference']);
    }

    public function test_money_is_rounded_to_currency_precision(): void
    {
        $result = $this->calculator->installment('24600.005', ['400.004']);

        $this->assertSame(24600.01, $result['expected_amount']);
        $this->assertSame(400.0, $result['schedule_paid_amount']);
        $this->assertSame(24200.01, $result['outstanding_amount']);
    }
}
