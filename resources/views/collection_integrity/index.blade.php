@extends('layouts.master')

@section('title')
    Collection Integrity
@endsection

@section('content')
    <div class="sp-list-shell">
        @component('components.breadcrumb')
            @slot('li_1') Dashboard @endslot
            @slot('title') Collection Integrity @endslot
        @endcomponent

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @elseif (session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif

        <div class="card sp-list-card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('collection-integrity.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">From</label>
                            <input type="date" name="start_at" class="form-control" value="{{ $filters['start_at'] }}">
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">To</label>
                            <input type="date" name="end_at" class="form-control" value="{{ $filters['end_at'] }}">
                        </div>
                        <div class="col-lg-3 col-md-4">
                            <label class="form-label">Issue</label>
                            <select name="issue_type" class="form-control select2">
                                <option value="">All issues</option>
                                @foreach ($issueTypes as $type)
                                    <option value="{{ $type }}" @selected($filters['issue_type'] === $type)>
                                        {{ str_replace('_', ' ', $type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Severity</label>
                            <select name="severity" class="form-control select2">
                                <option value="">All severities</option>
                                <option value="CRITICAL" @selected($filters['severity'] === 'CRITICAL')>Critical</option>
                                <option value="ERROR" @selected($filters['severity'] === 'ERROR')>Error</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-8 d-flex gap-2">
                            <button class="btn btn-primary" type="submit"><i class="bx bx-filter-alt"></i> Filter</button>
                            <a class="btn btn-light" href="{{ route('collection-integrity.index') }}">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6">
                <div class="detail-summary shadow h-100 py-2">
                    <div class="detail-summary__label text-muted">Open Exceptions</div>
                    <div class="detail-summary__value text-danger">{{ number_format($summary->issue_count ?? 0) }}</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="detail-summary shadow h-100 py-2">
                    <div class="detail-summary__label text-muted">Exception Value</div>
                    <div class="detail-summary__value text-danger">TZS {{ number_format($summary->issue_amount ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="detail-summary shadow h-100 py-2" title="Installments scheduled inside the selected period.">
                    <div class="detail-summary__label text-muted">Schedule Expected</div>
                    <div class="detail-summary__value text-primary">TZS {{ number_format($accounting['expected_amount'], 2) }}</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="detail-summary shadow h-100 py-2" title="Posted settlement events received inside the selected period.">
                    <div class="detail-summary__label text-muted">Cash Received</div>
                    <div class="detail-summary__value text-success">TZS {{ number_format($accounting['cash_received'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <small class="text-muted">Last audited: {{ $lastAuditAt ? \Carbon\Carbon::parse($lastAuditAt)->format('d M Y H:i') : 'Never' }}</small>
            <form method="POST" action="{{ route('collection-integrity.run') }}">
                @csrf
                <input type="hidden" name="start_at" value="{{ $filters['start_at'] }}">
                <input type="hidden" name="end_at" value="{{ $filters['end_at'] }}">
                <button class="btn btn-primary" type="submit"><i class="bx bx-refresh"></i> Run Audit</button>
            </form>
        </div>

        <div class="card sp-list-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-nowrap">
                        <thead>
                            <tr>
                                <th>Severity</th>
                                <th>Issue</th>
                                <th>Mandate</th>
                                <th>Payment Reference</th>
                                <th>Installment</th>
                                <th>Event Date</th>
                                <th class="text-end">Amount</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($issues as $issue)
                                <tr>
                                    <td><span class="badge {{ $issue->severity === 'CRITICAL' ? 'badge-soft-danger' : 'badge-soft-warning' }}">{{ $issue->severity }}</span></td>
                                    <td>{{ str_replace('_', ' ', $issue->issue_type) }}</td>
                                    <td>{{ $issue->mandate_reference ?: 'N/A' }}</td>
                                    <td>{{ $issue->payment_reference ?: 'N/A' }}</td>
                                    <td>{{ $issue->installment_id ?: 'N/A' }}</td>
                                    <td>{{ $issue->event_date?->format('d M Y') ?: 'N/A' }}</td>
                                    <td class="text-end">{{ number_format($issue->amount, 2) }}</td>
                                    <td>{{ $issue->description }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">No integrity exceptions found for this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $issues->links() }}</div>
            </div>
        </div>
    </div>
@endsection
