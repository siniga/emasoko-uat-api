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
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div>
                        <h5>{{ ucfirst(Auth::user()->name) }}</h5>
                        <p class="text-muted mb-1">{{ Auth::user()->email }}</p>
                        <p class="text-muted mb-0">{{ Auth::user()->phone ?? 'NIL' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="row">
                <div class="col-md-4">
                    <div class="card mini-stats-wid">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="text-muted fw-medium">Pending Mandate</p>
                                    <h4 class="mb-2">{{ $pending_debit_orders }}</h4>
                                </div>

                                <div class="flex-shrink-0 align-self-center">
                                    <div class="mini-stat-icon avatar-sm rounded-circle bg-danger">
                                        <span class="avatar-title">
                                            <i class="bx bx-copy-alt font-size-24"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--./col -->

                <div class="col-md-4">
                    <div class="card mini-stats-wid">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="text-muted fw-medium">Approved Mandate</p>
                                    <h4 class="mb-2">{{ $approved_debit_orders }}</h4>
                                </div>

                                <div class="flex-shrink-0 align-self-center">
                                    <div class="mini-stat-icon avatar-sm rounded-circle bg-success">
                                        <span class="avatar-title">
                                            <i class="bx bx-copy-alt font-size-24"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--./col -->

                <div class="col-md-4">
                    <div class="card mini-stats-wid">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="text-muted fw-medium">Rejected Mandate</p>
                                    <h4 class="mb-2">{{ $rejected_debit_orders }}</h4>
                                </div>

                                <div class="flex-shrink-0 align-self-center">
                                    <div class="mini-stat-icon avatar-sm rounded-circle bg-danger">
                                        <span class="avatar-title">
                                            <i class="bx bx-copy-alt font-size-24"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--./col -->
            </div>
            <!--./row -->
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
                            <div id="bar-chart" class="apex-charts" dir="ltr"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--./col -->

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Value Payment Mandate Percentage</h4>
                    <div>
                        <div id="pending-approve-chart" class="apex-charts"></div>
                    </div>

                    <div class="text-center text-muted">
                        <div class="row">
                            <div class="col-4">
                                <div class="mt-4">
                                    <p class="mb-2 text-truncate">
                                        <i class="mdi mdi-circle text-danger me-1"></i> Pending
                                    </p>
                                    <h5>{{ $pending_debit_orders }}</h5>
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="mt-4">
                                    <p class="mb-2 text-truncate"><i class="mdi mdi-circle text-success me-1"></i> Approved
                                    </p>
                                    <h5>{{ $approved_debit_orders }}</h5>
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
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title text-uppercase mb-4">Latest Pending Mandate</h4>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 20px;">#</th>
                                    <th width="18%">Account Name</th>
                                    <th width="16%">NIDA Number</th>
                                    <th width="8%">Payment Channel</th>
                                    <th width="10%">Bank Name/MNO</th>
                                    <th width="12%">Account Number</th>
                                    <th width="12%">Phone<br /><small>(Connected to Bank)</small></th>
                                    <th width="8%">Consent Status</th>
                                    <th class="align-middle">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $serial = 1; @endphp
                                @foreach ($debit_orders as $values)
                                    <tr>
                                        <td>{{ $serial }}</td>
                                        <td>{{ strtoupper($values->customerAccount->account_name ?? '') }}</td>
                                        <td><small>{{ $values->customerAccount->id_number ?? 'NIL' }}</small></td>
                                        <td>{{ $values->customerAccount->accountType->name ?? '' }}</td>
                                        <td>{{ $values->customerAccount->account->name ?? '' }}</td>
                                        <td>
                                            {{ $values->customerAccount->account_number ?? '' }}
                                        </td>
                                        <td>{{ $values->customerAccount->phone ?? '' }}</td>

                                        <td>
                                            @if ($values->approved == 0)
                                                <span class="badge badge-pill badge-soft-danger font-size-11">Pending</span>
                                            @elseif($values->approved == 1)
                                                <span
                                                    class="badge badge-pill badge-soft-success font-size-11">Approved</span>
                                            @elseif($values->approved == 99)
                                                <span
                                                    class="badge badge-pill badge-soft-danger font-size-11">Rejected</span>
                                            @endif
                                        </td>

                                        <td>
                                            <a href="{{ route('payment-mandate.details', $values->customer_account_uuid) }}"
                                                title="View">
                                                <i type="solid" class="bx bx-folder-open bx-sm text-muted"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @php $serial++; @endphp
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- end table-responsive -->
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
