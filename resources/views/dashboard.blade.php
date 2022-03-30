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
                                <select class="form-select form-select-sm">
                                    <option value="JA" selected>Jan</option>
                                    <option value="DE">Dec</option>
                                    <option value="NO">Nov</option>
                                    <option value="OC">Oct</option>
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
                        <div id="donut-chart" class="apex-charts"></div>
                    </div>

                    <div class="text-center text-muted">
                        <div class="row">
                            <div class="col-4">
                                <div class="mt-4">
                                    <p class="mb-2 text-truncate"><i class="mdi mdi-circle text-primary me-1"></i> Pending
                                    </p>
                                    <h5>30</h5>
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="mt-4">
                                    <p class="mb-2 text-truncate"><i class="mdi mdi-circle text-danger me-1"></i> Approved
                                    </p>
                                    <h5>60</h5>
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
@endsection

@section('script')
    <!-- apexcharts -->
    <script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <!-- Saas dashboard init -->
    <script src="{{ URL::asset('/assets/js/pages/saas-dashboard.init.js') }}"></script>

    <!-- dashboard init -->
    {{-- <script src="{{ URL::asset('/assets/js/pages/dashboard.init.js') }}"></script> --}}
@endsection
