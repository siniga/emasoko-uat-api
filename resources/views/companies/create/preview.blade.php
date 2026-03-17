@extends('layouts.master')

@section('title')
    Register Service Provider
@endsection

@include('companies.create.partials.styles')

<style>
    .sp-overview-card {
        border: 1px solid #e3ebf7;
        border-radius: 16px;
        padding: 1rem 1.1rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        height: 100%;
    }

    .sp-overview-card__label {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7c93;
        margin-bottom: 0.45rem;
    }

    .sp-overview-card__value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #153567;
    }

    .sp-kv {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.85rem;
    }

    .sp-kv__item {
        border: 1px solid #e8edf5;
        border-radius: 14px;
        background: #fff;
        padding: 0.95rem 1rem;
    }

    .sp-kv__label {
        display: block;
        font-size: 0.78rem;
        color: #6b7c93;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.35rem;
    }

    .sp-kv__value {
        color: #153567;
        font-weight: 600;
        word-break: break-word;
    }

    .sp-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .sp-section-head__copy h5 {
        margin-bottom: 0.2rem;
        color: #153567;
        font-weight: 700;
    }

    .sp-section-head__copy p {
        margin-bottom: 0;
        color: #6b7c93;
    }

    .sp-empty {
        border: 1px dashed #d7e2f0;
        border-radius: 14px;
        background: #fbfcfe;
        color: #6b7c93;
        padding: 1rem 1.1rem;
    }

    @media (max-width: 767.98px) {
        .sp-kv {
            grid-template-columns: 1fr;
        }
    }
</style>

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Register Service Provider</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('companies.lists') }}">Service Providers</a></li>
                        <li class="breadcrumb-item active">Register New</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

    @include('companies.create.partials.hero', [
        'currentStep' => 5,
        'heading' => 'Review the service provider setup',
        'subtitle' => 'Confirm the registration package before saving the draft or submitting it for approval.',
        'company' => $company,
    ])

    <div class="row" x-data="initData()">
        <div class="col-lg-12">
            <div class="card sp-card">
                <div class="card-body sp-card__body">
                    <form @submit.prevent="">
                        <input type="hidden" name="id" x-model="formData.id">
                        <input type="hidden" name="uuid" x-model="formData.uuid">

                        <section>
                            <div class="sp-section">
                                <div class="sp-section-head">
                                    <div class="sp-section-head__copy">
                                        <h5>Readiness Summary</h5>
                                        <p>Use this checkpoint to confirm the service provider package is complete before saving or submitting.</p>
                                    </div>
                                    <div>
                                        <span class="badge bg-soft-primary text-primary">Preview Step</span>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="sp-overview-card">
                                            <div class="sp-overview-card__label">Documents</div>
                                            <div class="sp-overview-card__value">{{ number_format($company->partnerDocuments->count()) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="sp-overview-card">
                                            <div class="sp-overview-card__label">Accounts</div>
                                            <div class="sp-overview-card__value">{{ number_format($company->partnerAccounts->count()) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="sp-overview-card">
                                            <div class="sp-overview-card__label">Representatives</div>
                                            <div class="sp-overview-card__value">{{ number_format($company->partnerStaff->count()) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="sp-overview-card">
                                            <div class="sp-overview-card__label">Current Status</div>
                                            <div class="sp-overview-card__value">
                                                @if ($company->approved == 1)
                                                    Approved
                                                @elseif($company->approved == 99)
                                                    Failed
                                                @else
                                                    Draft
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="sp-section">
                                <div class="sp-section-head">
                                    <div class="sp-section-head__copy">
                                        <h5>Service Provider Information</h5>
                                        <p>Core identity and address information captured during onboarding.</p>
                                    </div>
                                </div>

                                <div class="sp-kv">
                                    <div class="sp-kv__item">
                                        <span class="sp-kv__label">Business Name</span>
                                        <span class="sp-kv__value">{{ $company->business_name }}</span>
                                    </div>
                                    <div class="sp-kv__item">
                                        <span class="sp-kv__label">Business Category</span>
                                        <span class="sp-kv__value">{{ $company->partnerCategory->name ?? '-' }}</span>
                                    </div>
                                    <div class="sp-kv__item">
                                        <span class="sp-kv__label">TIN Number</span>
                                        <span class="sp-kv__value">{{ $company->tin_number }}</span>
                                    </div>
                                    <div class="sp-kv__item">
                                        <span class="sp-kv__label">Year of Incorporation</span>
                                        <span class="sp-kv__value">{{ $company->year_of_incorporation }}</span>
                                    </div>
                                    <div class="sp-kv__item">
                                        <span class="sp-kv__label">Number of Staff</span>
                                        <span class="sp-kv__value">{{ $company->number_of_staff }}</span>
                                    </div>
                                    <div class="sp-kv__item">
                                        <span class="sp-kv__label">Region</span>
                                        <span class="sp-kv__value">{{ $company->region->name ?? '-' }}</span>
                                    </div>
                                    <div class="sp-kv__item">
                                        <span class="sp-kv__label">Email</span>
                                        <span class="sp-kv__value">{{ $company->email }}</span>
                                    </div>
                                    <div class="sp-kv__item">
                                        <span class="sp-kv__label">Phone</span>
                                        <span class="sp-kv__value">{{ $company->phone }}</span>
                                    </div>
                                    <div class="sp-kv__item">
                                        <span class="sp-kv__label">Postal Address</span>
                                        <span class="sp-kv__value">{{ $company->postal_address ?? '-' }}</span>
                                    </div>
                                    <div class="sp-kv__item">
                                        <span class="sp-kv__label">Physical Address</span>
                                        <span class="sp-kv__value">{{ $company->physical_address ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="sp-section">
                                <div class="sp-section-head">
                                    <div class="sp-section-head__copy">
                                        <h5>Supporting Documents</h5>
                                        <p>Files uploaded for compliance and provider due diligence.</p>
                                    </div>
                                    <div>
                                        <span class="badge bg-soft-info text-info">{{ number_format($company->partnerDocuments->count()) }} files</span>
                                    </div>
                                </div>

                                @if ($company->partnerDocuments->isNotEmpty())
                                    <div class="table-responsive sp-table">
                                        <table class="table table-nowrap align-middle table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    <th width="6%">#</th>
                                                    <th width="54%">Document Name</th>
                                                    <th width="40%">Attachment</th>
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
                                                                    href="{{ asset('assets/uploads/docs/' . $val->attachment) }}">
                                                                    {{ $val->attachment }}
                                                                </a>
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="sp-empty">No document has been added to this registration yet.</div>
                                @endif
                            </div>

                            <div class="sp-section">
                                <div class="sp-section-head">
                                    <div class="sp-section-head__copy">
                                        <h5>Collection Accounts</h5>
                                        <p>Accounts configured to receive collections and support operational settlement.</p>
                                    </div>
                                    <div>
                                        <span class="badge bg-soft-success text-success">{{ number_format($company->partnerAccounts->count()) }} accounts</span>
                                    </div>
                                </div>

                                @if ($company->partnerAccounts->isNotEmpty())
                                    <div class="table-responsive sp-table">
                                        <table class="table table-nowrap align-middle table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    <th width="6%">#</th>
                                                    <th>Account Type</th>
                                                    <th>Bank/Institution</th>
                                                    <th>Account Name</th>
                                                    <th>Account Number</th>
                                                    <th>Customer Number</th>
                                                    <th>Branch Code</th>
                                                    <th class="text-end">Corporate Limit</th>
                                                    <th>Currency</th>
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
                                                        <td>{{ $val->branch_code ?? '' }}</td>
                                                        <td class="text-end">{{ number_format($val->corporate_limit) ?? '' }}</td>
                                                        <td>{{ $val->currency->short_code ?? '' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="sp-empty">No collection account has been added to this registration yet.</div>
                                @endif
                            </div>

                            <div class="sp-section">
                                <div class="sp-section-head">
                                    <div class="sp-section-head__copy">
                                        <h5>Authorized Representatives</h5>
                                        <p>Contacts who will handle approvals and formal follow-up for the provider.</p>
                                    </div>
                                    <div>
                                        <span class="badge bg-soft-warning text-warning">{{ number_format($company->partnerStaff->count()) }} contacts</span>
                                    </div>
                                </div>

                                @if ($company->partnerStaff->isNotEmpty())
                                    <div class="table-responsive sp-table">
                                        <table class="table table-nowrap align-middle table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    <th width="6%">#</th>
                                                    <th>Staff Name</th>
                                                    <th>NIDA Number</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Title</th>
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
                                @else
                                    <div class="sp-empty">No authorized representative has been added to this registration yet.</div>
                                @endif
                            </div>

                            <div id="message_wrp"></div>

                            <template x-if="complete=='0' || complete=='1'">
                                <div class="sp-actionbar">
                                    <div class="sp-actionbar__meta">Final step. Save this draft for later or submit it for approval when the package is complete.</div>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        <template x-if="approved==0">
                                            <a href="{{ route('companies.cancel-registration', $company->uuid) }}"
                                                class="btn btn-danger cancel text-medium">Cancel
                                            </a>
                                        </template>

                                        <template x-if="approved==0 || approved==99">
                                            <a href="{{ route('companies.registration', $company->uuid) }}"
                                                class="btn btn-outline-primary text-medium">Edit
                                            </a>
                                        </template>

                                        <template x-if="approved==0">
                                            <button type="submit" name="save"
                                                class="btn btn-secondary text-medium" x-text="buttonSave"
                                                :disabled="saveLoading" x-on:click="savingForm();">
                                            </button>
                                        </template>

                                        <template x-if="approved==0 || approved==99">
                                            <button type="submit" name="submit" class="btn btn-success text-medium"
                                                x-text="buttonSubmit" :disabled="submitLoading"
                                                x-on:click="submitForm();"></button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </section>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->
@endsection

@section('script')
    @include('companies.create.alpinejs')
@endsection
