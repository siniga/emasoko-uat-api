@extends('layouts.master')

@section('title')
    Register Service Provider
@endsection

@include('companies.create.partials.styles')

@section('css')
    <link href="{{ URL::asset('/assets/css/companies-show.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="cp-preview" x-data="initData()">
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

        <div class="card cp-card mb-4">
            <div class="card-body">
                <form @submit.prevent="">
                    <input type="hidden" name="id" x-model="formData.id">
                    <input type="hidden" name="uuid" x-model="formData.uuid">

                    <div class="card cp-card mb-4">
                        <div class="card-body">
                            <div class="sp-section-head">
                                <div class="sp-section-head__copy">
                                    <h5>Service Provider Information</h5>
                                    <p>Core identity and address information captured during onboarding.</p>
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
                    </div>

                    <div class="card cp-card mb-4">
                        <div class="card-body">
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
                                <div class="table-responsive">
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
                                                            <a download href="{{ asset('assets/uploads/docs/' . $val->attachment) }}">
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
                    </div>

                    <div class="card cp-card mb-4">
                        <div class="card-body">
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
                                <div class="table-responsive">
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
                    </div>

                    <div class="card cp-card">
                        <div class="card-body">
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
                                <div class="table-responsive">
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

                            <div id="message_wrp"></div>

                            <template x-if="complete=='0' || complete=='1'">
                                <div class="sp-actionbar">
                                    <div class="sp-actionbar__meta">Final step. Save this draft for later or submit it for approval when the package is complete.</div>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        <template x-if="approved==0">
                                            <a href="{{ route('companies.cancel-registration', $company->uuid) }}"
                                                class="btn btn-danger cancel text-medium">Cancel</a>
                                        </template>

                                        <template x-if="approved==0 || approved==99">
                                            <a href="{{ route('companies.registration', $company->uuid) }}"
                                                class="btn btn-outline-primary text-medium">Edit</a>
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
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
@endsection

@section('script')
    @include('companies.create.alpinejs')
@endsection
