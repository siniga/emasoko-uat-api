<?php

namespace App\Services;

use Illuminate\Support\Collection;

final class LeaderboardMetricCalculator
{
    public function row(array $identity, array $mandates, array $expected, array $actual): array
    {
        $expectedAmount = round((float) ($expected['expected_amount'] ?? 0), 2);
        $actualAmount = round((float) ($actual['actual_amount'] ?? 0), 2);

        return array_merge($identity, [
            'mandates_created' => (int) ($mandates['mandates_created'] ?? 0),
            'mandates_approved' => (int) ($mandates['mandates_approved'] ?? 0),
            'approval_rate' => $this->percentage(
                (float) ($mandates['mandates_approved'] ?? 0),
                (float) ($mandates['mandates_created'] ?? 0)
            ),
            'expected_collection' => $expectedAmount,
            'actual_collection' => $actualAmount,
            'successful_collections' => (int) ($actual['successful_collections'] ?? 0),
            'collection_success_rate' => $this->percentage($actualAmount, $expectedAmount),
        ]);
    }

    public function rank(Collection $rows, string $sortBy = 'actual_collection'): Collection
    {
        $allowed = [
            'mandates_created',
            'mandates_approved',
            'actual_collection',
            'collection_success_rate',
        ];
        $metric = in_array($sortBy, $allowed, true) ? $sortBy : 'actual_collection';

        return $rows
            ->sort(function (array $left, array $right) use ($metric) {
                $comparison = ($right[$metric] ?? 0) <=> ($left[$metric] ?? 0);

                return $comparison !== 0
                    ? $comparison
                    : strcasecmp((string) $left['name'], (string) $right['name']);
            })
            ->values()
            ->map(function (array $row, int $index) {
                $row['rank'] = $index + 1;

                return $row;
            });
    }

    public function percentage(float $value, float $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 2) : 0.0;
    }
}
