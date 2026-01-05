@extends('layouts.master')

<style>
    .table th {
        background-color: #E1F5FE !important;
    }
</style>

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
                                <a href="{{ route('companies.collection-account', $company->uuid) }}"
                                    title="New Collection Account" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> New Collection Account
                                </a>

                                <template x-if="approved==0 || approved==99">
                                    <a href="{{ route('companies.registration', $company->uuid) }}" title="View"
                                        class="btn btn-warning btn-sm">
                                        <i class='fa fa-pen'></i> Update Information
                                    </a>
                                </template>

                                <template x-if="approved==0 || approved==99">
                                    <button title="Reject Registration" class="btn btn-danger btn-sm"
                                        x-on:click="openRejectModal();" x-transition x-cloak>
                                        <i class="fa fa-times"></i> Reject Registration
                                    </button>
                                </template>

                                @if ($is_verified_false > 0)
                                    <button x-html="btnApprove" title="Approve Registration" class="btn btn-success btn-sm"
                                        x-on:click="approveRegistration()"></button>
                                @endif

                                @if ($is_nmb_verified_false > 0)
                                    <button x-html="btnStatus" title="Get Status" class="btn btn-outline-success btn-sm"
                                        x-on:click="fetchRegistrationStatus('{{ $company->uuid }}')"></button>
                                @endif
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
                                <th>Corporate Reference</th>
                                <td>{{ $company->reference }}</td>

                                <th>Corporate ShortCode</th>
                                <td>
                                    {{ $company->short_code }}
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
                                <th>eMakato Status</th>
                                <td id="company-status">
                                    <template x-if="approved==0">
                                        <span class="badge badge-soft-warning">Pending Review</span>
                                    </template>

                                    <template x-if="approved==1">
                                        <span class="badge badge-soft-success">Approved</span>
                                    </template>

                                    <template x-if="approved==99">
                                        <span class="badge badge-soft-danger">Failed</span>
                                    </template>
                                </td>

                                <th>Created On</th>
                                <td>{{ date('M d, Y H:i', strtotime($company->created_at)) }}</td>
                            </tr>

                            <tr>
                                <th width="25%">Remarks</th>
                                <td colspan="3">
                                    <span x-text="remarks"></span>
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

                        @if ($company->partnerAccounts->isNotEmpty())
                            <table class="table table-bordered dt-responsive  nowrap w-100" id="dynamicTable">
                                <thead>
                                    <tr>
                                        <td colspan="4" class="font-weight-600 text-uppercase">
                                            Service Provider Collection Accounts
                                        </td>
                                    </tr>

                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="10%">Channel</th>
                                        <th width="10%">Bank/MNO</th>
                                        <th width="20%">Account Name</th>
                                        <th width="10%">Account No.</th>
                                        <th width="10%">Customer No.</th>
                                        <th width="8%">Verified</th>
                                        <th width="24%">Remarks</th>
                                        {{-- <th style="width:10px !important;"></th> --}}
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
                                            <td>{{ $val->customer_code ?? '' }}</td>
                                            <td>
                                                @if ($val->is_verified == 1)
                                                    <span class="badge badge-soft-success">Yes</span>
                                                @elseif($val->is_verified == 0)
                                                    <span class="badge badge-soft-danger">No</span>
                                                @endif
                                            </td>
                                            <td>{{ $val->remarks ?? '' }}</td>
                                            {{-- <td>
                                                <a title="View" class="btn btn-xss">
                                                    <i class="bx bxs-folder-open"></i>
                                                </a>
                                            </td> --}}
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-danger">No any collection account at the moment!</div>
                        @endif

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
                                        <th width="25%">Attachment</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($company->partnerDocuments as $val)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $val->name }}</td>
                                            <td>
                                                @if ($val->attachment != null)
                                                    {{ $val->attachment }}
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($val->attachment != null)
                                                    <a href="{{ asset('assets/uploads/docs/' . $val->attachment) }}"
                                                        download><i class='bx bx-cloud-download bx-xs'></i></a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-danger">No any document at the moment!</div>
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

        @include('companies.modals.rejecting')
    </div>
@endsection

@section('script')
    @include('companies.alpinejs')
@endsection
