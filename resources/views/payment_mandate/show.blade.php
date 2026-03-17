@extends('layouts.master')

@section('title')
    Summary of Payment Mandate
@endsection

@section('content')
    <style>
        .pm-show {
            --pm-primary: #153567;
            --pm-border: #dce6f5;
            --pm-muted: #6b7c93;
        }

        .pm-hero,
        .pm-card {
            border: 1px solid var(--pm-border);
            border-radius: 18px;
            box-shadow: 0 16px 40px rgba(29, 53, 87, 0.08);
        }

        .pm-hero {
            background: linear-gradient(135deg, #153567 0%, #224b88 100%);
            color: #fff;
            overflow: visible;
            position: relative;
        }

        .pm-hero::after {
            content: '';
            position: absolute;
            inset: auto -40px -70px auto;
            width: 220px;
            height: 220px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .pm-hero__label {
            font-size: 0.8rem;
            letter-spacing: 0.06em;
            opacity: 0.8;
            text-transform: uppercase;
        }

        .pm-hero__title {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .pm-hero__meta {
            font-size: 0.92rem;
            opacity: 0.9;
        }

        .pm-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #fff;
            border-radius: 999px;
            padding: 0.45rem 0.85rem;
            font-size: 0.82rem;
            font-weight: 600;
            margin-right: 0.45rem;
            margin-top: 0.45rem;
        }

        .pm-card {
            background: #fff;
        }

        .pm-card .card-body {
            padding: 1.2rem;
        }

        .pm-card__title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--pm-primary);
            margin-bottom: 0.2rem;
        }

        .pm-card__hint {
            color: var(--pm-muted);
            font-size: 0.86rem;
            margin-bottom: 1rem;
        }

        .pm-kpi {
            border: 1px solid var(--pm-border);
            border-radius: 16px;
            background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
            padding: 1rem;
            height: 100%;
        }

        .pm-kpi__label {
            color: var(--pm-muted);
            font-size: 0.76rem;
            letter-spacing: 0.04em;
            margin-bottom: 0.35rem;
            text-transform: uppercase;
        }

        .pm-kpi__value {
            color: var(--pm-primary);
            font-size: 1.3rem;
            font-weight: 700;
        }

        .pm-kpi__value.text-success {
            color: #198754 !important;
        }

        .pm-kpi__value.text-warning {
            color: #d97706 !important;
        }

        .pm-kpi__value.text-danger {
            color: #dc3545 !important;
        }

        .pm-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.9rem 1rem;
        }

        .pm-grid__item {
            border-bottom: 1px dashed #e5ebf5;
            padding-bottom: 0.65rem;
        }

        .pm-grid__label {
            color: var(--pm-muted);
            font-size: 0.76rem;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
        }

        .pm-grid__value {
            color: #243b5a;
            font-weight: 600;
            word-break: break-word;
        }

        .pm-table {
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #dfe8f4;
            margin-bottom: 0;
        }

        .pm-table thead th {
            background: #edf4ff;
            color: var(--pm-primary);
            font-weight: 700;
            border-color: #dce7fa;
        }

        .pm-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            justify-content: flex-end;
            position: relative;
            z-index: 3;
        }

        @media (max-width: 991px) {
            .pm-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .pm-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 575px) {
            .pm-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

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
                    <div class="col-lg-8">
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

                    <div class="col-lg-4 mt-3 mt-lg-0">
                        <div class="pm-actions">
                            <div class="btn-group">
                                <button class="btn btn-light btn-sm" type="button">Mandate Actions</button>
                                <button type="button" class="btn btn-sm btn-light dropdown-toggle dropdown-toggle-split"
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
                </div>
            </div>
        </div>

        <div id="message_wrp" class="mb-3"></div>

        <div class="row g-3 mb-4">
            <div class="col-lg-2 col-md-4 col-6">
                <div class="pm-kpi">
                    <div class="pm-kpi__label">Installments</div>
                    <div class="pm-kpi__value">{{ number_format($installmentStats['total'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="pm-kpi">
                    <div class="pm-kpi__label">Paid</div>
                    <div class="pm-kpi__value text-success">{{ number_format($installmentStats['paid'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="pm-kpi">
                    <div class="pm-kpi__label">Partial</div>
                    <div class="pm-kpi__value text-warning">{{ number_format($installmentStats['partial'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="pm-kpi">
                    <div class="pm-kpi__label">Unpaid</div>
                    <div class="pm-kpi__value text-danger">{{ number_format($installmentStats['unpaid'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 col-6">
                <div class="pm-kpi">
                    <div class="pm-kpi__label">Paid Amount</div>
                    <div class="pm-kpi__value">{{ number_format($installmentStats['paid_amount'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 col-6">
                <div class="pm-kpi">
                    <div class="pm-kpi__label">Outstanding</div>
                    <div class="pm-kpi__value">{{ number_format($installmentStats['outstanding_amount'] ?? 0) }}</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-12">
                <div class="card pm-card">
                    <div class="card-body">
                        <div class="pm-card__title">Mandate Details</div>
                        <div class="pm-card__hint">Customer, mandate terms, and operational metadata.</div>

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

                        <div class="table-responsive pm-table">
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
