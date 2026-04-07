@extends('layouts.master')

@section('title')
    Service Providers
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/companies-show.css') }}" rel="stylesheet" type="text/css" />
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

    <div class="provider-shell" x-data="initData()">
        <div class="row">
            <div class="col-lg-12">
                <div class="card provider-hero mb-4">
                    <div class="provider-hero__body">
                        <div class="row align-items-center">
                            <div class="col-lg-12 col-12">
                                <span class="provider-kicker">Service Provider Profile</span>
                                <h2 class="provider-title">{{ $company->business_name }}</h2>
                                <p class="provider-subtitle">Operational view of provider setup, mandate footprint, collection performance, and approval readiness.</p>
                                <div class="provider-meta">
                                    <span class="provider-meta__pill"><strong>Reference:</strong> {{ $company->reference }}</span>
                                    <span class="provider-meta__pill"><strong>Category:</strong> {{ $company->partnerCategory->name ?? '-' }}</span>
                                    <span class="provider-meta__pill"><strong>Status:</strong>
                                        @if ($company->approved == 1)
                                            Approved
                                        @elseif($company->approved == 99)
                                            Failed
                                        @else
                                            Pending Review
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <div class="provider-summary shadow h-96 py-2">
                    <div class="provider-summary__label">Total Mandates</div>
                    <div class="provider-summary__split">
                        <div class="provider-summary__metric">
                            <div class="provider-summary__value">{{ number_format($summaryStats['total_mandates_amount']) }}</div>
                        </div>
                        <div class="provider-summary__metric provider-summary__metric--count">
                            <div class="provider-summary__value">{{ number_format($summaryStats['total_mandates']) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="provider-summary bg-success-1 shadow h-96 py-2">
                    <div class="provider-summary__label">Active Mandates</div>
                    <div class="provider-summary__split">
                        <div class="provider-summary__metric">
                            <div class="provider-summary__value">{{ number_format($summaryStats['active_mandates_amount']) }}</div>
                        </div>
                        <div class="provider-summary__metric provider-summary__metric--count">
                            <div class="provider-summary__value">{{ number_format($summaryStats['active_mandates']) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="provider-summary bg-danger-1 shadow h-96 py-2">
                    <div class="provider-summary__label">Pending Mandates</div>
                    <div class="provider-summary__split">
                        <div class="provider-summary__metric">
                            <div class="provider-summary__value">{{ number_format($summaryStats['pending_mandates_amount']) }}</div>
                        </div>
                        <div class="provider-summary__metric provider-summary__metric--count">
                            <div class="provider-summary__value">{{ number_format($summaryStats['pending_mandates']) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="provider-summary bg-danger shadow h-96 py-2">
                    <div class="provider-summary__label">Failed Mandates</div>
                    <div class="provider-summary__split">
                        <div class="provider-summary__metric">
                            <div class="provider-summary__value">{{ number_format($summaryStats['failed_mandates_amount']) }}</div>
                        </div>
                        <div class="provider-summary__metric provider-summary__metric--count">
                            <div class="provider-summary__value">{{ number_format($summaryStats['failed_mandates']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <div class="provider-summary shadow h-96 py-2">
                    <div class="provider-summary__label">Expected ({{ $summaryStats['current_month_label'] }})</div>
                    <div class="provider-summary__value">{{ number_format($summaryStats['this_month_expected']) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="provider-summary bg-success-1 shadow h-96 py-2">
                    <div class="provider-summary__label">Collected ({{ $summaryStats['current_month_label'] }})</div>
                    <div class="provider-summary__value">{{ number_format($summaryStats['this_month_collected']) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="provider-summary bg-info shadow h-96 py-2">
                    <div class="provider-summary__label">Collection Rate</div>
                    <div class="provider-summary__value">{{ number_format($summaryStats['collection_rate'], 2) }}%</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="provider-summary bg-danger shadow h-96 py-2">
                    <div class="provider-summary__label">Overdue Amount</div>
                    <div class="provider-summary__value">{{ number_format($summaryStats['overdue_amount']) }}</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card provider-card">
                    <div class="card-body">
                        <!-- success message -->
                        <div id="message_wrp"></div>

                        <div class="provider-section">
                            <div class="provider-section__head">
                                <div>
                                    <h5>Provider Overview</h5>
                                    <p>Core service provider identity arranged in the same overview style as mandate details.</p>
                                </div>
                                <div class="actions">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-primary" type="button">
                                            Actions
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="mdi mdi-chevron-down font-size-14"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a href="{{ route('companies.collection-account', $company->uuid) }}"
                                                class="dropdown-item">
                                                <i class="bx bx-plus me-2"></i> New Collection Account
                                            </a>

                                            <template x-if="approved==0 || approved==99">
                                                <a href="{{ route('companies.registration', $company->uuid) }}"
                                                    class="dropdown-item">
                                                    <i class='bx bx-pencil me-2'></i> Update Information
                                                </a>
                                            </template>

                                            <template x-if="approved==0 || approved==99">
                                                <button type="button" class="dropdown-item text-danger"
                                                    x-on:click="openRejectModal();" x-transition x-cloak>
                                                    <i class="fa fa-times me-2"></i> Reject Registration
                                                </button>
                                            </template>

                                            @if ($is_verified_false > 0)
                                                <button type="button" class="dropdown-item text-success"
                                                    x-on:click="approveRegistration()">
                                                    <i class='bx bx-check-square me-2'></i> <span x-text="formLoading ? 'Approving Registration....' : 'Approve Registration'"></span>
                                                </button>
                                            @endif

                                            @if ($is_nmb_verified_false > 0)
                                                <button type="button" class="dropdown-item"
                                                    x-on:click="fetchRegistrationStatus('{{ $company->uuid }}')">
                                                    <i class='bx bx-refresh me-2'></i> <span x-text="formLoading ? 'Fetch Registration status....' : 'Fetch Status'"></span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="provider-grid">
                                <div class="provider-grid__item provider-grid__item--wide">
                                    <span class="provider-grid__label">Business Name</span>
                                    <span class="provider-grid__value">{{ $company->business_name }}</span>
                                </div>
                                <div class="provider-grid__item">
                                    <span class="provider-grid__label">Business Category</span>
                                    <span class="provider-grid__value">{{ $company->partnerCategory->name ?? '-' }}</span>
                                </div>
                                <div class="provider-grid__item">
                                    <span class="provider-grid__label">Corporate Reference</span>
                                    <span class="provider-grid__value">{{ $company->reference }}</span>
                                </div>
                                <div class="provider-grid__item">
                                    <span class="provider-grid__label">Corporate ShortCode</span>
                                    <span class="provider-grid__value">{{ $company->short_code }}</span>
                                </div>
                                <div class="provider-grid__item">
                                    <span class="provider-grid__label">TIN Number</span>
                                    <span class="provider-grid__value">{{ $company->tin_number }}</span>
                                </div>
                                <div class="provider-grid__item">
                                    <span class="provider-grid__label">Year of Incorporation</span>
                                    <span class="provider-grid__value">{{ $company->year_of_incorporation }}</span>
                                </div>
                                <div class="provider-grid__item">
                                    <span class="provider-grid__label">eMakato Status</span>
                                    <span class="provider-grid__value" id="company-status">
                                        <template x-if="approved==0">
                                            <span class="badge badge-soft-warning">Pending Review</span>
                                        </template>
                                        <template x-if="approved==1">
                                            <span class="badge badge-soft-success">Approved</span>
                                        </template>
                                        <template x-if="approved==99">
                                            <span class="badge badge-soft-danger">Failed</span>
                                        </template>
                                    </span>
                                </div>
                                <div class="provider-grid__item">
                                    <span class="provider-grid__label">Created On</span>
                                    <span class="provider-grid__value">{{ date('M d, Y H:i', strtotime($company->created_at)) }}</span>
                                </div>
                                <div class="provider-grid__item">
                                    <span class="provider-grid__label">Email</span>
                                    <span class="provider-grid__value">{{ $company->email ?? '-' }}</span>
                                </div>
                                <div class="provider-grid__item">
                                    <span class="provider-grid__label">Phone</span>
                                    <span class="provider-grid__value">{{ $company->phone }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="provider-section">
                            <div class="provider-section__head">
                                <div>
                                    <h5>Additional Details</h5>
                                    <p>Address and review notes for this provider.</p>
                                </div>
                            </div>

                            <div class="provider-grid">
                                <div class="provider-grid__item">
                                    <span class="provider-grid__label">Postal Address</span>
                                    <span class="provider-grid__value">{{ $company->postal_address ?? '-' }}</span>
                                </div>
                                <div class="provider-grid__item">
                                    <span class="provider-grid__label">Physical Address</span>
                                    <span class="provider-grid__value">{{ $company->physical_address }}</span>
                                </div>
                                <div class="provider-grid__item provider-grid__item--wide" style="grid-column: 1 / -1;">
                                    <span class="provider-grid__label">Remarks</span>
                                    <span class="provider-grid__value" x-text="remarks || '-'"></span>
                                </div>
                            </div>
                        </div>

                        @if ($company->partnerAccounts->isNotEmpty())
                            <div class="provider-section">
                                <div class="provider-section__head">
                                    <div>
                                        <h5>Collection Accounts</h5>
                                        <p>Configured settlement accounts and their verification state.</p>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered dt-responsive nowrap w-100 mb-0" id="dynamicTable">
                                        <thead>
                                            <tr>
                                                <th width="3%">#</th>
                                                <th width="10%">Channel</th>
                                                <th width="10%">Bank/MNO</th>
                                                <th width="20%">Account Name</th>
                                                <th width="10%">Account No.</th>
                                                <th width="10%">Customer No.</th>
                                                <th width="8%">Verified</th>
                                                <th width="24%">Remarks</th>
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
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="provider-empty mb-4">No collection account has been added for this service provider yet.</div>
                        @endif

                        @if ($company->partnerDocuments->isNotEmpty())
                            <div class="provider-section">
                                <div class="provider-section__head">
                                    <div>
                                        <h5>Documents</h5>
                                        <p>Compliance files and uploaded provider records.</p>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-nowrap align-middle table-bordered mb-0">
                                        <thead>
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
                                </div>
                            </div>
                        @else
                            <div class="provider-empty mb-4">No document has been uploaded for this service provider yet.</div>
                        @endif

                        @if ($company->partnerStaff->isNotEmpty())
                            <div class="provider-section">
                                <div class="provider-section__head">
                                    <div>
                                        <h5>Authorized Representatives</h5>
                                        <p>Contacts assigned to operate and approve on behalf of this provider.</p>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-nowrap align-middle table-bordered mb-0">
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
                                </div>
                            </div>
                        @else
                            <div class="provider-empty">No authorized representative has been recorded for this service provider yet.</div>
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
