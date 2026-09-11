<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function __construct(private LeaderboardService $leaderboard)
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $report = $this->leaderboard->report($request->user(), $filters);
        $perPage = 50;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $rows = new LengthAwarePaginator(
            $report['rows']->forPage($page, $perPage)->values(),
            $report['rows']->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('leaderboard.index', [
            'title' => $report['title'],
            'mode' => $report['mode'],
            'period' => $report['period'],
            'summary' => $report['summary'],
            'rows' => $rows,
            'filters' => $filters,
            'providers' => $this->leaderboard->accessibleProviders($request->user()),
            'channels' => Account::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'mandateTypes' => DB::table('vw_dashboard_mandates')
                ->whereNotNull('mandate_type')
                ->distinct()
                ->orderBy('mandate_type')
                ->pluck('mandate_type'),
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'partner_id' => ['nullable', 'integer'],
            'channel' => ['nullable', 'string', 'max:50'],
            'mandate_type' => ['nullable', 'string', 'max:50'],
            'keyword' => ['nullable', 'string', 'max:100'],
            'sort_by' => ['nullable', 'in:mandates_created,mandates_approved,actual_collection,collection_success_rate'],
        ]);
    }
}
