@extends('layouts.master')

@section('title')
    @lang('translation.Dashboards')
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/dashboard.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-0 font-size-18">Summaries : {{ request('channel') ?? 'All Channels' }}</h4>
                    <small class="text-muted">Data updated {{ $dataFreshAt?->format('d M Y H:i') ?? 'N/A' }}</small>
                </div>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Summaries</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

    @php
        $selectedMandateYear = request()->filled('year') ? (int) request('year') : null;
        $mandateCardFilters = array_filter([
            'payment_channel' => $post_data['channel'] ?? null,
            'start_at' => $selectedMandateYear ? $selectedMandateYear . '-01-01' : null,
            'end_at' => $selectedMandateYear ? $selectedMandateYear . '-12-31' : null,
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

    <div class="row mb-3">
        <div class="col-md-3">
            <a href="{{ route('payment-mandate.lists', $mandateCardFilters) }}">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Total Mandates</div>
                    <div class="detail-summary__split">
                        <div class="detail-summary__metric">
                            <div class="detail-summary__value text-primary">
                                {{ number_format($stats['total_mandate_value']) }}</div>
                        </div>
                        <div class="detail-summary__metric detail-summary__metric--count">
                            <div class="detail-summary__value text-primary">
                                {{ number_format($stats['total_number_of_mandate']) }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('payment-mandate.lists', $mandateCardFilters + ['approval_status' => 1]) }}">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Approved Mandates</div>
                    <div class="detail-summary__split">
                        <div class="detail-summary__metric">
                            <div class="detail-summary__value text-success">
                                {{ number_format($stats['approved_mandate_value']) }}</div>
                        </div>
                        <div class="detail-summary__metric detail-summary__metric--count">
                            <div class="detail-summary__value text-success">
                                {{ number_format($stats['number_of_approved_mandate']) }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('payment-mandate.lists', $mandateCardFilters + ['approval_status' => 0]) }}">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Pending Approval Mandates</div>
                    <div class="detail-summary__split">
                        <div class="detail-summary__metric">
                            <div class="detail-summary__value text-warning">
                                {{ number_format($stats['pending_mandate_value']) }}</div>
                        </div>
                        <div class="detail-summary__metric detail-summary__metric--count">
                            <div class="detail-summary__value text-warning">
                                {{ number_format($stats['number_of_pending_mandate']) }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('payment-mandate.lists', $mandateCardFilters + ['approval_status' => 2]) }}">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Failed Approval Mandates</div>
                    <div class="detail-summary__split">
                        <div class="detail-summary__metric">
                            <div class="detail-summary__value text-danger">
                                {{ number_format($stats['failed_mandate_value']) }}</div>
                        </div>
                        <div class="detail-summary__metric detail-summary__metric--count">
                            <div class="detail-summary__value text-danger">
                                {{ number_format($stats['number_of_failed_mandate']) }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3">
            <a href="{{ route('collections.expected-monthly') }}">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted" title="Installments scheduled in this month.">Expected Collection - {{ $stats['month'] }}</div>
                    <div class="detail-summary__split">
                        <div class="detail-summary__metric">
                            <div class="detail-summary__value text-primary">
                                {{ number_format($stats['this_month_expected']) }}</div>
                        </div>
                        <div class="detail-summary__metric detail-summary__metric--count">
                            <div class="detail-summary__value text-primary">{{ number_format($stats['this_month_total_collections']) }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('collections.actual-monthly') }}">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted" title="Net posted settlement cash received this month.">Actual Collection - {{ $stats['month'] }}</div>
                    <div class="detail-summary__split">
                        <div class="detail-summary__metric">
                            <div class="detail-summary__value text-success">{{ number_format($stats['this_month_paid']) }}
                            </div>
                        </div>
                        <div class="detail-summary__metric detail-summary__metric--count">
                            <div class="detail-summary__value text-success">{{ number_format($stats['this_month_successful_collections']) }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('collections.expected-daily') }}">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Expected Collection - Today</div>
                    <div class="detail-summary__split">
                        <div class="detail-summary__metric">
                            <div class="detail-summary__value text-primary">{{ number_format($stats['today_expected']) }}
                            </div>
                        </div>

                        <div class="detail-summary__metric detail-summary__metric--count">
                            <div class="detail-summary__value text-primary">{{ number_format($stats['today_total_collections']) }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('collections.actual-daily') }}">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Actual Collection - Today</div>
                    <div class="detail-summary__split">
                        <div class="detail-summary__metric">
                            <div class="detail-summary__value text-success">{{ number_format($stats['today_paid']) }}</div>
                        </div>

                        <div class="detail-summary__metric detail-summary__metric--count">
                            <div class="detail-summary__value text-success">{{ number_format($stats['today_successful_collections']) }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="clearfix">
                        <div class="float-end">
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm" id="mandate-value-select-year">
                                    @php
                                        $year_options = [];
                                        $current_year = date('Y');
                                    @endphp

                                    <option value="">Select</option>
                                    @foreach (range(2024, $current_year) as $v)
                                        <option value="{{ $v }}"
                                            {{ (int) $post_data['year'] === (int) $v ? 'selected' : '' }}>
                                            {{ $v }}</option>
                                    @endforeach
                                </select>
                                <label class="input-group-text">Year</label>
                            </div>
                        </div>
                        <h4 class="card-title mb-3">Payment Mandate Values</h4>
                    </div>

                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-12">
                            <div id="mandate-value-chart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Mandate Approval Status</h4>
                    <div class="dashboard-pipeline">
                        @foreach ($mandate_pipeline as $item)
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
                            <div class="dashboard-pipeline__footer-metric">
                                <span>Total mandate value</span>
                                <strong>{{ format_short_amount($stats['total_mandate_value']) }} TZS</strong>
                            </div>
                            <div class="dashboard-pipeline__footer-metric">
                                <span>Highest mandate</span>
                                <strong>{{ format_short_amount($highest_single_mandate) }} TZS</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="clearfix">
                        <div class="float-end">
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm" id="collected-mandate-value-select-year">
                                    @php
                                        $year_options = [];
                                        $current_year = date('Y');
                                    @endphp

                                    <option value="">Select</option>
                                    @foreach (range(2024, $current_year + 5) as $v)
                                        <option value="{{ $v }}"
                                            {{ (int) $post_data['year'] === (int) $v ? 'selected' : '' }}>
                                            {{ $v }}</option>
                                    @endforeach
                                </select>
                                <label class="input-group-text">Year</label>
                            </div>
                        </div>
                        <h4 class="card-title mb-3">Expected vs Actual Collections</h4>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div id="collected-mandate-value-chart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--./col -->

        <div class="col-xl-4">
            <div class="card dashboard-report-panel">
                <div class="card-body">
                    <div class="dashboard-report-panel__head dashboard-weekly-head">
                        <div>
                            <h4 class="card-title">7-Day Collection Trend</h4>
                            <p class="dashboard-report-panel__hint" id="weekly-collection-period">
                                Seven completed collection days.
                            </p>
                        </div>
                        <div class="dashboard-weekly-date">
                            <label for="collected-mandate-value-weekly-date">Period ending</label>
                            <input type="date" name="end_at" class="form-control form-control-sm"
                                id="collected-mandate-value-weekly-date"
                                value="{{ now()->subDay()->toDateString() }}"
                                max="{{ now()->subDay()->toDateString() }}" />
                        </div>
                    </div>

                    <div class="dashboard-weekly-summary">
                        <div class="dashboard-weekly-summary__item">
                            <span>Collected</span>
                            <strong id="weekly-total-collected">0 TZS</strong>
                        </div>
                        <div class="dashboard-weekly-summary__item">
                            <span>Prior 7 days</span>
                            <strong id="weekly-previous-total">0 TZS</strong>
                        </div>
                        <div class="dashboard-weekly-summary__item">
                            <span>Change vs prior period</span>
                            <strong id="weekly-change">N/A</strong>
                        </div>
                    </div>

                    <div class="dashboard-weekly-chart-label">Daily collected amount</div>
                    <div id="collected-mandate-value-weekly-chart"></div>
                    <div class="dashboard-weekly-empty d-none" id="weekly-collection-empty">
                        No collections posted for this period.
                    </div>
                </div>
            </div>
        </div>
        <!--./col -->
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card dashboard-report-panel">
                <div class="card-body">
                    <div class="dashboard-report-panel__head">
                        <div>
                            <h4 class="card-title">Mandates Sent vs Successfully Deducted</h4>
                            <p class="dashboard-report-panel__hint">Unique mandates sent to the bank and successfully
                                deducted each month.</p>
                        </div>

                        <div>
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm" id="mandates-posted-vs-deducted-select-year">
                                    @php $current_year = date('Y'); @endphp
                                    <option value="">Select</option>
                                    @foreach (range(2024, $current_year + 5) as $v)
                                        <option value="{{ $v }}"
                                            {{ (int) $post_data['year'] === (int) $v ? 'selected' : '' }}>
                                            {{ $v }}</option>
                                    @endforeach
                                </select>
                                <label class="input-group-text">Year</label>
                            </div>
                        </div>
                    </div>
                    <div id="mandates-posted-vs-deducted-chart"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card dashboard-report-panel">
                <div class="card-body">
                    <div class="dashboard-report-panel__head">
                        <div>
                            <h4 class="card-title">Payment Channel Performance</h4>
                            <p class="dashboard-report-panel__hint">Current month collection value by channel.</p>
                        </div>
                    </div>

                    <div class="dashboard-channel-list">
                        @foreach ($bank_channel_performance as $channelItem)
                            <div class="dashboard-channel-item">
                                <div class="dashboard-channel-item__badge">
                                    @if (!empty($channelItem['logo']))
                                        <img src="{{ $channelItem['logo'] }}" alt="{{ $channelItem['name'] }}" class="dashboard-channel-item__logo">
                                    @else
                                        {{ $channelItem['slug'] }}
                                    @endif
                                </div>

                                <div class="dashboard-channel-item__content">
                                    <div class="dashboard-channel-item__head">
                                        <div>
                                            <div class="dashboard-channel-item__name">{{ $channelItem['name'] }}</div>
                                            <div class="dashboard-channel-item__meta">
                                                {{ number_format($channelItem['expected_count']) }} expected ·
                                                {{ number_format($channelItem['collection_count']) }} collected
                                            </div>
                                        </div>
                                        <div class="dashboard-channel-item__status channel-{{ $channelItem['tone'] }}">
                                            <span>{{ $channelItem['status'] }}</span>
                                            <small>Collection rate</small>
                                        </div>
                                    </div>

                                    <div class="dashboard-channel-item__amounts">
                                        <div>
                                            <span>Expected</span>
                                            <strong>{{ format_short_amount($channelItem['expected_amount']) }} TZS</strong>
                                        </div>
                                        <div>
                                            <span>Collected</span>
                                            <strong>{{ format_short_amount($channelItem['paid_amount']) }} TZS</strong>
                                        </div>
                                    </div>

                                    <div class="dashboard-channel-item__track">
                                        <div class="dashboard-channel-item__bar channel-{{ $channelItem['tone'] }}"
                                            style="width: {{ $channelItem['bar_width'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <!-- apexcharts -->
    <script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <script>
        window.DASHBOARD_FILTERS = @json($post_data);
        window.DASHBOARD_ROUTES = {
            paymentCollections: @json(route('payment-collections.index')),
        };
    </script>

    <!-- Saas dashboard init -->
    <script src="{{ URL::asset('/assets/js/charts/dashboard.js') }}?v={{ filemtime(public_path('assets/js/charts/dashboard.js')) }}"></script>
@endsection
