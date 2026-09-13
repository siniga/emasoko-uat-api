@extends('layouts.master')

@section('title')
    BI Dashboard
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/dashboard.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('/assets/css/bi-dashboard.css') }}?v={{ filemtime(public_path('assets/css/bi-dashboard.css')) }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-0 font-size-18">BI Dashboard : {{ $headerMeta }}</h4>
                    <small class="text-muted">Data updated {{ $dataFreshAt?->format('d M Y H:i') ?? 'N/A' }}</small>
                </div>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">BI Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="sp-list-filter mb-3">
        <form action="{{ route('bi.dashboard') }}" method="GET">
            <input type="hidden" name="active_tab" value="{{ request('active_tab', 'growth') }}" data-bi-active-tab-input>
            <div class="row g-3">
                <div class="col-xl-2 col-lg-3 col-md-6">
                    <select class="form-select" id="bi_year" name="year">
                        @foreach (range(date('Y') - 3, date('Y') + 1) as $yearOption)
                            <option value="{{ $yearOption }}"
                                {{ (int) ($filters['year'] ?? 0) === (int) $yearOption ? 'selected' : '' }}>
                                {{ $yearOption }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-6">
                    <select class="form-select" id="bi_month" name="month">
                        <option value="">-- All Months --</option>
                        @foreach (range(1, 12) as $monthOption)
                            <option value="{{ $monthOption }}"
                                {{ (int) ($filters['month'] ?? 0) === (int) $monthOption ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $monthOption, 1)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-6">
                    <select class="form-control select2" id="bi_channel" name="channel">
                        <option value="">-- All Channels --</option>
                        @foreach ($paymentChannels as $paymentChannel)
                            <option value="{{ $paymentChannel->slug }}"
                                {{ ($filters['channel'] ?? '') === $paymentChannel->slug ? 'selected' : '' }}>
                                {{ $paymentChannel->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-4 col-lg-4 col-md-6">
                    <select class="form-control select2" id="partner_id" name="partner_id">
                        <option value="">-- All Service Providers --</option>
                        @foreach ($serviceProviderOptions as $serviceProviderOption)
                            <option value="{{ $serviceProviderOption->id }}"
                                {{ (int) ($filters['partner_id'] ?? 0) === (int) $serviceProviderOption->id ? 'selected' : '' }}>
                                {{ $serviceProviderOption->business_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-6" data-bi-reconciliation-filter>
                    <select class="form-control select2" id="recon_status" name="recon_status">
                        <option value="">-- All Statuses --</option>
                        <option value="COLLECTED" {{ ($reconciliationFilters['recon_status'] ?? '') === 'COLLECTED' ? 'selected' : '' }}>Collected</option>
                        <option value="PARTIAL" {{ ($reconciliationFilters['recon_status'] ?? '') === 'PARTIAL' ? 'selected' : '' }}>Partially Paid</option>
                        <option value="FAILED" {{ ($reconciliationFilters['recon_status'] ?? '') === 'FAILED' ? 'selected' : '' }}>Failed</option>
                        <option value="PENDING" {{ ($reconciliationFilters['recon_status'] ?? '') === 'PENDING' ? 'selected' : '' }}>Unreconciled</option>
                    </select>
                </div>

                <div class="col-12">
                    <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                        <a href="{{ route('bi.dashboard') }}" class="btn btn-outline-danger" data-bi-clear-link>
                            <i class='bx bx-reset'></i> Clear
                        </a>
                        <button type="submit" class="btn btn-secondary">
                            <i class="bx bx-filter-alt"></i> Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="bi-tab-bar mb-3">
        <button type="button" class="bi-tab is-active" data-bi-tab="growth">Growth Tracker</button>
        <button type="button" class="bi-tab" data-bi-tab="reconciliation">Installment Reconciliation</button>
    </div>

    <div class="bi-pane is-active" data-bi-pane="growth">
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Approved Providers Registered</div>
                    <div class="detail-summary__value text-primary">{{ number_format($growthSummary['partners_count']) }}
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Approved Mandates Created</div>
                    <div class="detail-summary__value text-success">
                        {{ number_format($growthSummary['approved_mandate_count']) }}</div>
                    <div class="detail-summary__meta">Value {{ number_format($growthSummary['approved_mandate_value']) }} TZS
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Expected Collections</div>
                    <div class="detail-summary__value text-primary">{{ number_format($growthSummary['period_expected']) }}</div>
                    <div class="detail-summary__meta">TZS due ·
    @if ($growthSummary['expected_change'] === null)
        no baseline
    @else
        {{ $growthSummary['expected_change'] > 0 ? '+' : '' }}{{ number_format($growthSummary['expected_change'], 2) }}%
    @endif
</div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted" title="Net posted settlement cash received in the selected period.">Cash Received</div>
                    <div class="detail-summary__value text-success">{{ number_format($growthSummary['period_collected']) }}</div>
                    <div class="detail-summary__meta">
                        {{ number_format($growthSummary['period_realization_rate'], 2) }}% realized ·
@if ($growthSummary['collected_change'] === null)
    no baseline
@else
    {{ $growthSummary['collected_change'] > 0 ? '+' : '' }}{{ number_format($growthSummary['collected_change'], 2) }}%
@endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="card dashboard-report-panel">
                    <div class="card-body">
                        <div class="dashboard-report-panel__head">
                            <div>
                                <div class="card-title" id="bi-growth-title">Expected collections vs cash received</div>
                                <p class="dashboard-report-panel__hint">Expected follows installment due dates; cash received follows settlement dates.</p>
                            </div>

                            <select id="bi-growth-view" class="form-select form-select-sm bi-inline-select">
                                <option value="collections">Expected vs cash received</option>
                                <option value="mandates">Mandate approval values</option>
                                <option value="success_rate">Cash realization rate</option>
                                <option value="new_mandates">New mandates created</option>
                            </select>
                        </div>

                        <div id="bi-growth-chart" class="apex-charts"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
                <div class="card dashboard-report-panel">
                    <div class="card-body">
                        <div class="card-title mb-4">Mandate Approval Pipeline</div>

                        <div class="dashboard-pipeline">
                            @foreach ($mandatePipeline as $item)
                                <div class="dashboard-pipeline__item">
                                    <div class="dashboard-pipeline__row">
                                        <span class="dashboard-pipeline__label">{{ $item['label'] }}</span>
                                        <span class="dashboard-pipeline__value pipeline-{{ $item['tone'] }}">
                                            {{ format_short_amount($item['amount']) }} TZS
                                        </span>
                                    </div>
                                    <div class="dashboard-pipeline__meta">
                                        <span>{{ number_format($item['count']) }} mandates</span>
                                        <span>{{ number_format($item['percent'], 1) }}%</span>
                                    </div>
                                    <div class="dashboard-pipeline__track">
                                        <div class="dashboard-pipeline__bar pipeline-{{ $item['tone'] }}"
                                            style="width: {{ min(max($item['percent'], 0), 100) }}%"></div>
                                    </div>
                                </div>
                            @endforeach

                            <div class="dashboard-pipeline__footer">
                                Total mandate value:
                                <strong>{{ format_short_amount(collect($mandatePipeline)->sum('amount')) }}
                                    TZS</strong><br>
                                Highest single: <strong>{{ format_short_amount($highestSingleMandate) }} TZS</strong><br>
                                Cash realization:
                                <strong>{{ number_format($growthSummary['selected_realization_rate'], 2) }}%</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
                <div class="card dashboard-report-panel">
                    <div class="card-body">
                        <div class="dashboard-report-panel__head">
                            <div>
                                <div class="card-title">Approved Mandates by Payment Channel</div>
                                <p class="dashboard-report-panel__hint">Mandates approved and created within the selected period.</p>
                            </div>
                        </div>

                        <div id="bi-channel-mix-chart" class="apex-charts"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bi-pane" data-bi-pane="reconciliation">
        <div class="bi-recon-kpi-grid">
            <div class="detail-summary shadow h-96 py-2">
                <div class="detail-summary__label text-muted">Scheduled Installments</div>
                <div class="detail-summary__value text-primary">{{ number_format($reconciliationSummary['total_count']) }}</div>
            </div>
            <div class="detail-summary shadow h-96 py-2">
                <div class="detail-summary__label text-muted">Fully Paid</div>
                <div class="detail-summary__value text-success">{{ number_format($reconciliationSummary['collected_count']) }}</div>
                <div class="detail-summary__sub">TZS {{ number_format($reconciliationSummary['collected_amount']) }}</div>
            </div>
            <div class="detail-summary shadow h-96 py-2">
                <div class="detail-summary__label text-muted">Partially Paid</div>
                <div class="detail-summary__value text-warning">{{ number_format($reconciliationSummary['partial_count']) }}</div>
                <div class="detail-summary__sub">TZS {{ number_format($reconciliationSummary['partial_amount']) }}</div>
            </div>
            <div class="detail-summary shadow h-96 py-2">
                <div class="detail-summary__label text-muted">Failed</div>
                <div class="detail-summary__value text-danger">{{ number_format($reconciliationSummary['failed_count']) }}</div>
                <div class="detail-summary__sub">TZS {{ number_format($reconciliationSummary['failed_amount']) }}</div>
            </div>
            <div class="detail-summary shadow h-96 py-2">
                <div class="detail-summary__label text-muted">Unreconciled</div>
                <div class="detail-summary__value text-warning">{{ number_format($reconciliationSummary['pending_count']) }}</div>
                <div class="detail-summary__sub">TZS {{ number_format($reconciliationSummary['pending_amount']) }}</div>
            </div>
        </div>

        <div class="card dashboard-report-panel">
            <div class="card-body">
                <div class="dashboard-report-panel__head">
                    <div>
                        <div class="card-title">
                            {{ $selectedServiceProviderName ?: 'All Service Providers' }} - {{ $headerMeta }} scheduled-installment reconciliation
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 bi-recon-table">
                        <thead>
                            <tr>
                                <th>Service Provider</th>
                                <th>Borrower</th>
                                <th>Loan #</th>
                                <th class="text-end">Scheduled (TZS)</th>
                                <th class="text-end">Collected (TZS)</th>
                                <th>Payment Date</th>
                                <th>Channel</th>
                                <th>eMakato Ref</th>
                                <th>Status</th>
                                <th>Settlement</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reconciliationDetail as $detail)
                                <tr>
                                    <td>{{ $detail['service_provider_name'] }}</td>
                                    <td class="fw-semibold">
                                        {{ strtoupper($detail['borrower_name']) }}<br/>
                                        {{ $detail['national_id'] }}
                                    </td>
                                    <td>{{ $detail['loan_reference'] }}</td>
                                    <td class="text-end">{{ number_format($detail['scheduled_amount']) }}</td>
                                    <td class="text-end">{{ number_format($detail['collected_amount']) }}</td>
                                    <td>{{ $detail['payment_date'] }}</td>
                                    <td>{{ $detail['channel'] }}</td>
                                    <td>{{ $detail['emakato_reference'] }}</td>
                                    <td>
                                        <span
                                            class="badge {{ $detail['status'] === 'CLOSED' ? 'badge-soft-success' : (in_array($detail['status'], ['FAILED', 'FUNDSCHECK'], true) ? 'badge-soft-danger' : 'badge-soft-warning') }}">
                                            {{ str_replace('_', ' ', $detail['status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $detail['statement_matched'] ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                            {{ $detail['statement_matched'] ? 'Matched' : 'Pending' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted">No reconciliation detail found for
                                        the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($reconciliationDetail->hasPages())
                    <div class="mt-3">
                        {{ $reconciliationDetail->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        window.BI_DASHBOARD_PAYLOAD = @json($chartPayload);
    </script>
    <script src="{{ URL::asset('/assets/js/bi-dashboard.js') }}?v={{ filemtime(public_path('assets/js/bi-dashboard.js')) }}"></script>
@endsection
