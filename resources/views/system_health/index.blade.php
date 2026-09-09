@extends('layouts.master')

@section('title')
    System Health
@endsection

@section('content')
    <div class="sp-list-shell">
        @component('components.breadcrumb')
            @slot('li_1')
                Dashboard
            @endslot
            @slot('title')
                System Health
            @endslot
        @endcomponent

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @elseif (session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif

        @php
            $overallClasses = [
                'healthy' => 'badge-soft-success',
                'degraded' => 'badge-soft-warning',
                'down' => 'badge-soft-danger',
                'unknown' => 'badge-soft-secondary',
            ];
            $statusClasses = [
                'healthy' => 'badge-soft-success',
                'down' => 'badge-soft-danger',
                'unknown' => 'badge-soft-secondary',
            ];
        @endphp

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h4 class="mb-1">Bank Connectivity</h4>
                <p class="text-muted mb-0">Non-transactional TCP/TLS checks for configured bank gateways.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge {{ $overallClasses[$overallStatus] ?? 'badge-soft-secondary' }}">
                    {{ ucfirst($overallStatus) }}
                </span>
                <form action="{{ route('system-health.run') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-refresh"></i> Check Now
                    </button>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            @forelse ($checks as $check)
                <div class="col-xl-4 col-md-6">
                    <div class="card sp-list-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="text-muted small mb-1">{{ $check->service }}</div>
                                    <h5 class="card-title mb-2">{{ $check->endpoint_label }}</h5>
                                </div>
                                <span class="badge {{ $statusClasses[$check->status] ?? 'badge-soft-secondary' }}">
                                    {{ ucfirst($check->status) }}
                                </span>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-6">
                                    <div class="text-muted small">Latency</div>
                                    <div>{{ $check->response_time_ms !== null ? number_format($check->response_time_ms) . ' ms' : 'NIL' }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Last checked</div>
                                    <div>{{ $check->checked_at?->diffForHumans() ?? 'Never' }}</div>
                                </div>
                            </div>
                            <p class="text-muted mb-0 mt-3">{{ $check->message }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        No health data is available. Run the first connectivity check.
                    </div>
                </div>
            @endforelse
        </div>

        <div class="card sp-list-card">
            <div class="card-body">
                <h5 class="card-title mb-3">Recent Checks</h5>
                <div class="table-responsive sp-list-table">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Checked At</th>
                                <th>Service</th>
                                <th>Endpoint</th>
                                <th>Status</th>
                                <th>Latency</th>
                                <th>Issue</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($history as $check)
                                <tr>
                                    <td class="text-nowrap">{{ $check->checked_at?->format('d-m-Y H:i:s') }}</td>
                                    <td>{{ $check->service }}</td>
                                    <td class="text-nowrap">{{ $check->endpoint_label }}</td>
                                    <td>
                                        <span class="badge {{ $statusClasses[$check->status] ?? 'badge-soft-secondary' }}">
                                            {{ ucfirst($check->status) }}
                                        </span>
                                    </td>
                                    <td class="text-nowrap">{{ $check->response_time_ms !== null ? number_format($check->response_time_ms) . ' ms' : 'NIL' }}</td>
                                    <td>{{ $check->error_category ? ucfirst($check->error_category) : 'NIL' }}</td>
                                    <td>{{ $check->message }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No checks recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
