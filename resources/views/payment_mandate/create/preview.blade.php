@extends('layouts.master')

@section('title')
    Summary of Payment Mandate
@endsection

@section('content')
    @include('payment_mandate.create.partials.styles')

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

    <div class="pmc-shell" x-data="initData();">
        <div class="card pmc-hero mb-4">
            <div class="card-body">
                <div class="pmc-kicker">New Mandate Creation</div>
                <div class="pmc-title">Step 3: Review and Submit</div>
                @include('payment_mandate.create.partials.stepper', ['currentStep' => 3])
            </div>
        </div>

        <div class="card pmc-card">
            <div class="card-body">
                <form @submit.prevent="" autocomplete="off">
                    <section>
                        <div id="message_wrp" class="message_wrp"></div>

                        <div class="pmc-section">
                            <div class="pmc-section__title">Deduction Channel</div>
                            <div class="table-responsive pmc-table">
                                <table class="table table-bordered mb-0">
                                    <tr>
                                        <th width="25%">Payment Channel</th>
                                        <td width="25%">{{ $customer_account->accountType->name ?? '' }}</td>
                                        <th width="25%">Bank/MNO Name</th>
                                        <td width="25%">{{ $customer_account->account->name ?? '' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Bank Code</th>
                                        <td>{{ $customer_account->bank_code ?? '' }}</td>
                                        <th>Name of Account Holder</th>
                                        <td>{{ $customer_account->account_name ?? '' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Account Number</th>
                                        <td>{{ mask_account_number($customer_account->account_number ?? '') }}</td>
                                        <th>Phone (Connected to Bank)</th>
                                        <td>{{ $customer_account->phone ?? '' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Secondary Phone</th>
                                        <td colspan="3">{{ $debit_order->customerAccount->secondary_phone ?? '' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="pmc-section">
                            <div class="pmc-section__title">Customer Deduction Information</div>
                            <div class="table-responsive pmc-table">
                                <table class="table table-bordered mb-0">
                                    <tr>
                                        <th>Service Provider</th>
                                        <td>{{ $debit_order->partner->business_name ?? '' }}</td>
                                        <th>Mandate Reference</th>
                                        <td>{{ $debit_order->reference ?? '' }}</td>
                                    </tr>
                                    <tr>
                                        <th width="25%">Total Amount</th>
                                        <td width="25%">{{ number_format($debit_order->total_amount) ?? '' }}</td>
                                        <th>Debit Type</th>
                                        <td>{{ $debit_order->debit_type ?? '' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Installment Amount</th>
                                        <td>
                                            @if ($debit_order->debit_type == 'FIXED')
                                                {{ number_format($debit_order->installment_amount) ?? '' }}
                                            @elseif($debit_order->debit_type == 'DYNAMIC')
                                                Min: {{ number_format($debit_order->min_installment_amount) ?? '' }}<br />
                                                Max: {{ number_format($debit_order->max_installment_amount) ?? '' }}
                                            @endif
                                        </td>
                                        <th>Peridiocity</th>
                                        <td>{{ $debit_order->periodicity }}</td>
                                    </tr>
                                    <tr>
                                        <th>Maximum Number of Deduction</th>
                                        <td>{{ $debit_order->installment_number ?? '' }}</td>
                                        <th>Evidence of Aggrement</th>
                                        <td>
                                            @if ($debit_order->aggrement_evidence != null)
                                                <a download="" href="{{ asset('assets/uploads/pdf/' . $debit_order->aggrement_evidence) }}">
                                                    {{ $debit_order->aggrement_evidence }}
                                                </a>
                                            @else
                                                NIL
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Deduction Start Date</th>
                                        <td>{{ date('d-m-Y', strtotime($debit_order->start_date)) }}</td>
                                        <th>Deduction End Date</th>
                                        <td>{{ date('d-m-Y', strtotime($debit_order->end_date)) }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <template x-if="canVerifyOtp()">
                            <div class="pmc-actions" style="margin-top:0; margin-bottom:0.75rem;">
                                <button type="button" name="verify" x-on:click="verifyButton();" class="btn btn-primary">
                                    Verify Otp
                                </button>
                            </div>
                        </template>

                        <template x-if="canSaveOrSubmit()">
                            <div class="pmc-actions">
                                <a href="{{ route('payment-mandate.cancel', $debit_order->uuid) }}" class="btn btn-danger">Cancel</a>
                                <a href="{{ route('payment-mandate.create-account', [$customer_account->uuid, $debit_order->uuid]) }}" class="btn btn-info">Edit</a>
                                <button type="submit" name="save" class="btn btn-secondary" x-text="buttonSave" :disabled="saveLoading" x-on:click="saveMandateForm();"></button>
                                <button type="submit" name="submit" class="btn btn-success" x-html="buttonSubmit" :disabled="submitLoading" x-on:click="submitMandateForm();"></button>
                            </div>
                        </template>
                    </section>
                </form>
            </div>
        </div>

        @include('payment_mandate.modals.verify_otp_modal')
    </div>
@endsection

@section('script')
    @include('payment_mandate.create.alpinejs')
@endsection
