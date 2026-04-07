@extends('layouts.master')

@section('title')
    @lang('translation.Dashboards')
@endsection

@section('css')
    <style>
        .dashboard-report-card {
            border: 1px solid #dce6f5;
            border-radius: 16px;
            box-shadow: 0 14px 30px rgba(21, 53, 103, 0.08);
            overflow: hidden;
        }

        .dashboard-report-card__body {
            padding: 1.1rem 1rem;
            border-top: 3px solid transparent;
        }

        .dashboard-report-card__body.report-primary {
            border-top-color: #1565C0;
        }

        .dashboard-report-card__body.report-success {
            border-top-color: #2E7D32;
        }

        .dashboard-report-card__body.report-warning {
            border-top-color: #F9A825;
        }

        .dashboard-report-card__body.report-danger {
            border-top-color: #C62828;
        }

        .dashboard-report-card__label {
            color: #7b8798;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            margin-bottom: 0.45rem;
            text-transform: uppercase;
        }

        .dashboard-report-card__value {
            color: #153567;
            font-size: 1.7rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 0.45rem;
        }

        .dashboard-report-card__meta {
            color: #6b7c93;
            font-size: 0.84rem;
            margin-bottom: 0;
        }

        .dashboard-report-panel {
            border: 1px solid #dce6f5;
            border-radius: 18px;
            box-shadow: 0 16px 40px rgba(21, 53, 103, 0.08);
        }

        .dashboard-report-panel .card-body {
            padding: 1.2rem;
        }

        .dashboard-report-panel__head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .dashboard-report-panel__title {
            color: #153567;
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.15rem;
        }

        .dashboard-report-panel__hint {
            color: #6b7c93;
            margin-bottom: 0;
            font-size: 0.88rem;
        }

        .dashboard-inline-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #edf4ff;
            color: #153567;
            border-radius: 999px;
            padding: 0.45rem 0.8rem;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .dashboard-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .dashboard-dot--collected {
            background: #2E7D32;
        }

        .dashboard-dot--gap {
            background: #F8BBD0;
        }

        .dashboard-pipeline__item + .dashboard-pipeline__item {
            margin-top: 2.4rem;
        }

        .dashboard-pipeline__row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 0.75rem;
            margin-bottom: 0.35rem;
        }

        .dashboard-pipeline__label {
            font-size: 0.9rem;
            font-weight: 700;
            color: #424242;
        }

        .dashboard-pipeline__value {
            font-size: 0.88rem;
            font-weight: 700;
        }

        .dashboard-pipeline__value.pipeline-success {
            color: #2E7D32;
        }

        .dashboard-pipeline__value.pipeline-primary {
            color: #1565C0;
        }

        .dashboard-pipeline__value.pipeline-warning {
            color: #C49000;
        }

        .dashboard-pipeline__value.pipeline-danger {
            color: #C62828;
        }

        .dashboard-pipeline__track {
            height: 8px;
            background: #edf1f7;
            border-radius: 999px;
            overflow: hidden;
        }

        .dashboard-pipeline__bar {
            height: 100%;
            border-radius: 999px;
        }

        .dashboard-pipeline__bar.pipeline-success {
            background: #2E7D32;
        }

        .dashboard-pipeline__bar.pipeline-primary {
            background: #1565C0;
        }

        .dashboard-pipeline__bar.pipeline-warning {
            background: #F9A825;
        }

        .dashboard-pipeline__bar.pipeline-danger {
            background: #C62828;
        }

        .dashboard-pipeline__footer {
            margin-top: 1.1rem;
            padding-top: 0.9rem;
            border-top: 1px solid #e7edf6;
            color: #6b7c93;
            font-size: 0.84rem;
        }

        .dashboard-channel-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .dashboard-channel-item {
            display: grid;
            grid-template-columns: 52px 1fr auto;
            gap: 0.85rem;
            align-items: center;
        }

        .dashboard-channel-item__badge {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            font-weight: 800;
            color: #3f4a5c;
            background: #f4f7fc;
            border: 1px solid #e5ebf5;
        }

        .dashboard-channel-item__name {
            color: #243b5a;
            font-size: 0.92rem;
            font-weight: 700;
            margin-bottom: 0.12rem;
        }

        .dashboard-channel-item__meta {
            color: #7b8798;
            font-size: 0.78rem;
            margin-bottom: 0.45rem;
        }

        .dashboard-channel-item__track {
            height: 8px;
            background: #edf1f7;
            border-radius: 999px;
            overflow: hidden;
        }

        .dashboard-channel-item__bar {
            height: 100%;
            border-radius: 999px;
        }

        .dashboard-channel-item__bar.channel-success {
            background: #2E7D32;
        }

        .dashboard-channel-item__bar.channel-warning {
            background: #C49000;
        }

        .dashboard-channel-item__bar.channel-danger {
            background: #C62828;
        }

        .dashboard-channel-item__bar.channel-muted {
            background: #cfd8e6;
        }

        .dashboard-channel-item__status {
            min-width: 52px;
            text-align: right;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .dashboard-channel-item__status.channel-success {
            color: #2E7D32;
        }

        .dashboard-channel-item__status.channel-warning {
            color: #C49000;
        }

        .dashboard-channel-item__status.channel-danger {
            color: #C62828;
        }

        .dashboard-channel-item__status.channel-muted {
            color: #7b8798;
        }
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Summaries : {{ request('channel') ?? 'All Channels'}}</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Summaries</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <a href="{{ route('payment-mandate.lists') }}">
                <div class="card border-left-info bg-primary shadow h-96 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xss font-weight-600 text-white mb-1">
                                    Payment Mandate
                                </div>

                                <div class="h5 mb-0 font-weight-700 text-white">
                                    {{ number_format($stats['total_mandate_value']) }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <div class="h5 mb-0 font-weight-700 text-white">
                                        {{ number_format($stats['total_number_of_mandate']) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('payment-mandate.lists', ['status' => 'approved']) }}">
                <div class="card border-left-info bg-success-1 shadow h-96 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xss font-weight-600 text-white mb-1">
                                    Approved Mandate
                                </div>

                                <div class="h5 mb-0 font-weight-700 text-white">
                                    {{ number_format($stats['approved_mandate_value']) }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <div class="h5 mb-0 font-weight-700 text-white">
                                        {{ number_format($stats['number_of_approved_mandate']) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('payment-mandate.lists', ['status' => 'pending']) }}">
                <div class="card border-left-info bg-danger-1 shadow h-96 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xss font-weight-600 text-white mb-1">
                                    Pending Mandate
                                </div>

                                <div class="h5 mb-0 font-weight-700 text-white">
                                    {{ number_format($stats['pending_mandate_value']) }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <div class="h5 mb-0 font-weight-700 text-white">
                                        {{ number_format($stats['number_of_pending_mandate']) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('payment-mandate.lists', ['status' => 'failed']) }}">
                <div class="card border-left-info bg-danger shadow h-96 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xss font-weight-600 text-white mb-1">
                                    Failed Mandate
                                </div>

                                <div class="h5 mb-0 font-weight-700 text-white">
                                    {{ number_format($stats['failed_mandate_value']) }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <div class="h5 mb-0 font-weight-700 text-white">
                                        {{ number_format($stats['number_of_failed_mandate']) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <a href="{{ route('collections.expected-monthly') }}">
                <div class="card border-left-info bg-primary shadow h-96 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xss font-weight-600 text-white mb-1">
                                    Expected Collection ({{ $stats['month'] }})
                                </div>

                                <div class="h5 mb-0 font-weight-700 text-white">
                                    {{ number_format($stats['this_month_expected']) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('collections.actual-monthly') }}">
                <div class="card border-left-info bg-success-1 shadow h-96 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xss font-weight-600 text-white mb-1">
                                    Actual Collection ({{ $stats['month'] }})
                                </div>

                                <div class="h5 mb-0 font-weight-700 text-white">
                                    {{ number_format($stats['this_month_paid']) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('collections.expected-daily') }}">
                <div class="card border-left-info bg-primary shadow h-96 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xss font-weight-600 text-white mb-1">
                                    Expected Collection (Today)
                                </div>

                                <div class="h5 mb-0 font-weight-700 text-white">
                                    {{ number_format($stats['today_expected']) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('collections.actual-daily') }}">
                <div class="card border-left-info bg-success-1 shadow h-96 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xss font-weight-600 text-white mb-1">
                                    Actual Collection (Today)
                                </div>

                                <div class="h5 mb-0 font-weight-700 text-white">
                                    {{ number_format($stats['today_paid']) }}
                                </div>
                            </div>
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
                                        <option value="{{ $v }}">{{ $v }}</option>
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
                                        {{ format_short_amount($item['amount']) }} TZS · {{ number_format($item['percent'], 1) }}%
                                    </span>
                                </div>
                                <div class="dashboard-pipeline__track">
                                    <div class="dashboard-pipeline__bar pipeline-{{ $item['tone'] }}" style="width: {{ min(max($item['percent'], 0), 100) }}%"></div>
                                </div>
                            </div>
                        @endforeach

                        <div class="dashboard-pipeline__footer">
                            Total mandate value: <strong>{{ format_short_amount($stats['total_mandate_value']) }} TZS</strong><br>
                            Highest single: <strong>{{ format_short_amount($highest_single_mandate) }} TZS</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--./col -->
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
                                        <option value="{{ $v }}">{{ $v }}</option>
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
                            <p class="dashboard-report-panel__hint">Collected mandates against the remaining gap for each month.</p>
                        </div>

                        <div>
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm" id="mandates-posted-vs-deducted-select-year">
                                    @php $current_year = date('Y'); @endphp
                                    <option value="">Select</option>
                                    @foreach (range(2024, $current_year + 5) as $v)
                                        <option value="{{ $v }}" {{ (int) $post_data['year'] === (int) $v ? 'selected' : '' }}>{{ $v }}</option>
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
                            <h4 class="card-title">Bank &amp; Channel Performance</h4>
                            <p class="dashboard-report-panel__hint">Current month collection success by payment channel.</p>
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
