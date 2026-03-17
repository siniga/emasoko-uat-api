@extends('layouts.master')

<style>
    .provider-shell {
        --provider-primary: #153567;
        --provider-border: #dfe8f5;
        --provider-muted: #6a7d96;
    }

    .provider-hero {
        border: 0;
        border-radius: 20px;
        background: linear-gradient(135deg, #153567 0%, #1f4b8b 100%);
        box-shadow: 0 20px 48px rgba(21, 53, 103, 0.2);
        overflow: hidden;
    }

    .provider-hero__body {
        padding: 1.6rem;
    }

    .provider-kicker {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.8rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .provider-title {
        color: #fff;
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0.9rem 0 0.45rem;
    }

    .provider-subtitle,
    .provider-meta {
        color: rgba(255, 255, 255, 0.8);
    }

    .provider-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .provider-meta__pill {
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
    }

    .provider-card {
        border: 1px solid var(--provider-border);
        border-radius: 18px;
        box-shadow: 0 16px 40px rgba(29, 53, 87, 0.08);
    }

    .provider-summary {
        border: 1px solid var(--provider-border);
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        padding: 1rem 1.05rem;
        height: 100%;
    }

    .provider-summary__label {
        font-size: 0.78rem;
        color: var(--provider-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.35rem;
    }

    .provider-summary__value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--provider-primary);
    }

    .provider-section {
        padding: 0;
        margin-bottom: 1.25rem;
    }

    .provider-section__head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .provider-section__head h5 {
        margin-bottom: 0.15rem;
        color: var(--provider-primary);
        font-weight: 700;
        font-size: 1rem;
    }

    .provider-section__head p {
        margin-bottom: 0;
        color: var(--provider-muted);
        font-size: 0.86rem;
    }

    .provider-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.9rem 1rem;
    }

    .provider-grid__item {
        border-bottom: 1px dashed #e5ebf5;
        padding-bottom: 0.65rem;
    }

    .provider-grid__label {
        display: block;
        font-size: 0.76rem;
        color: var(--provider-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.25rem;
    }

    .provider-grid__value {
        color: #243b5a;
        font-weight: 600;
        word-break: break-word;
    }

    .provider-table {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #e0e8f4;
    }

    .provider-table thead th,
    .provider-table thead td {
        background-color: #edf4ff !important;
        color: var(--provider-primary);
        font-weight: 700;
        border-color: #dce7fa;
    }

    .provider-empty {
        border: 1px dashed #d6e1ef;
        border-radius: 14px;
        padding: 1rem;
        background: #fff;
        color: var(--provider-muted);
    }

    .provider-actions {
        display: flex;
        justify-content: flex-end;
        position: relative;
        z-index: 2;
    }

    .provider-actions .btn-group .btn {
        border-color: #d7e3f4;
        color: var(--provider-primary);
        background: #f8fbff;
        font-weight: 600;
    }

    .provider-actions .btn-group .btn:hover,
    .provider-actions .btn-group .btn:focus {
        background: #edf4ff;
        color: var(--provider-primary);
    }

    .provider-actions .dropdown-menu {
        border: 1px solid #dde7f5;
        border-radius: 14px;
        padding: 0.45rem 0;
        box-shadow: 0 16px 36px rgba(21, 53, 103, 0.12);
    }

    .provider-actions .dropdown-item {
        padding: 0.55rem 1rem;
    }

    .provider-grid__item--wide {
        grid-column: span 2;
    }

    @media (max-width: 767.98px) {
        .provider-grid {
            grid-template-columns: 1fr;
        }

        .provider-grid__item--wide {
            grid-column: span 1;
        }
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

    <div class="provider-shell" x-data="initData()">
        <div class="row">
            <div class="col-lg-12">
                <div class="card provider-hero mb-4">
                    <div class="provider-hero__body">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
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
                            <div class="col-lg-4 mt-3 mt-lg-0">
                                <div class="provider-actions">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-light" type="button">
                                            Provider Actions
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light dropdown-toggle dropdown-toggle-split"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="mdi mdi-chevron-down font-size-14"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a href="{{ route('companies.collection-account', $company->uuid) }}"
                                                class="dropdown-item">
                                                <i class="fas fa-plus me-2"></i> New Collection Account
                                            </a>

                                            <template x-if="approved==0 || approved==99">
                                                <a href="{{ route('companies.registration', $company->uuid) }}"
                                                    class="dropdown-item">
                                                    <i class="fa fa-pen me-2"></i> Update Information
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
                                                    <i class="fa fa-check me-2"></i> <span x-text="formLoading ? 'Approving Registration....' : 'Approve Registration'"></span>
                                                </button>
                                            @endif

                                            @if ($is_nmb_verified_false > 0)
                                                <button type="button" class="dropdown-item"
                                                    x-on:click="fetchRegistrationStatus('{{ $company->uuid }}')">
                                                    <i class="fa fa-refresh me-2"></i> <span x-text="formLoading ? 'Get Registration status....' : 'Get Status'"></span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-2">
                <div class="provider-summary">
                    <div class="provider-summary__label">Active Mandates</div>
                    <div class="provider-summary__value">{{ number_format($summaryStats['active_mandates']) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="provider-summary">
                    <div class="provider-summary__label">Total Mandates</div>
                    <div class="provider-summary__value">{{ number_format($summaryStats['total_mandates']) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="provider-summary">
                    <div class="provider-summary__label">Expected This Month</div>
                    <div class="provider-summary__value">{{ number_format($summaryStats['this_month_expected']) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="provider-summary">
                    <div class="provider-summary__label">Collected This Month</div>
                    <div class="provider-summary__value">{{ number_format($summaryStats['this_month_collected']) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="provider-summary">
                    <div class="provider-summary__label">Collection Rate</div>
                    <div class="provider-summary__value">{{ number_format($summaryStats['collection_rate'], 2) }}%</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="provider-summary">
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

                        <div class="provider-section">
                            <div class="provider-section__head">
                                <div>
                                    <h5>Provider Summary Dashboard</h5>
                                    <p>Quick health indicators specific to this service provider.</p>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="provider-summary">
                                        <div class="provider-summary__label">Documents</div>
                                        <div class="provider-summary__value">{{ number_format($summaryStats['documents']) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="provider-summary">
                                        <div class="provider-summary__label">Collection Accounts</div>
                                        <div class="provider-summary__value">{{ number_format($summaryStats['accounts']) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="provider-summary">
                                        <div class="provider-summary__label">Verified Accounts</div>
                                        <div class="provider-summary__value">{{ number_format($summaryStats['verified_accounts']) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="provider-summary">
                                        <div class="provider-summary__label">Representatives</div>
                                        <div class="provider-summary__value">{{ number_format($summaryStats['representatives']) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="provider-summary">
                                        <div class="provider-summary__label">Expected Today</div>
                                        <div class="provider-summary__value">{{ number_format($summaryStats['today_expected']) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="provider-summary">
                                        <div class="provider-summary__label">Collected Today</div>
                                        <div class="provider-summary__value">{{ number_format($summaryStats['today_collected']) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="provider-summary">
                                        <div class="provider-summary__label">Overdue Installments</div>
                                        <div class="provider-summary__value">{{ number_format($summaryStats['overdue_count']) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="provider-summary">
                                        <div class="provider-summary__label">Overdue Amount</div>
                                        <div class="provider-summary__value">{{ number_format($summaryStats['overdue_amount']) }}</div>
                                    </div>
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

                                <div class="table-responsive provider-table">
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

                                <div class="table-responsive provider-table">
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

                                <div class="table-responsive provider-table">
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
