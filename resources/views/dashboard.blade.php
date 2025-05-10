@extends('layouts.master')

@section('title')
    @lang('translation.Dashboards')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            Summaries
        @endslot
    @endcomponent

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
                                <i class='bx bx-copy-alt fa-3x text-white'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('payment-mandate.lists', 'approved') }}">
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
                                <i class='bx bx-copy-alt fa-3x text-white'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('payment-mandate.lists', 'pending') }}">
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
                                <i class='bx bx-copy-alt fa-3x text-white'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('payment-mandate.lists', 'failed') }}">
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
                                <i class='bx bx-copy-alt fa-3x text-white'></i>
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
                    <h4 class="card-title mb-4">Payment Mandate Rates</h4>
                    <div>
                        <div id="consent-rate-chart" class="apex-charts"></div>
                    </div>
                    <div class="text-center text-muted">
                        <div class="row">
                            <div class="col-lg-4 col-md-4 col-4">
                                <div class="my-2">
                                    <p class="text-xs">
                                        <i class="mdi mdi-circle text-success-1 my-1"></i> Approved :
                                        {{ $stats['number_of_approved_mandate'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4 col-4">
                                <div class="my-2">
                                    <p class="text-xs">
                                        <i class="mdi mdi-circle text-danger my-1"></i> Pending :
                                        {{ $stats['number_of_pending_mandate'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4 col-4">
                                <div class="my-2">
                                    <p class="text-xs">
                                        <i class="mdi mdi-circle text-danger-1 my-1"></i> Failed :
                                        {{ $stats['number_of_failed_mandate'] }}
                                    </p>
                                </div>
                            </div>

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
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="clearfix">
                        <h4 class="card-title mb-4">{{ $collection_title }}</h4>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive  nowrap w-100" id="dynamicTable">
                            <thead>
                                <tr>
                                    <th width="3%">#</th>
                                    <th width="30%">Service Provider</th>
                                    <th width="12%">No. of Collection</th>
                                    <th width="14%">Expected Amount</th>
                                    <th width="14%">Collected Amount</th>
                                    <th width="14%">Outstanding Amount</th>
                                    <th width="12%">Success Rate(%)</th>
                                </tr>
                            </thead>

                            <tbody>
                                @php $serial = 1; @endphp
                                @foreach ($arr_data as $values)
                                    <tr>
                                        <td>{{ $serial }}</td>
                                        <td>
                                            <a href="{{ route('collections.actual-monthly-detail', $values['uuid']) }}?month={{ $post_data['month'] }}&year={{ $post_data['year'] }}"
                                                class="font-weight-600">
                                                {{ strtoupper($values['partner_name']) }}
                                            </a>
                                        </td>
                                        <td class="text-sm-end">{{ number_format($values['number_of_collection']) }}</td>
                                        <td class="text-sm-end">{{ number_format($values['total_amount']) }}</td>
                                        <td class="text-sm-end">{{ number_format($values['paid_amount']) }}</td>
                                        <td class="text-sm-end">{{ number_format($values['outstanding_amount']) }}</td>
                                        <td class="text-sm-end">{{ $values['percent'] }}</td>
                                    </tr>
                                    @php $serial++; @endphp
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!--./col -->
    </div>
@endsection

@section('script')
    <!-- apexcharts -->
    <script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <!-- Saas dashboard init -->
    <script src="{{ URL::asset('/assets/js/charts/dashboard.js') }}"></script>
@endsection
