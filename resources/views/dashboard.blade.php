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
            <a href="#">
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
            <a href="#">
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
            <a href="#">
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
            <a href="#">
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
                                    @foreach (range(2024, $current_year+5) as $v)
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
                                <input type="date" name="start_at" class="form-control" id="collected-mandate-value-weekly-date" /> 
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
            <div class="card">
                <div class="card-body">
                    <div class="clearfix">
                        <div class="float-end">
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm" id="select-month">
                                    <option value="">Select</option>
                                    @foreach (range(1, 12) as $v)
                                        @php
                                            $month_name = date('F', mktime(0, 0, 0, $v, 10)); // convert number to month
                                        @endphp
                                        <option value="{{ $v }}">{{ $month_name }}</option>
                                    @endforeach
                                </select>
                                <label class="input-group-text">Month</label>
                            </div>
                        </div>
                        <h4 class="card-title mb-4">Payment Mandate by Channel Distribution</h4>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div id="mandate-channel-chart" dir="ltr"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--./col -->

        <div class="col-xl-4">
        </div>
        <!--./col -->

    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title text-uppercase mb-4">Latest Payment Mandate</h4>

                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#pending" role="tab">
                                <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                                <span class="d-none d-sm-block">Pending</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#approved" role="tab">
                                <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                                <span class="d-none d-sm-block">Approved</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#rejected" role="tab">
                                <span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
                                <span class="d-none d-sm-block">Failed</span>
                            </a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content p-3 text-muted">
                        <div class="tab-pane active" id="pending" role="tabpanel">
                            <div class="table-responsive">
                                @if (isset($pending_debit_orders) && $pending_debit_orders)
                                    <table id="dt" class="table table-hover table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="3%">#</th>
                                                <th width="20%">Account Name</th>
                                                <th>Bank Name/MNO</th>
                                                <th>Account Number</th>
                                                <th>Mandate Reference</th>
                                                <th>Primary Phone</th>
                                                <th>Mandate Status</th>
                                                <th>Created Date</th>
                                                <th width="4%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $serial = 1; @endphp
                                            @foreach ($pending_debit_orders as $values)
                                                <tr>
                                                    <td>{{ $serial }}</td>
                                                <td>
                                                    {{ strtoupper($values->customerAccount->account_name) ?? $values->customerAccount->phone }}<br>
                                                    ID: {{ $values->customerAccount->customer->id_number ?? 'NIL' }}
                                                </td>
                                                <td>{{ $values->customerAccount->account->name ?? '' }}</td>
                                                <td>
                                                    {{ $values->customerAccount->account_number ?? $values->customerAccount->phone }}
                                                </td>
                                                <td>
                                                    {{ $values->reference }}
                                                </td>
                                                <td>{{ $values->customerAccount->phone ?? '' }}</td>

                                                <td>
                                                    @if ($values->status == 100)
                                                        <span class="badge badge-pill badge-soft-danger">Pending Registration</span>
                                                    @elseif($values->status == 200)
                                                        <span class="badge badge-pill badge-soft-info">Under Review</span>
                                                    @elseif($values->status == 201)
                                                        <span class="badge badge-pill badge-soft-info">Pending Verification</span>
                                                    @elseif($values->status == 202)
                                                        <span class="badge badge-pill bg-success">Verified</span>
                                                    @elseif($values->status == 240)
                                                        <span class="badge badge-pill bg-success">Active</span>
                                                    @elseif($values->status == 249)
                                                        <span class="badge badge-pill badge-soft-warning">Processing</span>
                                                    @elseif($values->status == 403) 
                                                        <span class="badge badge-pill badge-soft-danger">Failed</span>
                                                    @elseif($values->status == 405) 
                                                        <span class="badge badge-pill badge-soft-danger">Cancelled</span>  
                                                    @elseif($values->status == 500 || $values->status == 430)
                                                        <span class="badge badge-pill badge-soft-danger">Failed Verification</span>
                                                    @elseif($values->status == 501)
                                                        <span class="badge badge-pill badge-soft-danger">Inactive</span>
                                                    @endif
                                                </td>

                                                <td>
                                                    {{ date('d-m-Y H:i', strtotime($values->updated_at)) }}
                                                </td>

                                                    <td>
                                                        <a href="{{ route('payment-mandate.show', $values->uuid) }}"
                                                            title="View" class="btn btn-xss">
                                                            <i class="bx bxs-folder-open"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                @php $serial++; @endphp
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <div class="alert alert-danger"> No any pending payment mandate</div>
                                @endif
                            </div>
                        </div>


                        <div class="tab-pane" id="approved" role="tabpanel">
                            <div class="table-responsive">
                                @if (isset($approved_debit_orders) && $approved_debit_orders)
                                    <table id="dt" class="table table-hover table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="3%">#</th>
                                                <th width="20%">Account Name</th>
                                                <th>Bank Name/MNO</th>
                                                <th>Account Number</th>
                                                <th>Mandate Reference</th>
                                                <th>Primary Phone</th>
                                                <th>Mandate Status</th>
                                                <th>Created Date</th>
                                                <th width="4%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $serial = 1; @endphp
                                            @foreach ($approved_debit_orders as $values)
                                                <tr>
                                                    <td>{{ $serial }}</td>
                                                    <td>
                                                        {{ strtoupper($values->customerAccount->account_name) ?? $values->customerAccount->phone }}<br>
                                                        ID: {{ $values->customerAccount->customer->id_number ?? 'NIL' }}
                                                    </td>
                                                    <td>{{ $values->customerAccount->account->name ?? '' }}</td>
                                                    <td>
                                                        {{ $values->customerAccount->account_number ?? $values->customerAccount->phone }}
                                                    </td>
                                                    <td>
                                                        {{ $values->reference }}
                                                    </td>
                                                    <td>{{ $values->customerAccount->phone ?? '' }}</td>

                                                    <td>
                                                        @if ($values->status == 100)
                                                            <span class="badge badge-pill badge-soft-danger">Pending Registration</span>
                                                        @elseif($values->status == 200)
                                                            <span class="badge badge-pill badge-soft-info">Under Review</span>
                                                        @elseif($values->status == 201)
                                                            <span class="badge badge-pill badge-soft-info">Pending Verification</span>
                                                        @elseif($values->status == 202)
                                                            <span class="badge badge-pill bg-success">Verified</span>
                                                        @elseif($values->status == 240)
                                                            <span class="badge badge-pill bg-success">Active</span>
                                                        @elseif($values->status == 249)
                                                            <span class="badge badge-pill badge-soft-warning">Processing</span>
                                                        @elseif($values->status == 403) 
                                                            <span class="badge badge-pill badge-soft-danger">Failed</span>
                                                        @elseif($values->status == 405) 
                                                            <span class="badge badge-pill badge-soft-danger">Cancelled</span>  
                                                        @elseif($values->status == 500 || $values->status == 430)
                                                            <span class="badge badge-pill badge-soft-danger">Failed Verification</span>
                                                        @elseif($values->status == 501)
                                                            <span class="badge badge-pill badge-soft-danger">Inactive</span>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        {{ date('d-m-Y H:i', strtotime($values->updated_at)) }}
                                                    </td>

                                                    <td>
                                                        <a href="{{ route('payment-mandate.show', $values->uuid) }}"
                                                            title="View" class="btn btn-xss">
                                                            <i class="bx bxs-folder-open"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                @php $serial++; @endphp
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <div class="alert alert-danger"> No any approved payment mandate</div>
                                @endif
                            </div>
                        </div>

                        <div class="tab-pane" id="rejected" role="tabpanel">
                            <div class="table-responsive">
                                @if (isset($failed_debit_orders) && $failed_debit_orders)
                                    <table id="dt" class="table table-hover table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="3%">#</th>
                                                <th width="20%">Account Name</th>
                                                <th>Bank Name/MNO</th>
                                                <th>Account Number</th>
                                                <th>Mandate Reference</th>
                                                <th>Primary Phone</th>
                                                <th>Mandate Status</th>
                                                <th>Created Date</th>
                                                <th width="4%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $serial = 1; @endphp
                                            @foreach ($failed_debit_orders as $values)
                                                <tr>
                                                    <td>{{ $serial }}</td>
                                                    <td>
                                                        {{ strtoupper($values->customerAccount->account_name) ?? $values->customerAccount->phone }}<br>
                                                        ID: {{ $values->customerAccount->customer->id_number ?? 'NIL' }}
                                                    </td>
                                                    <td>{{ $values->customerAccount->account->name ?? '' }}</td>
                                                    <td>
                                                        {{ $values->customerAccount->account_number ?? $values->customerAccount->phone }}
                                                    </td>
                                                    <td>
                                                        {{ $values->reference }}
                                                    </td>
                                                    <td>{{ $values->customerAccount->phone ?? '' }}</td>

                                                    <td>
                                                        @if ($values->status == 100)
                                                            <span class="badge badge-pill badge-soft-danger">Pending Registration</span>
                                                        @elseif($values->status == 200)
                                                            <span class="badge badge-pill badge-soft-info">Under Review</span>
                                                        @elseif($values->status == 201)
                                                            <span class="badge badge-pill badge-soft-info">Pending Verification</span>
                                                        @elseif($values->status == 202)
                                                            <span class="badge badge-pill bg-success">Verified</span>
                                                        @elseif($values->status == 240)
                                                            <span class="badge badge-pill bg-success">Active</span>
                                                        @elseif($values->status == 249)
                                                            <span class="badge badge-pill badge-soft-warning">Processing</span>
                                                        @elseif($values->status == 403) 
                                                            <span class="badge badge-pill badge-soft-danger">Failed</span>
                                                        @elseif($values->status == 405) 
                                                            <span class="badge badge-pill badge-soft-danger">Cancelled</span>  
                                                        @elseif($values->status == 500 || $values->status == 430)
                                                            <span class="badge badge-pill badge-soft-danger">Failed Verification</span>
                                                        @elseif($values->status == 501)
                                                            <span class="badge badge-pill badge-soft-danger">Inactive</span>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        {{ date('d-m-Y H:i', strtotime($values->updated_at)) }}
                                                    </td>

                                                    <td>
                                                        <a href="{{ route('payment-mandate.show', $values->uuid) }}"
                                                            title="View" class="btn btn-xss">
                                                            <i class="bx bxs-folder-open"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                @php $serial++; @endphp
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <div class="alert alert-danger"> No any rejected payment mandate</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <!-- apexcharts -->
    <script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <!-- Saas dashboard init -->
    <script src="{{ URL::asset('/assets/js/charts/dashboard.js') }}"></script>
@endsection
