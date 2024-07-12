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
                                    {{ number_format($total[0]->total_amount) }}
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
        <!--./col -->

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
                                    {{ number_format($pending[0]->total_amount) }}
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
        <!--./col -->

        <div class="col-md-3">
            <a href="{{ route('payment-mandate.lists', 'rejected') }}">
                <div class="card border-left-info bg-danger shadow h-96 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xss font-weight-600 text-white mb-1">
                                    Rejected Mandate
                                </div>

                                <div class="h5 mb-0 font-weight-700 text-white">
                                    {{ number_format($rejected[0]->total_amount) }}
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
        <!--./col -->

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
                                    {{ number_format($approved[0]->total_amount) }}
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
        <!--./col -->
    </div>
    <!--./row -->

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="clearfix">
                        <h4 class="card-title mb-4">Payment Mandate Values</h4>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div id="consent-value-chart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--./col -->

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Payment Mandate Rates</h4>
                    <div>
                        <div id="consent-rate-chart" class="apex-charts"></div>
                    </div>
                    <div class="text-center text-muted">
                        <div class="row">
                            <div class="col-6">
                                <div class="mt-2">
                                    <p class="">
                                        <i class="mdi mdi-circle text-success-1 my-1"></i> Approved : {{ $approved[0]->count }}
                                    </p>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mt-2">
                                    <p class="">
                                        <i class="mdi mdi-circle text-danger my-1"></i> Pending : {{ $pending[0]->count }}
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
    <!-- end row -->

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="clearfix">
                        <div class="float-end">
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm" id="select-month">
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
                            <div id="consent-channel-chart" dir="ltr"></div>
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
    <!-- end row -->

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
                                <span class="d-none d-sm-block">Rejected</span>
                            </a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content p-3 text-muted">
                        <div class="tab-pane active" id="pending" role="tabpanel">
                            <div class="table-responsive">
                                @if (isset($pending_debit_orders) && count($pending_debit_orders) > 0)
                                    <table id="dt" class="table table-hover table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="3%">#</th>
                                                <th width="20%">Account Name</th>
                                                <th width="20%">NIDA Number</th>
                                                <th width="10%">Payment Channel</th>
                                                <th width="10%">Bank Name/MNO</th>
                                                <th width="12%">Account Number</th>
                                                <th width="12%">Phone<br /><small>(Connected to Bank)</small></th>
                                                <th width="4%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $serial = 1; @endphp
                                            @foreach ($pending_debit_orders as $values)
                                                <tr>
                                                    <td>{{ $serial }}</td>
                                                    <td>{{ $values->customerAccount->account_name ?? $values->customerAccount->phone }}
                                                    </td>
                                                    <td><small>{{ $values->customerAccount->customer->id_number ?? 'NIL' }}</small>
                                                    </td>
                                                    <td>{{ $values->customerAccount->accountType->name ?? '' }}</td>
                                                    <td>{{ $values->customerAccount->account->name ?? '' }}</td>
                                                    <td>
                                                        {{ $values->customerAccount->account_number ?? $values->customerAccount->phone }}
                                                    </td>
                                                    <td>{{ $values->customerAccount->phone ?? $values->customerAccount->phone }}</td>

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
                            <!--./table-responsive -->
                        </div>
                        <!--./tab-pane -->

                        <div class="tab-pane" id="approved" role="tabpanel">
                            <div class="table-responsive">
                                @if (isset($approved_debit_orders) && count($approved_debit_orders) > 0)
                                    <table id="dt" class="table table-hover table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="3%">#</th>
                                                <th width="20%">Account Name</th>
                                                <th width="16%">NIDA Number</th>
                                                <th width="10%">Payment Channel</th>
                                                <th width="10%">Bank Name/MNO</th>
                                                <th width="12%">Account Number</th>
                                                <th width="12%">Phone<br /><small>(Connected to Bank)</small></th>
                                                <th width="8%">Consent Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $serial = 1; @endphp
                                            @foreach ($approved_debit_orders as $values)
                                                <tr>
                                                    <td>{{ $serial }}</td>
                                                    <td>{{ $values->customerAccount->account_name ?? $values->customerAccount->phone }}
                                                    </td>
                                                    <td><small>{{ $values->customerAccount->customer->id_number ?? 'NIL' }}</small>
                                                    </td>
                                                    <td>{{ $values->customerAccount->accountType->name ?? '' }}</td>
                                                    <td>{{ $values->customerAccount->account->name ?? '' }}</td>
                                                    <td>
                                                        {{ $values->customerAccount->account_number ?? $values->customerAccount->phone }}
                                                    </td>
                                                    <td>{{ $values->customerAccount->phone ?? $values->customerAccount->phone }}</td>

                                                    <td>
                                                        @if ($values->approved == "PENDING")
                                                            <span
                                                                class="badge badge-pill badge-soft-warning">Pending</span>
                                                        @elseif($values->approved == "APPROVED")
                                                            <span
                                                                class="badge badge-pill badge-soft-success">Approved</span>
                                                        @elseif($values->approved == "REJECTED")
                                                            <span
                                                                class="badge badge-pill badge-soft-danger">Rejected</span>
                                                        @endif
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
                            <!--./table-responsive -->
                        </div>
                        <!--./tab-pane -->

                        <div class="tab-pane" id="rejected" role="tabpanel">
                            <div class="table-responsive">
                                @if (isset($rejected_debit_orders) && count($rejected_debit_orders) > 0)
                                    <table id="dt" class="table table-hover table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="3%">#</th>
                                                <th width="20%">Account Name</th>
                                                <th width="16%">NIDA Number</th>
                                                <th width="10%">Payment Channel</th>
                                                <th width="10%">Bank Name/MNO</th>
                                                <th width="12%">Account Number</th>
                                                <th width="12%">Phone<br /><small>(Connected to Bank)</small></th>
                                                <th width="8%">Consent Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $serial = 1; @endphp
                                            @foreach ($rejected_debit_orders as $values)
                                                <tr>
                                                    <td>{{ $serial }}</td>
                                                    <td>
                                                        {{ $values->customerAccount->account_name ?? $values->customerAccount->phone }}
                                                    </td>
                                                    <td><small>{{ $values->customerAccount->customer->id_number ?? 'NIL' }}</small>
                                                    </td>
                                                    <td>{{ $values->customerAccount->accountType->name ?? '' }}</td>
                                                    <td>{{ $values->customerAccount->account->name ?? '' }}</td>
                                                    <td>
                                                        {{ $values->customerAccount->account_number ?? $values->customerAccount->phone }}
                                                    </td>
                                                    <td>{{ $values->customerAccount->phone ?? $values->customerAccount->phone }}</td>

                                                    <td>
                                                        @if ($values->approved == "PENDING")
                                                            <span
                                                                class="badge badge-pill badge-soft-warning">Pending</span>
                                                        @elseif($values->approved == "APPROVED")
                                                            <span
                                                                class="badge badge-pill badge-soft-success">Approved</span>
                                                        @elseif($values->approved == "REJECTED")
                                                            <span
                                                                class="badge badge-pill badge-soft-danger">Rejected</span>
                                                        @endif
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
                            <!--./table-responsive -->
                        </div>
                        <!--./tab-pane -->
                    </div>
                    <!--./tab-content -->
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->
@endsection

@section('script')
    <!-- apexcharts -->
    <script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <!-- Saas dashboard init -->
    <script src="{{ URL::asset('/assets/js/charts/dashboard.js') }}"></script>
@endsection
