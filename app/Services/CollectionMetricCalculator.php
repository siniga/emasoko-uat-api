<?php

namespace App\Services;

final class CollectionMetricCalculator
{
    public function installment(string|int|float $expectedAmount, array $signedPayments): array
    {
        $expected = max($this->toCents($expectedAmount), 0);
        $netPaid = array_sum(array_map(fn ($amount) => $this->toCents($amount), $signedPayments));
        $schedulePaid = min(max($netPaid, 0), $expected);
        $outstanding = $expected - $schedulePaid;

        return [
            'expected_amount' => $this->fromCents($expected),
            'net_paid_amount' => $this->fromCents($netPaid),
            'schedule_paid_amount' => $this->fromCents($schedulePaid),
            'outstanding_amount' => $this->fromCents($outstanding),
            'payment_event_count' => count($signedPayments),
            'is_fully_paid' => $expected > 0 && $schedulePaid === $expected,
            'is_partial' => $schedulePaid > 0 && $schedulePaid < $expected,
            'identity_difference' => $this->fromCents($expected - $schedulePaid - $outstanding),
        ];
    }

    private function toCents(string|int|float $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function fromCents(int $amount): float
    {
        return $amount / 100;
    }
}
