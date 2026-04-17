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
                <h4 class="mb-sm-0 font-size-18">Summaries : {{ request('channel') ?? 'All Channels' }}</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Summaries</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3">
            <a href="{{ route('payment-mandate.lists') }}">
                <div class="detail-summary border-top-primary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Payment Mandate</div>
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
            <a href="{{ route('payment-mandate.lists', ['status' => 'approved']) }}">
                <div class="detail-summary border-top-success shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Approved Mandate</div>
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
            <a href="{{ route('payment-mandate.lists', ['status' => 'pending']) }}">
                <div class="detail-summary border-top-warning shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Pending Mandate</div>
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
            <a href="{{ route('payment-mandate.lists', ['status' => 'failed']) }}">
                <div class="detail-summary border-top-danger shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Failed Mandate</div>
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
                <div class="detail-summary border-top-primary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Expected Collection - {{ $stats['month'] }}</div>
                    <div class="detail-summary__split">
                        <div class="detail-summary__metric">
                            <div class="detail-summary__value text-primary">
                                {{ number_format($stats['this_month_expected']) }}</div>
                        </div>
                        <div class="detail-summary__metric detail-summary__metric--count">
                            <div class="detail-summary__value text-primary">0</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('collections.actual-monthly') }}">
                <div class="detail-summary border-top-success shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Actual Collection - {{ $stats['month'] }}</div>
                    <div class="detail-summary__split">
                        <div class="detail-summary__metric">
                            <div class="detail-summary__value text-success">{{ number_format($stats['this_month_paid']) }}
                            </div>
                        </div>
                        <div class="detail-summary__metric detail-summary__metric--count">
                            <div class="detail-summary__value text-success">0</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('collections.expected-daily') }}">
                <div class="detail-summary border-top-primary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Expected Collection - Today</div>
                    <div class="detail-summary__split">
                        <div class="detail-summary__metric">
                            <div class="detail-summary__value text-primary">{{ number_format($stats['today_expected']) }}
                            </div>
                        </div>

                        <div class="detail-summary__metric detail-summary__metric--count">
                            <div class="detail-summary__value text-primary">0</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('collections.actual-daily') }}">
                <div class="detail-summary border-top-success shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Actual Collection - Today</div>
                    <div class="detail-summary__split">
                        <div class="detail-summary__metric">
                            <div class="detail-summary__value text-primary">{{ number_format($stats['today_paid']) }}</div>
                        </div>

                        <div class="detail-summary__metric detail-summary__metric--count">
                            <div class="detail-summary__value text-primary">0</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3">
            <div class="detail-summary border-top-primary shadow h-96 py-2">
                <div class="detail-summary__label text-muted">Total Subscribers</div>
                <div class="detail-summary__split">
                    <div class="detail-summary__metric">
                        <div class="detail-summary__value text-primary">{{ number_format($service_provider_count) }}</div>
                    </div>
                    <div class="detail-summary__metric detail-summary__metric--count">
                        <div class="detail-summary__value text-success">{{ number_format($active_service_provider_count) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="detail-summary border-top-success shadow h-96 py-2">
                <div class="detail-summary__label text-muted">Amount Collected</div>
                <div class="detail-summary__split">
                    <div class="detail-summary__metric">
                        <div class="detail-summary__value text-success">
                            {{ format_short_amount($year_summary->paid_amount ?? 0) }}</div>
                    </div>
                    <div class="detail-summary__metric detail-summary__metric--count">
                        <div class="detail-summary__value text-success">0</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="detail-summary border-top-primary shadow h-96 py-2">
                <div class="detail-summary__label text-muted"> Avg Collection</div>
                <div class="detail-summary__split">
                    <div class="detail-summary__metric">
                        <div class="detail-summary__value text-success">{{ number_format($year_success_rate, 2) }}%</div>
                    </div>
                    <div class="detail-summary__metric detail-summary__metric--count">
                        <div class="detail-summary__value text-success">0</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="detail-summary border-top-success shadow h-96 py-2">
                <div class="detail-summary__label text-muted">Revenue</div>
                <div class="detail-summary__split">
                    <div class="detail-summary__metric">
                        <div class="detail-summary__value text-success">0</div>
                    </div>
                    <div class="detail-summary__metric detail-summary__metric--count">
                        <div class="detail-summary__value text-success">0</div>
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
                    <h4 class="card-title mb-4">Mandate Rates</h4>
                    <div class="dashboard-pipeline">
                        @foreach ($mandate_pipeline as $item)
                            <div class="dashboard-pipeline__item">
                                <div class="dashboard-pipeline__row">
                                    <span class="dashboard-pipeline__label">{{ $item['label'] }}</span>
                                    <span class="dashboard-pipeline__value pipeline-{{ $item['tone'] }}">
                                        {{ format_short_amount($item['amount']) }} TZS ·
                                        {{ number_format($item['percent'], 1) }}%
                                    </span>
                                </div>
                                <div class="dashboard-pipeline__track">
                                    <div class="dashboard-pipeline__bar pipeline-{{ $item['tone'] }}"
                                        style="width: {{ min(max($item['percent'], 0), 100) }}%"></div>
                                </div>
                            </div>
                        @endforeach

                        <div class="dashboard-pipeline__footer">
                            Total mandate value: <strong>{{ format_short_amount($stats['total_mandate_value']) }}
                                TZS</strong><br>
                            Highest single: <strong>{{ format_short_amount($highest_single_mandate) }} TZS</strong>
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
                        <h4 class="card-title mb-3">Expected vs Collected</h4>
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
            <div class="card">
                <div class="card-body">
                    <div class="clearfix">
                        <div class="float-end">
                            <div class="input-group input-group-sm">
                                <input type="date" name="start_at" class="form-control"
                                    id="collected-mandate-value-weekly-date" />
                            </div>
                        </div>
                        <h4 class="card-title mb-3">Weekly Collections</h4>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div id="collected-mandate-value-weekly-chart"></div>
                        </div>
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
                            <h4 class="card-title">Mandates Posted vs Deducted</h4>
                            <p class="dashboard-report-panel__hint">Collected mandates against the remaining gap for
                                each month.</p>
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
                            <p class="dashboard-report-panel__hint">Current month collection success by payment
                                channel.</p>
                        </div>
                    </div>

                    <div class="dashboard-channel-list">
                        @foreach ($bank_channel_performance as $channelItem)
                            <div class="dashboard-channel-item">
                                <div class="dashboard-channel-item__badge">{{ $channelItem['slug'] }}</div>

                                <div>
                                    <div class="dashboard-channel-item__name">{{ $channelItem['name'] }}</div>
                                    <div class="dashboard-channel-item__meta">{{ $channelItem['subtitle'] }}</div>
                                    <div class="dashboard-channel-item__track">
                                        <div class="dashboard-channel-item__bar channel-{{ $channelItem['tone'] }}"
                                            style="width: {{ $channelItem['bar_width'] }}%"></div>
                                    </div>
                                </div>

                                <div class="dashboard-channel-item__status channel-{{ $channelItem['tone'] }}">
                                    {{ $channelItem['status'] }}
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
    </script>

    <!-- Saas dashboard init -->
    <script src="{{ URL::asset('/assets/js/charts/dashboard.js') }}"></script>
@endsection
