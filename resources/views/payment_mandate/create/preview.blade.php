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

    <div x-data="initData();">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="stepwizard mb-4">
                            <div class="stepwizard-row setup-panel">
                                <div class="stepwizard-step bg-success">
                                    <a href="#step-1" type="button" class="btn btn-success btn-circle text-white"
                                        disabled="disabled">1</a>
                                    <span class="text-white">Deduction Channel</span>
                                </div>

                                <div class="stepwizard-step bg-success">
                                    <a href="#step-2" type="button" class="btn btn-success btn-circle text-white"
                                        disabled="disabled">2</a>
                                    <span class="text-white">Customer Deduction Information</span>
                                </div>

                                <div class="stepwizard-step bg-primary">
                                    <a href="#step-3" type="button" class="btn btn-secondary btn-circle"
                                        disabled="disabled">3</a>
                                    <span class="text-white">Deduction Confirmation</span>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="" autocomplete="off">
                            <section>
                                <div id="message_wrp" class="message_wrp"></div>

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
                                                    <span>{{ $customer_account->accountType->name ?? '' }}</span>
                                                </td>

                                                <th width=25%>Bank/MNO Name</th>
                                                <td width=25%>
                                                    <span>{{ $customer_account->account->name ?? '' }}</span>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th>Bank Code</th>
                                                <td>
                                                    <span>{{ $customer_account->bank_code ?? '' }}</span>
                                                </td>

                                                <th>Name of Account Holder</th>
                                                <td>
                                                    <span>{{ $customer_account->account_name ?? '' }}</span>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th>Account Number</th>
                                                <td>
                                                    <span>{{ $customer_account->account_number ?? '' }}</span>
                                                </td>

                                                <th>Phone (Connected to Bank)</th>
                                                <td>
                                                    <span>{{ $customer_account->phone ?? '' }}</span>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th>Secondary Phone</th>
                                                <td colspan="3">{{ $debit_order->customerAccount->secondary_phone ?? '' }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <table class="table table-bordered">
                                            <tr>
                                                <td colspan="2" class="font-weight-600 text-uppercase">
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
                                                <th width=25%>Total Amount</th>
                                                <td width=25%>
                                                    <span>{{ number_format($debit_order->total_amount) ?? '' }}</span>
                                                </td>

                                                <th>Debit Type</th>
                                                <td>
                                                    <span>{{ $debit_order->debit_type ?? '' }}</span>
                                                </td>
                                            </tr>

                                            <tr>
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

                                                <th>Peridiocity</th>
                                                <td>
                                                    <span>{{ $debit_order->periodicity }}</span>
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
                                                <th>Deduction Start Date</th>
                                                <td>
                                                    <span>{{ date('d-m-Y', strtotime($debit_order->start_date)) }}</span>
                                                </td>

                                                <th>Deduction End Date</th>
                                                <td>
                                                    <span>{{ date('d-m-Y', strtotime($debit_order->end_date)) }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <template x-if="status==201 && channel == 'NMB'">
                                    <div class="row">
                                        <div class="col-md-12 d-flex justify-content-end text-sm-end mb-2">
                                            <button type="button" name="verify" x-on:click="verifyButton();" class="btn btn-primary">
                                                Verify Otp
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="status==100 || status==200">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-3 text-sm-end d-sm-block">
                                                <a href="{{ route('payment-mandate.cancel', $debit_order->uuid) }}"
                                                    class="btn btn-danger text-medium">
                                                    Cancel
                                                </a>
                                                <a href="{{ route('payment-mandate.create-account', [$customer_account->uuid, $debit_order->uuid]) }}"
                                                    class="btn btn-info text-medium">
                                                    Edit
                                                </a>
                                                <button type="submit" name="save" class="btn btn-secondary"
                                                    x-text="buttonSave" :disabled="saveLoading" x-on:click="saveMandateForm();">
                                                </button>
                                                <button type="submit" name="submit" class="btn btn-success"
                                                    x-html="buttonSubmit" :disabled="submitLoading" x-on:click="submitMandateForm();">
                                                </button>
                                            </div>
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

    <!-- include modals -->
    @include('payment_mandate.modals.verify_otp_modal')
</div>
@endsection

@section('script')
    @include('payment_mandate.create.alpinejs')
@endsection
