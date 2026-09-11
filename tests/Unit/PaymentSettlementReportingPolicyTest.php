<?php

namespace Tests\Unit;

use App\Models\PaymentSettlement;
use PHPUnit\Framework\TestCase;

class PaymentSettlementReportingPolicyTest extends TestCase
{
    public function test_reportable_statuses_include_append_only_reversals(): void
    {
        $this->assertSame([
            PaymentSettlement::STATUS_SUCCESS,
            PaymentSettlement::STATUS_PARTIAL,
            PaymentSettlement::STATUS_REVERSED,
        ], PaymentSettlement::reportableStatuses());
    }

    public function test_money_and_dates_have_explicit_model_casts(): void
    {
        $casts = (new PaymentSettlement())->getCasts();

        $this->assertSame('decimal:2', $casts['paid_amount']);
        $this->assertSame('decimal:2', $casts['expected_amount']);
        $this->assertSame('date', $casts['effective_payment_date']);
    }
}
