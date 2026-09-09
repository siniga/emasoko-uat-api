<?php

namespace App\Http\Controllers;

use App\Services\BankConnectivityHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function __construct(protected BankConnectivityHealthService $healthService)
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $this->authorizeSystemHealth();

        $checks = $this->healthService->latest();

        return view('system_health.index', [
            'title' => 'System Health',
            'checks' => $checks,
            'history' => $this->healthService->history(),
            'overallStatus' => $this->healthService->overallStatus($checks),
        ]);
    }

    public function run(): RedirectResponse
    {
        $this->authorizeSystemHealth();

        $checks = $this->healthService->run();
        $down = $checks->where('status', 'down')->count();
        $unknown = $checks->where('status', 'unknown')->count();

        $message = $down > 0
            ? "{$down} bank connection check(s) failed."
            : ($unknown > 0
                ? "Connectivity checks completed with {$unknown} unconfigured endpoint(s)."
                : 'All configured bank gateways are reachable.');

        return redirect()
            ->route('system-health.index')
            ->with($down > 0 ? 'warning' : 'success', $message);
    }

    protected function authorizeSystemHealth(): void
    {
        $user = Auth::user();

        $hasRoleAccess = $user && method_exists($user, 'hasRole')
            ? $user->hasRole('admin') || $user->hasRole('Manager') || $user->hasRole('manager')
            : false;

        abort_unless($hasRoleAccess, 403);
    }
}
