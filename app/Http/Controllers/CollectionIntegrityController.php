<?php

namespace App\Http\Controllers;

use App\Models\CollectionIntegrityIssue;
use App\Services\CollectionIntegrityService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CollectionIntegrityController extends Controller
{
    public function __construct(protected CollectionIntegrityService $integrityService)
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $this->authorizeAccess();
        [$start, $end] = $this->period($request);

        $query = CollectionIntegrityIssue::query()
            ->when(!$request->boolean('include_resolved'), fn ($query) => $query->open())
            ->when($request->filled('issue_type'), fn ($query) => $query->where('issue_type', $request->issue_type))
            ->when($request->filled('severity'), fn ($query) => $query->where('severity', $request->severity))
            ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()]);

        $summary = (clone $query)
            ->selectRaw('COUNT(*) as issue_count, COALESCE(SUM(amount), 0) as issue_amount')
            ->first();

        return view('collection_integrity.index', [
            'title' => 'Collection Integrity',
            'issues' => $query->latest('last_detected_at')->paginate(50)->withQueryString(),
            'summary' => $summary,
            'accounting' => $this->integrityService->periodSummary($start, $end),
            'filters' => [
                'start_at' => $start->toDateString(),
                'end_at' => $end->toDateString(),
                'issue_type' => $request->input('issue_type'),
                'severity' => $request->input('severity'),
                'include_resolved' => $request->boolean('include_resolved'),
            ],
            'issueTypes' => CollectionIntegrityIssue::query()->distinct()->orderBy('issue_type')->pluck('issue_type'),
            'lastAuditAt' => CollectionIntegrityIssue::max('last_detected_at'),
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        $this->authorizeAccess();
        [$start, $end] = $this->period($request);
        $result = $this->integrityService->audit($start, $end);

        return redirect()
            ->route('collection-integrity.index', [
                'start_at' => $start->toDateString(),
                'end_at' => $end->toDateString(),
            ])
            ->with(
                $result['detected_count'] > 0 ? 'warning' : 'success',
                "Audit {$result['run_id']} completed with {$result['detected_count']} issue(s)."
            );
    }

    private function period(Request $request): array
    {
        $start = $request->filled('start_at')
            ? Carbon::parse($request->start_at)->startOfDay()
            : now()->startOfMonth();
        $end = $request->filled('end_at')
            ? Carbon::parse($request->end_at)->endOfDay()
            : now()->endOfMonth();

        abort_if($start->gt($end), 422, 'Start date must not be after end date.');
        return [$start, $end];
    }

    private function authorizeAccess(): void
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['admin', 'Manager', 'manager']),
            403
        );
    }
}
