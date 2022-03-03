@extends('layouts.master')

@section('title')
    Companies
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1')
            Companies
        @endslot
        @slot('title')
            Companies
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    @if ($company->status == 1 || $company->status == 2)
                        <div class="mb-3 text-sm-start d-none d-sm-block">
                            <a href="{{ route($route, $company->id) }}" title="{{ $action }}"
                                class="btn btn-sm btn-success">
                                <span class="text-capitalize">
                                    <i class="fa fa-check"></i> {{ $action }}</span>
                            </a>
                        </div>
                    @endif

                    @if (session()->get('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div><br />
                    @elseif(session()->get('danger'))
                        <div class="alert alert-danger">
                            {{ session()->get('danger') }}
                        </div><br />
                    @endif

                    <table class="table table-stripped table-bordered">
                        <tr>
                            <td colspan="4">
                                <b>Company Information</b>
                            </td>
                        </tr>
                        <tr>
                            <th width=25%>Business Name</th>
                            <td width=25%>
                                <span>{{ $company->business_name }}</span>
                            </td>

                            <th>Business Category</th>
                            <td>
                                <span>{{ $company->partnerCategory->name ?? '' }}</span>
                            </td>
                        </tr>

                        <tr>
                            <th>TIN Number</th>
                            <td>{{ $company->tin_number }}</td>

                            <th>Year of Incorporation</th>
                            <td>
                                {{ $company->year_of_incorporation }}
                            </td>
                        </tr>

                        <tr>
                            <th>Number of Staff</th>
                            <td>{{ $company->number_of_staff }}</td>

                            <th>Contacts</th>
                            <td>
                                Email: {{ $company->email }}<br />
                                Phone: {{ $company->phone }}
                            </td>
                        </tr>

                        <tr>
                            <th>Status</th>
                            <td colspan="3">
                                @if ($company->status == 1)
                                    <span class="badge bg-success">Completed</span>
                                @elseif($company->status == 2)
                                    <span class="badge bg-success">Verified</span>
                                @elseif($company->status == 3)
                                    <span class="badge bg-success">Approved</span>
                                @endif
                            </td>
                        </tr>



                        <tr>
                            <td colspan="4">
                                <b>Company Location</b>
                            </td>
                        </tr>

                        <tr>
                            <th>Region</th>
                            <td>{{ $company->region->name ?? '' }}</td>

                            <th>Physical Address</th>
                            <td>
                                Email: {{ $company->address }}
                            </td>
                        </tr>

                    </table>
                </div>
                <!--./col -->
            </div>
            <!--./row-->

            <div class="row mt-3">
                <div class="col-lg-12">
                    <h5 class="text-capitalize">Company Documents</h5>
                    <hr />
                </div>
                <!--./col -->
            </div>
            <!--./row -->

            <div class="row">
                <div class="col-lg-12">
                    @if ($company->partnerDocuments->isNotEmpty())
                        <table class="table table-nowrap align-middle table-bordered">
                            <thead>
                                <tr>
                                    <th width="3%">#</th>
                                    <th width="60%">Name</th>
                                    <th width="30%">Attachment</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($company->partnerDocuments as $val)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $val->name }}</td>
                                        <td>
                                            @if ($val->attachment != null)
                                                <a download=""
                                                    href="{{ asset('assets/uploads/docs' . $val->attachment) }}">
                                                    {{ $val->attachment }}
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="alert">No any document at the moment!</div>
                    @endif
                </div>
                <!--./col -->
            </div>
            <!--./row -->

            <div class="row mt-2">
                <div class="col-lg-12">
                    <h5 class="text-capitalize">Company Accounts</h5>
                    <hr />
                </div>
                <!--./col -->
            </div>
            <!--./row -->

            <div class="row">
                <div class="col-lg-12">
                    @if ($company->partnerAccounts->isNotEmpty())
                        <table class="table table-nowrap align-middle table-bordered">
                            <thead>
                                <tr>
                                    <th width="3%">#</th>
                                    <th width="20%">Account Type</th>
                                    <th width="20%">Account</th>
                                    <th width="25%">Account Name</th>
                                    <th width="20%">Account Number</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($company->partnerAccounts as $val)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $val->accountType->name ?? '' }}</td>
                                        <td>{{ $val->account->name ?? '' }}</td>
                                        <td>{{ $val->account_name ?? '' }}</td>
                                        <td>{{ $val->account_number ?? '' }}</td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="alert">No any account at the moment!</div>
                    @endif
                </div>
                <!--./col -->
            </div>
            <!--./row -->

            <div class="row mt-2">
                <div class="col-lg-12">
                    <h5 class="text-capitalize">Company Staff</h5>
                    <hr />
                </div>
                <!--./col -->
            </div>
            <!--./row -->

            <div class="row">
                <div class="col-lg-12">
                    @if ($company->partnerStaff->isNotEmpty())
                        <table class="table table-nowrap align-middle table-bordered">
                            <thead>
                                <tr>
                                    <th width="3%">#</th>
                                    <th width="20%">Staff Name</th>
                                    <th width="20%">NIDA Number</th>
                                    <th width="25%">Email</th>
                                    <th width="20%">Phone</th>
                                    <th width="20%">Title</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($company->partnerStaff as $val)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $val->name ?? '' }}</td>
                                        <td>{{ $val->NIDA_number ?? '' }}</td>
                                        <td>{{ $val->email ?? '' }}</td>
                                        <td>{{ $val->phone ?? '' }}</td>
                                        <td>{{ $val->title ?? '' }}</td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="alert">No any staff at the moment!</div>
                    @endif
                </div>
                <!--./col -->
            </div>
            <!--./row -->

            <div class="row mt-2">
                <div class="col-lg-12">
                    <h5 class="text-capitalize">Company Authorized Person</h5>
                    <hr />
                </div>
                <!--./col -->
            </div>
            <!--./row -->

            <div class="row">
                <div class="col-lg-12">
                    <table class="table table-stripped table-bordered">
                        <tr>
                            <th width=25%>Person Name</th>
                            <td width=25%>
                                <span>{{ $company->partnerAuthorizeUser->user->name ?? '' }}</span>
                            </td>

                            <th>NIDA Number</th>
                            <td>
                                <span>{{ $company->partnerAuthorizeUser->user->NIDA_number ?? '' }}</span>
                            </td>
                        </tr>

                        <tr>
                            <th>Email</th>
                            <td>{{ $company->partnerAuthorizeUser->user->email ?? '' }}
                            </td>

                            <th>Phone</th>
                            <td>
                                {{ $company->partnerAuthorizeUser->user->phone ?? '' }}
                            </td>
                        </tr>
                    </table>

                </div>
            </div>
        </div>
    </div>
    <!-- end row -->


@endsection
@section('script')
    <!-- apexcharts -->
    <script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <!-- dashboard init -->
    <script src="{{ URL::asset('/assets/js/pages/dashboard.init.js') }}"></script>
@endsection
