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

    <div class="row" x-data="initData();">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="stepwizard mb-4">
                        <div class="stepwizard-row setup-panel">
                            <div class="stepwizard-step">
                                <a href="#step-1" type="button" class="btn btn-success btn-circle text-white"
                                    disabled="disabled">1</a>
                                <span class="text-success">Deduction Channel</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-success btn-circle text-white"
                                    disabled="disabled">2</a>
                                <span class="text-success">Customer Deduction Information</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-3" type="button" class="btn btn-success btn-circle text-white"
                                    disabled="disabled">3</a>
                                <span class="text-success">Confirmation</span>
                            </div>
                        </div>
                    </div>


                    <form @submit.prevent="">
                        <input type="hidden" name="mandate_id" x-model="formData.mandate_id">
                        <section>
                            <div id="message_wrp" class=""></div>

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
                                            <th>Name of Account Holder</th>
                                            <td>
                                                <span>{{ $customer_account->account_name ?? '' }}</span>
                                            </td>

                                            <th>Account Number</th>
                                            <td>
                                                <span>{{ $customer_account->account_number ?? '' }}</span>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th>Phone (Connected to Bank)</th>
                                            <td>
                                                <span>{{ $customer_account->phone ?? '' }}</span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!--./row -->
                            <div class="row">
                                <div class="col-lg-12">
                                    <table class="table table-bordered">
                                        <tr>
                                            <td colspan="2" class="font-weight-600 text-uppercase">
                                                Customer Deduction Information
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
                                                    @if ($debit_order->evidence_of_aggrement != null)
                                                        <a download=""
                                                            href="{{ asset('assets/uploads/docs/' . $debit_order->evidence_of_aggrement) }}">
                                                            {{ $debit_order->evidence_of_aggrement }}
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

                            <template x-if="status=='submitted'">
                                <div class="row">
                                    <x-otp-verification-input :uuid="$debit_order->uuid" action="MandateApproval"
                                        :url="route('payment-mandate.verify-otp')" />
                                </div>
                            </template>

                            <template x-if="status=='saved'">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3 text-sm-end d-none d-sm-block">
                                            <a href="{{ route('payment-mandate.cancel', $debit_order->uuid) }}"
                                                class="btn btn-danger text-medium">
                                                Cancel
                                            </a>
                                            <a href="{{ route('payment-mandate.create-account', [$customer_account->uuid, $debit_order->uuid]) }}"
                                                class="btn btn-info text-medium">
                                                Edit
                                            </a>
                                            <button type="submit" name="save" class="btn btn-secondary"
                                                x-text="buttonSave" :disabled="saveLoading" x-on:click="savingForm();">
                                            </button>
                                            <button type="submit" name="submit" class="btn btn-success"
                                                x-text="buttonSubmit" :disabled="submitLoading" x-on:click="submitForm();">
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
    @include('payment_mandate.create.confirm_modal')
@endsection

@section('script')
    <script type="text/javascript">
        //initiate data
        function initData() {
            return {
                uuid: "{{ $debit_order->uuid }}",
                status: "{{ $debit_order->status }}",

                formData: {
                    mandate_id: "{{ $debit_order->id }}",
                    uuid: "{{ $debit_order->uuid }}",
                },

                verifyForm: {
                    uuid: "{{ $debit_order->uuid }}",
                    otpPin: "",
                },

                formMessage: "",
                saveLoading: false,
                buttonSave: "Save",

                submitLoading: false,
                buttonSubmit: "Submit",

                verifyLoading: false,
                buttonVerify: "Verify",

                //submit form
                submitForm() {
                    this.formMessage = "";
                    this.submitLoading = false;
                    this.buttonSubmit = "Submitting...";

                    fetch("/payment-mandate/confirm", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify(this.formData),
                        }).then(response => response.json())
                        .then(responseJson => {
                            console.log(responseJson);
                            //change status
                            this.status = "submitted";

                            //show confirmation modal
                            // $('#confirmModal').modal('show');

                            //display message
                            $('#message_wrp').html(
                                "<div class='alert alert-success rounded-sm'>Payment mandate successfully created, Waiting for consent from customer</div>"
                            );
                        }).catch(() => {
                            this.formMessage = "Something went wrong.";
                        }).finally(() => {
                            this.submitLoading = false;
                            this.buttonSubmit = "Submit";
                        });
                },

                //saving Form
                savingForm() {
                    this.formMessage = "";
                    this.saveLoading = false;
                    this.buttonSave = "Saving...";

                    fetch("/payment-mandate/saving", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify(this.formData),
                        }).then(response => response.json())
                        .then(responseJson => {
                            console.log(responseJson);
                            // change status
                            this.status = "saved";

                            // display message
                            $('#message_wrp').html(
                                "<div class='alert alert-success rounded-sm'>Debit Mandate saved.</div>");
                        }).catch(() => {
                            this.formMessage = "Something went wrong.";
                        }).finally(() => {
                            this.saveLoading = false;
                            this.buttonSave = "Save";
                        });
                },

                //resend OTP
                resendOtp() {
                    //api for action
                    fetch("/payment-mandate/resend-otp", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify(this.formData),
                        }).then(response => response.json())
                        .then(responseJson => {
                            console.log(responseJson);

                            //display message
                            $('#message_wrp').html(
                                "<div class='alert alert-success rounded-sm'>OTP request initiated</div>"
                            );
                        }).catch(() => {
                            this.formMessage = "Something went wrong.";
                        }).finally(() => {

                        });
                },

                //verify OTP
                verifyOtp() {
                    console.log("== verifying Otp ==")
                    console.log(this.verifyForm)

                    this.formMessage = "";
                    this.verifyLoading = true;
                    this.buttonVerify = "Verifying...";

                    //api for action
                    fetch("/payment-mandate/verify-otp", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify(this.verifyForm),
                        }).then(response => response.json())
                        .then(responseJson => {
                            console.log(responseJson);

                            //display message
                            $('#message_wrp').html(
                                "<div class='alert alert-success rounded-sm'>OTP verification initiated</div>"
                            );
                        }).catch(() => {
                            this.formMessage = "Something went wrong.";
                        }).finally(() => {
                            this.verifyLoading = false;
                            this.buttonVerify = "Verify";
                        });
                },
            }
        }
    </script>
@endsection
