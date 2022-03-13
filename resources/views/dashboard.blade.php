@extends('layouts.master')

@section('title')
    @lang('translation.Dashboards')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Dashboards
        @endslot
        @slot('title')
            Dashboard
        @endslot
    @endcomponent

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
                        <h4 class="card-title mb-4">Payment Mandate</h4>
                    </div>

                    <div class="row">
                        <div class="col-lg-4">
                            <div class="text-muted">
                                <div class="mb-4">
                                    <p>This month</p>
                                    <h4>2,000,000</h4>
                                    <div><span class="badge badge-soft-success font-size-12 me-1"> + 0.2% </span> From
                                        previous period</div>
                                </div>

                                <div>
                                    <a href="javascript: void(0);"
                                        class="btn btn-primary waves-effect waves-light btn-sm">View Details <i
                                            class="mdi mdi-chevron-right ms-1"></i></a>
                                </div>

                                <div class="mt-4">
                                    <p class="mb-2">Last month</p>
                                    <h5>1,600,000</h5>
                                </div>

                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div id="line-chart" class="apex-charts" dir="ltr"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Payment Mandate Percentage</h4>

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
                        <h4 class="card-title mb-4">Payment Mandate by Channel</h4>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div id="bar-chart" class="apex-charts" dir="ltr"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!--./col -->

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
