@extends('layouts.master')

<style>
    .table th {
        background-color: #E1F5FE !important;
    }
</style>

@section('title')
    Summary of Payment Mandate
@endsection

@section('content')
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

    <div x-data="initData()">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">

                        <div id="message_wrp" class=""></div>

                        <div class="d-flex justify-content-between mb-2">
                            <div></div>

                            <div class="text-sm-end d-none d-sm-block">
                                <div class="btn-group">
                                    <button class="btn btn-primary btn-sm text-sm" type="button">
                                        Mandate Actions
                                    </button>
                                    <button type="button"
                                        class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="mdi mdi-chevron-down font-size-14"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item text-sm"
                                            href="{{ route('payment-mandate.create-deduction', [$debit_order->customerAccount->uuid]) }}">
                                            Create New Mandate
                                        </a>
                                        <template
                                            x-if="status==100 || status==200 || status==403 || status==430 || status==500 || status==501">
                                            <a class="dropdown-item text-sm"
                                                href="{{ route('payment-mandate.create-account', [$debit_order->customerAccount->uuid, $debit_order->uuid]) }}">
                                                Edit Mandate
                                            </a>
                                        </template>

                                        <template x-if="deleted==0">
                                            <template
                                                x-if="status==100 || status==200 || status==201 || status==249 || status==403 || status==430 || status==405 || status==500 || status==501">
                                                <a class="dropdown-item text-sm" x-on:click="archiveMandate();"
                                                    href="javascript:void(0);">
                                                    Archive Mandate
                                                </a>
                                            </template>
                                        </template>

                                        <template
                                            x-if="status==201 || status==202 || status==240 || status==249 || status==405 || status==403 || status==500">
                                            <a class="dropdown-item text-sm" title="Cancel Mandate"
                                                x-on:click="openMandateCancellationModal();" href="javascript:void(0);">
                                                Cancel Mandate
                                            </a>
                                        </template>

                                        <div class="dropdown-divider"></div>

                                        <!-- <template
                                            x-if="status==201 || status==249 || status==403 || status==430 || status==405 || status==500 || status==501">
                                            <a class="dropdown-item text-sm"
                                                href="{{ route('payment-mandate.delete', $debit_order->uuid) }}">
                                                Delete Mandate
                                            </a>
                                        </template> -->
                                    </div>
                                </div>

                                <template
                                    x-if="(status==201 || status==249 || status==430 || status==500) && channel == 'NMB'">
                                    <button x-html="verifyTxt" title="Verify OTP"
                                        class="btn btn-outline-primary text-medium btn-sm btn-sm"
                                        x-on:click="verifyButton();"></button>
                                </template>

                                <template
                                    x-if="(status==201 || status==202 || status==249 || status==430 || status==501) && channel == 'NMB'">
                                    <button x-html="btnStatus" title="Get Mandate Status"
                                        class="btn btn-outline-success btn-sm" x-on:click="fetchMandateStatus();"></button>
                                </template>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <table class="table table-stripped table-bordered">
                                    <tr>
                                        <td colspan="4" class="font-weight-600 text-uppercase">
                                            Deduction Channel
                                        </td>
                                    </tr>

                                    <tr>
                                        <th width=25%>Payment Channel</th>
                                        <td width=25%>
                                            <span>{{ $debit_order->customerAccount->accountType->name ?? '' }}</span>
                                        </td>

                                        <th>Bank/MNO Name</th>
                                        <td>
                                            <span>{{ $debit_order->customerAccount->account->name ?? '' }}</span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Name of Account Holder</th>
                                        <td>
                                            <span>{{ $debit_order->customerAccount->account_name ?? '' }}</span>
                                        </td>

                                        <th>Account Number</th>
                                        <td>
                                            <span>{{ $debit_order->customerAccount->account_number ?? '' }}</span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Phone (Connected to Bank)</th>
                                        <td>
                                            <span>{{ $debit_order->customerAccount->phone ?? '' }}</span>
                                        </td>

                                        <th>Secondary Phone</th>
                                        <td>{{ $debit_order->customerAccount->secondary_phone ?? '' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <table class="table table-bordered">
                                    <tr>
                                        <td colspan="4" class="font-weight-600 text-uppercase">
                                            Customer Deduction Information
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Service Provider</th>
                                        <td>
                                            <span>{{ $debit_order->partner->business_name ?? '' }}</span>
                                        </td>

                                        <th>Mandate Reference</th>
                                        <td>
                                            <span>{{ $debit_order->reference ?? '' }}</span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th width="25%">Application Date</th>
                                        <td width="25%">{{ date('M d, Y H:i', strtotime($debit_order->created_at)) }}
                                        </td>

                                        <th width="25%">Mandate Status</th>
                                        <td width="25%">
                                            <template x-if="status==0">
                                                <span class="badge badge-pill badge-soft-warning">Registration
                                                    Process</span>
                                            </template>

                                            <template x-if="status==100">
                                                <span class="badge badge-pill badge-soft-warning">Pending
                                                    Registration</span>
                                            </template>

                                            <template x-if="status==200">
                                                <span class="badge badge-pill badge-soft-info">Under Review</span>
                                            </template>

                                            <template x-if="status==201">
                                                <span class="badge badge-pill badge-soft-warning">Pending Verification</span>
                                            </template>

                                            <template x-if="status==202">
                                                <span class="badge badge-pill badge-soft-success">Verified</span>
                                            </template>

                                            <template x-if="status==240">
                                                <span class="badge badge-pill badge-soft-success">Active</span>
                                            </template>

                                            <template x-if="status==249">
                                                <span class="badge badge-pill badge-soft-warning">Processing</span>
                                            </template>

                                            <template x-if="status==403">
                                                <span class="badge badge-pill badge-soft-danger">Pending
                                                    Registration</span>
                                            </template>

                                            <template x-if="status==404">
                                                <span class="badge badge-pill badge-soft-danger">Pending
                                                    Registration</span>
                                            </template>

                                            <template x-if="status==405">
                                                <span class="badge badge-pill badge-soft-danger">Cancelled</span>
                                            </template>

                                            <template x-if="status==500">
                                                <span class="badge badge-pill badge-soft-danger">Failed Verification</span>
                                            </template>

                                            <template x-if="status==501">
                                                <span class="badge badge-pill badge-soft-danger">Inactive</span>
                                            </template>

                                            <template x-if="status==900">
                                                <span class="badge badge-pill badge-soft-success">Closed</span>
                                            </template>

                                            <template x-if="status==999">
                                                <span class="badge badge-pill badge-soft-danger">Cancelled</span>
                                            </template>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th width="25%">Remarks</th>
                                        <td colspan="3">
                                            <span x-text="remarks"></span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Total Amount</th>
                                        <td>{{ number_format($debit_order->total_amount) ?? '' }}</td>

                                        <th>Debit Type</th>
                                        <td>
                                            <span class="font-weight-600">{{ $debit_order->debit_type ?? '' }}</span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Peridiocity</th>
                                        <td>
                                            <span class="font-weight-600">{{ $debit_order->periodicity }}</span>
                                        </td>

                                        <th>Installment Amount</th>
                                        <td>
                                            <span>
                                                @if ($debit_order->debit_type == 'FIXED')
                                                    {{ number_format($debit_order->installment_amount) ?? '' }}
                                                @elseif($debit_order->debit_type == 'DYNAMIC')
                                                    Min:
                                                    {{ number_format($debit_order->min_installment_amount) ?? '' }}<br />
                                                    Max:
                                                    {{ number_format($debit_order->max_installment_amount) ?? '' }}
                                                @endif
                                            </span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Maximum Number of Deduction</th>
                                        <td>
                                            <span>{{ $debit_order->installment_number ?? '' }}</span>
                                        </td>

                                        <th>Evidence of Aggrement</th>
                                        <td>
                                            <span>
                                                @if ($debit_order->aggrement_evidence != null)
                                                    <a download=""
                                                        href="{{ asset('assets/uploads/pdf/' . $debit_order->aggrement_evidence) }}">
                                                        {{ $debit_order->aggrement_evidence }}
                                                    </a>
                                                @else
                                                    NIL
                                                @endif
                                            </span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Paid Installments</th>
                                        <td>{{ $debit_order->paid_installment ?? '' }}</td>
                                        <th>Failed Installments</th>
                                        <td>{{ $debit_order->failed_installment }}</td>
                                    </tr>

                                    <tr>
                                        <th>Deduction Start Date</th>
                                        <td>
                                            <span>{{ date('d-m-Y', strtotime($debit_order->start_date)) }}</span>
                                        </td>

                                        <th>Deduction End Date</th>
                                        <td>
                                            <span>{{ date('d-m-Y', strtotime($debit_order->end_date)) }}</span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Created Date</th>
                                        <td>
                                            <span>{{ date('d-m-Y H:i:s', strtotime($debit_order->created_at)) }}</span>
                                        </td>

                                        <th>Created By</th>
                                        <td>
                                            <span>{{ $debit_order->userCreated->name ?? '' }}</span>
                                        </td>
                                    </tr>
                                </table>

                                <div class="table-responsive">
                                    <table class="table table-nowrap align-middle table-bordered">
                                        <thead>
                                            <tr>
                                                <td colspan="6" class="font-weight-600 text-uppercase">
                                                    Deduction History
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Deduction Date</th>
                                                <th>Collection Date</th>
                                                <th>Installment Amt</th>
                                                <th>PaidAmt</th>
                                                <th>OutstandingAmt</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @php
                                                $installments = $debit_order->installments->sortBy('payment_date')->values();
                                            @endphp

                                            @forelse ($installments as $index => $val)
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
                                                            <span
                                                                class="badge badge-pill badge-soft-warning">Partial
                                                                Paid</span>
                                                        @elseif($isUnpaid)
                                                            <span
                                                                class="badge badge-pill badge-soft-danger">Unpaid</span>
                                                        @else
                                                            <span
                                                                class="badge badge-pill badge-soft-secondary">{{ $status }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">No deduction history
                                                        found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- include modals -->
        @include('payment_mandate.modals.verify_otp_modal')
        @include('payment_mandate.modals.cancellation_modal')
    </div>
@endsection

@section('script')
    @include('payment_mandate.js_script')
@endsection
