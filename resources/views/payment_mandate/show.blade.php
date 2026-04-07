@extends('layouts.master')

@section('title')
    Summary of Payment Mandate
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/payment-mandate-show.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="pm-show" x-data="initData()">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Summary of Payment Mandate</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('payment-mandate.lists') }}">Payment Mandates</a></li>
                            <li class="breadcrumb-item active">Summary</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card pm-hero mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-lg-12 col-12">
                        <div class="pm-hero__label">Mandate Overview</div>
                        <div class="pm-hero__title">{{ $debit_order->reference ?? 'NIL' }}</div>
                        <div class="pm-hero__meta">{{ $debit_order->partner->business_name ?? 'NIL' }} | {{ $debit_order->customerAccount->account_name ?? 'NIL' }}</div>

                        <div class="mt-2">
                            <span class="pm-chip">{{ $debit_order->customerAccount->account->name ?? 'NIL' }}</span>
                            <span class="pm-chip">{{ $debit_order->periodicity ?? 'NIL' }}</span>
                            <span class="pm-chip">{{ $debit_order->debit_type ?? 'NIL' }}</span>
                            <span class="pm-chip">{!! strip_tags(mandate_status_badge($debit_order)) !!}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="message_wrp" class="mb-3"></div>

        <div class="row g-3 mb-4">
            <div class="col-lg-2 col-md-4 col-6">
                <div class="detail-summary bg-primary shadow h-96 py-2">
                    <div class="detail-summary__label">Installments</div>
                    <div class="detail-summary__value">{{ number_format($installmentStats['total'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="detail-summary bg-success shadow h-96 py-2">
                    <div class="detail-summary__label">Paid</div>
                    <div class="detail-summary__value">{{ number_format($installmentStats['paid'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="detail-summary bg-warning shadow h-96 py-2">
                    <div class="detail-summary__label">Partial</div>
                    <div class="detail-summary__value">{{ number_format($installmentStats['partial'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="detail-summary bg-danger-1 shadow h-96 py-2">
                    <div class="detail-summary__label">Unpaid</div>
                    <div class="detail-summary__value">{{ number_format($installmentStats['unpaid'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 col-6">
                <div class="detail-summary bg-success shadow h-96 py-2">
                    <div class="detail-summary__label">Paid Amount</div>
                    <div class="detail-summary__value">{{ number_format($installmentStats['paid_amount'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 col-6">
                <div class="detail-summary bg-danger shadow h-96 py-2">
                    <div class="detail-summary__label">Outstanding</div>
                    <div class="detail-summary__value">{{ number_format($installmentStats['outstanding_amount'] ?? 0) }}</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-12">
                <div class="card pm-card">
                    <div class="card-body">
                        <div class="pm-card__head">
                            <div>
                                <div class="pm-card__title">Mandate Details</div>
                                <div class="pm-card__hint">Customer, mandate terms, and operational metadata.</div>
                            </div>

                            <div class="pm-actions">
                                <div class="btn-group">
                                    <button class="btn btn-primary btn-sm" type="button">Mandate Actions</button>
                                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="mdi mdi-chevron-down font-size-14"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item text-sm"
                                            href="{{ route('payment-mandate.create-deduction', [$debit_order->customerAccount->uuid]) }}">
                                            Create New Mandate
                                        </a>
                                        <template x-if="canEdit()">
                                            <a class="dropdown-item text-sm"
                                                href="{{ route('payment-mandate.create-account', [$debit_order->customerAccount->uuid, $debit_order->uuid]) }}">
                                                Edit Mandate
                                            </a>
                                        </template>
                                        <template x-if="canArchive()">
                                            <a class="dropdown-item text-sm" x-on:click="archiveMandate();" href="javascript:void(0);">
                                                Archive Mandate
                                            </a>
                                        </template>
                                        <template x-if="canCancel()">
                                            <a class="dropdown-item text-sm" x-on:click="openMandateCancellationModal();" href="javascript:void(0);">
                                                Cancel Mandate
                                            </a>
                                        </template>
                                    </div>
                                </div>

                                <template x-if="canVerifyOtp()">
                                    <button x-html="verifyTxt" title="Verify OTP" class="btn btn-outline-light btn-sm"
                                        x-on:click="verifyButton();"></button>
                                </template>

                                <template x-if="canFetchStatus()">
                                    <button x-html="btnStatus" title="Get Mandate Status" class="btn btn-outline-light btn-sm"
                                        x-on:click="fetchMandateStatus();"></button>
                                </template>
                            </div>
                        </div>

                        <div class="pm-grid">
                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Lifecycle Status</div>
                                <div class="pm-grid__value"><span x-html="lifecycleBadgeHtml()"></span></div>
                            </div>
                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Application Date</div>
                                <div class="pm-grid__value">{{ date('M d, Y H:i', strtotime($debit_order->created_at)) }}</div>
                            </div>
                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Service Provider</div>
                                <div class="pm-grid__value">{{ $debit_order->partner->business_name ?? 'NIL' }}</div>
                            </div>
                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Created By</div>
                                <div class="pm-grid__value">{{ $debit_order->userCreated->name ?? 'NIL' }}</div>
                            </div>

                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Payment Channel</div>
                                <div class="pm-grid__value">{{ $debit_order->customerAccount->accountType->name ?? 'NIL' }}</div>
                            </div>
                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Bank/MNO</div>
                                <div class="pm-grid__value">{{ $debit_order->customerAccount->account->name ?? 'NIL' }}</div>
                            </div>
                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Account Holder</div>
                                <div class="pm-grid__value">{{ $debit_order->customerAccount->account_name ?? 'NIL' }}</div>
                            </div>
                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Account Number</div>
                                <div class="pm-grid__value">{{ mask_account_number($debit_order->customerAccount->account_number ?? '') ?: 'NIL' }}</div>
                            </div>

                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Primary Phone</div>
                                <div class="pm-grid__value">{{ $debit_order->customerAccount->phone ?? 'NIL' }}</div>
                            </div>
                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Secondary Phone</div>
                                <div class="pm-grid__value">{{ $debit_order->customerAccount->secondary_phone ?? 'NIL' }}</div>
                            </div>
                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Debit Type</div>
                                <div class="pm-grid__value">{{ $debit_order->debit_type ?? 'NIL' }}</div>
                            </div>
                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Periodicity</div>
                                <div class="pm-grid__value">{{ $debit_order->periodicity ?? 'NIL' }}</div>
                            </div>

                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Total Amount</div>
                                <div class="pm-grid__value">{{ number_format($debit_order->total_amount ?? 0) }}</div>
                            </div>
                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Installment Amount</div>
                                <div class="pm-grid__value">
                                    @if ($debit_order->debit_type == 'FIXED')
                                        {{ number_format($debit_order->installment_amount ?? 0) }}
                                    @else
                                        Min {{ number_format($debit_order->min_installment_amount ?? 0) }} / Max
                                        {{ number_format($debit_order->max_installment_amount ?? 0) }}
                                    @endif
                                </div>
                            </div>
                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Max Installments</div>
                                <div class="pm-grid__value">{{ number_format($debit_order->installment_number ?? 0) }}</div>
                            </div>
                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Next Due Date</div>
                                <div class="pm-grid__value">
                                    {{ !empty($installmentStats['next_due_date']) ? date('d-m-Y', strtotime($installmentStats['next_due_date'])) : 'NIL' }}
                                </div>
                            </div>

                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Deduction Start Date</div>
                                <div class="pm-grid__value">{{ date('d-m-Y', strtotime($debit_order->start_date)) }}</div>
                            </div>
                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Deduction End Date</div>
                                <div class="pm-grid__value">{{ date('d-m-Y', strtotime($debit_order->end_date)) }}</div>
                            </div>
                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Evidence of Agreement</div>
                                <div class="pm-grid__value">
                                    @if ($debit_order->aggrement_evidence)
                                        <a download href="{{ asset('assets/uploads/pdf/' . $debit_order->aggrement_evidence) }}">
                                            {{ $debit_order->aggrement_evidence }}
                                        </a>
                                    @else
                                        NIL
                                    @endif
                                </div>
                            </div>
                            <div class="pm-grid__item">
                                <div class="pm-grid__label">Remarks</div>
                                <div class="pm-grid__value" x-text="remarks"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card pm-card">
                    <div class="card-body">
                        <div class="pm-card__title">Deduction History</div>
                        <div class="pm-card__hint">Expected deduction dates and realized collection outcomes per installment.</div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Deduction Date</th>
                                        <th>Collection Date</th>
                                        <th>Installment Amt</th>
                                        <th>Paid Amt</th>
                                        <th>Outstanding Amt</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($installments as $val)
                                        @php
                                            $status = strtoupper($val->status ?? 'OPEN');
                                            $isPaid = in_array($status, ['CLOSED', 'SUCCESS']);
                                            $isPartial = $status === 'PARTIAL_PAID';
                                            $isUnpaid = in_array($status, ['OPEN', 'FAILED', 'FUNDSCHECK']);
                                        @endphp
                                        <tr>
                                            <td>{{ date('d-m-Y', strtotime($val->payment_date)) }}</td>
                                            <td>
                                                @if ($val->effective_payment_date)
                                                    {{ date('d-m-Y', strtotime($val->effective_payment_date)) }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ number_format($val->installment_amount ?? 0) }}</td>
                                            <td>{{ number_format($val->current_balance ?? 0) }}</td>
                                            <td>{{ number_format($val->outstanding_amount ?? 0) }}</td>
                                            <td>
                                                @if ($isPaid)
                                                    <span class="badge badge-pill badge-soft-success">Paid</span>
                                                @elseif($isPartial)
                                                    <span class="badge badge-pill badge-soft-warning">Partial Paid</span>
                                                @elseif($isUnpaid)
                                                    <span class="badge badge-pill badge-soft-danger">Unpaid</span>
                                                @else
                                                    <span class="badge badge-pill badge-soft-secondary">{{ $status }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No deduction history found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('payment_mandate.modals.verify_otp_modal')
        @include('payment_mandate.modals.cancellation_modal')
    </div>
@endsection

@section('script')
    @include('payment_mandate.js_script')
@endsection
