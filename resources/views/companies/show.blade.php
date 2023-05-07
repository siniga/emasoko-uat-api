@extends('layouts.master')

@section('title')
    Service Providers
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Service Provider Information</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('companies.lists') }}">Service Providers</a></li>
                        <li class="breadcrumb-item active">Provider Information</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

    <div x-data="initData()">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <!-- success message -->
                        <div id="message_wrp"></div>

                        <div class="d-flex justify-content-between mb-2">
                            <div></div>

                            <div class="text-sm-end d-none d-sm-block">
                                <button title="Assessment Process" class="btn btn-outline-primary text-medium btn-sm"
                                    x-on:click="sidebarOpen = true">
                                    <i class="fa fa-check"></i> Service Provider Assessment
                                </button>
                            </div>
                        </div>

                        <table class="table table-stripped table-bordered mb-4">
                            <tr>
                                <td colspan="4" class="font-weight-600 text-uppercase">
                                    Service Provider Information
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

                                <th>Status</th>
                                <td colspan="3" id="company-status">
                                    @if ($company->status == 0)
                                        <span class="badge bg-warning">Registration Process</span>
                                    @elseif ($company->status == 1)
                                        <span class="badge bg-info">Completed Registration</span>
                                    @elseif($company->status == 2)
                                        <span class="badge bg-info">Assessment Process</span>
                                    @elseif($company->status == 3)
                                        <span class="badge bg-success">Assessment Approved</span>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td colspan="4">
                                    <span class="font-weight-600 font-size-12">Service Provider Address</span>
                                </td>
                            </tr>

                            <tr>
                                <th>Email</th>
                                <td>{{ $company->email ?? '' }}</td>

                                <th>Phone</th>
                                <td>{{ $company->phone }}</td>
                            </tr>

                            <tr>
                                <th>Postal Address</th>
                                <td>{{ $company->postal_address ?? '' }}</td>

                                <th>Physical Address</th>
                                <td>{{ $company->physical_address }}</td>
                            </tr>
                        </table>

                        @if ($company->partnerDocuments->isNotEmpty())
                            <table class="table table-nowrap align-middle table-bordered mb-4">
                                <thead>
                                    <tr>
                                        <td colspan="3" class="font-weight-600 text-uppercase">
                                            Service Provider Documents
                                        </td>
                                    </tr>

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
                            <div class="alert alert-danger">No any document at the moment!</div>
                        @endif

                        @if ($company->partnerAccounts->isNotEmpty())
                            <table class="table table-nowrap align-middle table-bordered mb-4">
                                <thead>
                                    <tr>
                                        <td colspan="4" class="font-weight-600 text-uppercase">
                                            Service Provider Collection Accounts
                                        </td>
                                    </tr>

                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="20%">Account Type</th>
                                        <th width="20%">Bank/MNO</th>
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
                            <div class="alert alert-danger">No any collection account at the moment!</div>
                        @endif

                        @if ($company->partnerStaff->isNotEmpty())
                            <table class="table table-nowrap align-middle table-bordered">
                                <thead>
                                    <tr>
                                        <td colspan="4" class="font-weight-600 text-uppercase">
                                            Authorized Representative
                                        </td>
                                    </tr>

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
                            <div class="alert alert-danger">No any staff at the moment!</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!--offcanvas wrp -->
        <div id="offcanvas-wrp" class="" x-show="sidebarOpen"></div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        //initiate data
        function initData() {
            return {
                sidebarOpen: false,

                manageInformation(id){},
                manageInformation(id){},
            }
        }
    </script>
@endsection
