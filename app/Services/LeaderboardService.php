<?php

namespace App\Services;

use App\Models\Partner;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class LeaderboardService
{
    public const MODE_SERVICE_PROVIDERS = 'service_providers';
    public const MODE_USERS = 'users';

    public function __construct(
        private DashboardReportingQueryService $reporting,
        private LeaderboardMetricCalculator $calculator
    ) {
    }

    public function report(User $user, array $filters): array
    {
        $mode = $this->modeFor($user);
        $reportFilters = collect($filters)
            ->only([
                'start_at',
                'end_at',
                'partner_id',
                'channel',
                'mandate_type',
                'keyword',
                'sort_by',
            ])
            ->all();

        return $this->reporting->remember(
            $user,
            'leaderboard',
            array_merge($reportFilters, ['mode' => $mode]),
            fn () => $this->buildReport($user, $mode, $reportFilters)
        );
    }

    private function buildReport(User $user, string $mode, array $filters): array
    {
        $groupColumn = $mode === self::MODE_SERVICE_PROVIDERS ? 'partner_id' : 'created_by';
        [$start, $end] = $this->period($filters);
        $queryFilters = [
            'partner_id' => $filters['partner_id'] ?? null,
            'channel' => $filters['channel'] ?? null,
            'mandate_type' => $filters['mandate_type'] ?? null,
        ];

        $mandates = $this->reporting->mandates($user, $queryFilters)
            ->whereBetween('mandate_created_date', [$start, $end])
            ->select($groupColumn)
            ->selectRaw('COUNT(debit_order_id) as mandates_created')
            ->selectRaw('SUM(CASE WHEN mandate_approved = 1 THEN 1 ELSE 0 END) as mandates_approved')
            ->groupBy($groupColumn)
            ->get()
            ->keyBy($groupColumn);

        $expected = $this->reporting->expectedCollections($user, $queryFilters)
            ->whereBetween('expected_date', [$start, $end])
            ->select($groupColumn)
            ->selectRaw('COALESCE(SUM(installment_amount), 0) as expected_amount')
            ->groupBy($groupColumn)
            ->get()
            ->keyBy($groupColumn);

        $actual = $this->reporting->paymentCollections($user, $queryFilters)
            ->whereBetween('collected_date', [$start, $end])
            ->select($groupColumn)
            ->selectRaw('COUNT(settlement_id) as successful_collections')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as actual_amount')
            ->groupBy($groupColumn)
            ->get()
            ->keyBy($groupColumn);

        $rows = $this->identities($user, $mode, $filters)
            ->map(function (array $identity) use ($mandates, $expected, $actual) {
                $key = $identity['id'];

                return $this->calculator->row(
                    $identity,
                    (array) ($mandates->get($key) ?? []),
                    (array) ($expected->get($key) ?? []),
                    (array) ($actual->get($key) ?? [])
                );
            })
            ->when($filters['keyword'] ?? null, function (Collection $rows, string $keyword) {
                $needle = mb_strtolower(trim($keyword));

                return $rows->filter(fn (array $row) => str_contains(
                    mb_strtolower($row['name'] . ' ' . ($row['reference'] ?? '')),
                    $needle
                ));
            });

        $ranked = $this->calculator->rank($rows, $filters['sort_by'] ?? 'actual_collection');
        $summary = [
            'participants' => $ranked->count(),
            'mandates_created' => $ranked->sum('mandates_created'),
            'mandates_approved' => $ranked->sum('mandates_approved'),
            'expected_collection' => round((float) $ranked->sum('expected_collection'), 2),
            'actual_collection' => round((float) $ranked->sum('actual_collection'), 2),
        ];
        $summary['collection_success_rate'] = $this->calculator->percentage(
            $summary['actual_collection'],
            $summary['expected_collection']
        );

        return [
            'mode' => $mode,
            'title' => $mode === self::MODE_SERVICE_PROVIDERS
                ? 'Service Provider Leaderboard'
                : 'User Leaderboard',
            'period' => ['start_at' => $start, 'end_at' => $end],
            'summary' => $summary,
            'rows' => $ranked,
        ];
    }

    public function modeFor(User $user): string
    {
        if ($user->hasAnyRole(['admin', 'Manager', 'manager'])) {
            return self::MODE_SERVICE_PROVIDERS;
        }

        if ($user->hasRole('Company_Approver')) {
            return self::MODE_USERS;
        }

        throw new AuthorizationException('You are not allowed to view the leaderboard.');
    }

    public function accessibleProviders(User $user): Collection
    {
        $query = Partner::query()->where('approved', 1)->orderBy('business_name');

        if ($user->hasRole('Company_Approver')) {
            $partnerIds = $user->partners()
                ->where('authorize', 1)
                ->pluck('partner_id');

            $query->whereIn('id', $partnerIds);
        }

        return $query->get(['id', 'reference', 'business_name']);
    }

    private function identities(User $user, string $mode, array $filters): Collection
    {
        if ($mode === self::MODE_SERVICE_PROVIDERS) {
            return $this->accessibleProviders($user)
                ->when($filters['partner_id'] ?? null, fn (Collection $items, $id) => $items->where('id', (int) $id))
                ->map(fn (Partner $partner) => [
                    'id' => $partner->id,
                    'name' => $partner->business_name,
                    'reference' => $partner->reference,
                    'type' => 'service_provider',
                ])
                ->values();
        }

        $partnerIds = $this->accessibleProviders($user)
            ->when($filters['partner_id'] ?? null, fn (Collection $items, $id) => $items->where('id', (int) $id))
            ->pluck('id');

        return User::query()
            ->whereHas('partners', function ($query) use ($partnerIds) {
                $query->where('authorize', 1)->whereIn('partner_id', $partnerIds);
            })
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Company_Approver', 'Company_User']))
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'reference' => $member->email,
                'type' => 'user',
            ]);
    }

    private function period(array $filters): array
    {
        $start = !empty($filters['start_at'])
            ? Carbon::parse($filters['start_at'])->toDateString()
            : now()->startOfMonth()->toDateString();
        $end = !empty($filters['end_at'])
            ? Carbon::parse($filters['end_at'])->toDateString()
            : now()->endOfMonth()->toDateString();

        if ($start > $end) {
            throw new \InvalidArgumentException('Start date must not be after end date.');
        }

        return [$start, $end];
    }
}
