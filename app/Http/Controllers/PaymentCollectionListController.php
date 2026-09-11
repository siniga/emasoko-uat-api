<?php

namespace App\Http\Controllers;

use App\Exports\PaymentCollectionsExport;
use App\Models\Account;
use App\Models\User;
use App\Services\PaymentCollectionQueryService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentCollectionListController extends Controller
{
    public function __construct(private PaymentCollectionQueryService $collections)
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->authorizeAccess($user);
        $filters = $this->filters($request);
        $query = $this->collections->query($user, $filters);
        $summary = $this->collections->summary($query);
        $rows = $query
            ->orderByDesc('effective_payment_date')
            ->orderByDesc('settlement_id')
            ->paginate(50)
            ->withQueryString();

        return view('payment_collections.index', [
            'title' => 'Payment Collections',
            'collections' => $rows,
            'summary' => $summary,
            'filters' => $filters,
            'providers' => $this->collections->accessibleProviders($user),
            'channels' => Account::query()->where('active', 1)->orderBy('name')->get(['name', 'slug']),
            'statuses' => PaymentCollectionQueryService::statuses(),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        $this->authorizeAccess($user);
        $filters = $this->filters($request);
        $query = $this->collections->query($user, $filters)
            ->orderBy('effective_payment_date')
            ->orderBy('settlement_id');

        return Excel::download(
            new PaymentCollectionsExport($query),
            'Payment_Collections_' . now()->format('YmdHis') . '.xlsx'
        );
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'partner_id' => ['nullable', 'integer'],
            'channel' => ['nullable', 'string', 'max:50'],
            'mandate_type' => ['nullable', 'string', 'max:50'],
            'collection_status' => ['nullable', 'in:SUCCESS,PARTIAL,REVERSED'],
            'keyword' => ['nullable', 'string', 'max:100'],
        ]);

        return array_merge($validated, [
            'start_at' => $validated['start_at'] ?? now()->startOfMonth()->toDateString(),
            'end_at' => $validated['end_at'] ?? now()->endOfMonth()->toDateString(),
        ]);
    }

    private function authorizeAccess(?User $user): void
    {
        abort_unless(
            $user && $user->hasAnyRole([
                'admin',
                'Manager',
                'manager',
                'Partner',
                'Company_Approver',
                'Company_User',
            ]),
            403
        );
    }
}
