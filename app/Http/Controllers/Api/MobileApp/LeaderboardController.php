<?php

namespace App\Http\Controllers\Api\MobileApp;

use App\Http\Controllers\Api\ExternalApp\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\DashboardReportingQueryService;
use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    use RespondsWithApi;

    public function __construct(
        private LeaderboardService $leaderboard,
        private DashboardReportingQueryService $reporting
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'partner_id' => ['nullable', 'integer'],
            'channel' => ['nullable', 'string', 'max:50'],
            'mandate_type' => ['nullable', 'string', 'max:50'],
            'keyword' => ['nullable', 'string', 'max:100'],
            'sort_by' => ['nullable', 'in:mandates_created,mandates_approved,actual_collection,collection_success_rate'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $report = $this->leaderboard->report($request->user(), $filters);
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 25);

        return $this->successResponse('Leaderboard fetched successfully.', [
            'mode' => $report['mode'],
            'title' => $report['title'],
            'data_fresh_at' => optional($this->reporting->lastUpdatedAt())->toIso8601String(),
            'period' => $report['period'],
            'filters' => [
                'partner_id' => $filters['partner_id'] ?? null,
                'channel' => $filters['channel'] ?? null,
                'mandate_type' => $filters['mandate_type'] ?? null,
                'keyword' => $filters['keyword'] ?? null,
                'sort_by' => $filters['sort_by'] ?? 'actual_collection',
            ],
            'summary' => $report['summary'],
            'filter_options' => [
                'service_providers' => $this->leaderboard
                    ->accessibleProviders($request->user())
                    ->map(fn ($provider) => [
                        'id' => $provider->id,
                        'reference' => $provider->reference,
                        'name' => $provider->business_name,
                    ])
                    ->values(),
                'channels' => Account::query()
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug']),
                'mandate_types' => DB::table('vw_dashboard_mandates')
                    ->whereNotNull('mandate_type')
                    ->distinct()
                    ->orderBy('mandate_type')
                    ->pluck('mandate_type'),
                'rank_by' => [
                    ['value' => 'actual_collection', 'label' => 'Actual Collection'],
                    ['value' => 'collection_success_rate', 'label' => 'Collection Success'],
                    ['value' => 'mandates_created', 'label' => 'Mandates Created'],
                    ['value' => 'mandates_approved', 'label' => 'Mandates Approved'],
                ],
            ],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $report['rows']->count(),
                'last_page' => max((int) ceil($report['rows']->count() / $perPage), 1),
            ],
            'leaderboard' => $report['rows']->forPage($page, $perPage)->values(),
        ]);
    }
}
