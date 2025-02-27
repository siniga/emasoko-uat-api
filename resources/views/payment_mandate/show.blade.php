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
                                <a href="{{ route('payment-mandate.create-deduction', [$debit_order->customerAccount->uuid]) }}"
                                    title="New Payment Mandate" class="btn btn-primary text-medium btn-sm">
                                    <i class="bx bx-plus font-size-12"></i> Create Mandate
                                </a>

                                <template x-if="status==100 || status==200 || status==403 || status==430 || status==500 || status==501">
                                    <a href="{{ route('payment-mandate.create-account', [$debit_order->customerAccount->uuid, $debit_order->uuid] ) }}" title="Edit"
                                        class="btn btn-secondary text-medium btn-sm">
                                        <i class="bx bx-edit font-size-12"></i> Edit Mandate
                                    </a>
                                </template>

                                <template x-if="deleted==0">
                                    <template x-if="status==100 || status==200 || status==201 || status==249 || status==403 || status==405 || status==430 || status==500 || status==501">
                                        <a href="{{ route('payment-mandate.archive', $debit_order->uuid) }}" title="Archive"
                                            class="btn btn-warning text-medium btn-sm archive">
                                            <i class="bx bx-archive-in font-size-12"></i> Archive Mandate
                                        </a>
                                    </template>
                                </template>

                                <template x-if="status==100 || status==200">
                                    <a href="{{ route('payment-mandate.delete', $debit_order->uuid) }}" title="Delete"
                                        class="btn btn-outline-danger text-medium btn-sm delete">
                                        <i class="bx bx-trash font-size-12"></i> Delete Mandate
                                    </a>
                                </template>

                                <template x-if="status==201 || status==202 || status==240 || status==249 || status==405 || status==500">
                                    <button x-html="btnCancel" title="Cancel Mandate" class="btn btn-outline-danger text-medium btn-sm btn-sm"
                                        x-on:click="openMandateCancellationModal();"></button>
                                </template>

                                <template x-if="status==201 || status==249 || status==430 || status==500">
                                    <button x-html="verifyTXT" title="Verify OTP" class="btn btn-outline-primary text-medium btn-sm btn-sm"
                                        x-on:click="verifyButton();"></button>
                                </template>

                                <template x-if="status==201 || status==202 || status==249 || status==430 || status==501 ">
                                    <button x-html="btnStatus" title="Get Mandate Status" class="btn btn-outline-success btn-sm"
                                        x-on:click="fetchMandateStatus();"></button>
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
                                        <td width="25%">{{ date('M d, Y H:i', strtotime($debit_order->created_at)) }}</td>

                                        <th width="25%">Mandate Status</th>
                                        <td width="25%">
                                            <template x-if="status==0">
                                                <span class="badge badge-pill badge-soft-warning">Registration Process</span>
                                            </template>

                                            <template x-if="status==100">
                                                <span class="badge badge-pill badge-soft-warning">Pending Registration</span>
                                            </template>

                                            <template x-if="status==200">
                                                <span class="badge badge-pill badge-soft-info">Under Review</span>
                                            </template>

                                            <template x-if="status==201">
                                                <span class="badge badge-pill badge-soft-info">Pending Verification</span>
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
                                                <span class="badge badge-pill badge-soft-danger">Pending Registration</span>
                                            </template>

                                            <template x-if="status==404">
                                                <span class="badge badge-pill badge-soft-danger">Pending Registration</span>
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

                                            <template x-if="status==999">
                                                <span class="badge badge-pill badge-soft-danger">Canceled</span>
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
                                        <th>Paid Installment</th>
                                        <td>0</td>
                                        <th>UnPaid Installment</th>
                                        <td>{{ $debit_order->installment_number ?? '' }}</td>
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
                                            <span>{{ $debit_order->userCreated->name }}</span>
                                        </td>
                                    </tr>
                                </table>

                                <div class="table-responsive">
                                    <table class="table table-nowrap align-middle table-bordered">
                                        <thead>
                                            <tr>
                                                <td colspan="4" class="font-weight-600 text-uppercase">
                                                    Deduction History
                                                </td>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr>
                                                @foreach($debit_order->installments as $val)
                                                    <td class="text-center">{{ date('d-m', strtotime($val->payment_date)) }}</td>
                                                @endforeach
                                            </tr>

                                             <tr>
                                                @foreach($debit_order->installments as $val)
                                                    <td class="text-center">
                                                        @if($val->status == "OPEN" || $val->status == "FAILED")
                                                            <i class='bx bxs-x-square bx-sm text-danger'></i>
                                                        @elseif($val->status == "CLOSED" || $val->status == "SUCCESS")
                                                            <i class='bx bxs-check-square bx-sm text-success'></i>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
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
    <script type="text/javascript">
        //initiate data
        function initData() {
            return {
                sidebarOpen: false,
                eModal: "Mandate_Information",
                status: "{{ $debit_order->status }}",
                deleted: "{{ $debit_order->deleted }}",
                remarks: "{{ $debit_order->remarks }}",
                approved: "{{ $debit_order->approved }}",
                verifyTXT: "<i class='bx bx-check-square'></i> Verify OTP",

                formData: {
                    mandate_id: "{{ $debit_order->id }}",
                    uuid: "{{ $debit_order->uuid }}",
                    'remarks': "",
                },

                resendForm: {
                    uuid: "{{ $debit_order->uuid }}",
                },

                verifyForm: {
                    uuid: "{{ $debit_order->uuid }}",
                    otpPin: "",
                },

                formLoading: false,
                formMessage: "",
                btnCancel: '<i class="fa fa-times"></i> Cancel Mandate',
                btnStatus: '<i class="fa fa-check"></i> Mandate Status',
                buttonSubmit: "Submit",

                verifyLoading: false,
                buttonVerify: "Verify",

                resendTXT: "Resend",

                //approval mandate status
                fetchMandateStatus() {
                    this.formLoading = true;
                    this.btnStatus = '<i class="fas fa-spinner fa-spin"></i> Fetching mandate status....';

                    fetch("/payment-mandate/fetch-status", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify(this.formData),
                        }).then(response => response.json())
                        .then(responseJson => {
                            this.status = responseJson.http_response_code
                            this.remarks = responseJson.success_msg

                            //response code and message
                            let http_response_code = responseJson.http_response_code;
                            let success_msg = responseJson.success_msg;

                            if(http_response_code == 240){
                                $('#message_wrp').html(
                                    "<div class='alert alert-success rounded-sm'>" + success_msg + "</div>"
                                );
                            } else if(http_response_code == 249 || http_response_code == 430){
                                $('#message_wrp').html(
                                    "<div class='alert alert-info rounded-sm'>" + success_msg + "</div>"
                                );
                            } else if(http_response_code == 404){
                                $('#message_wrp').html(
                                    "<div class='alert alert-danger rounded-sm'>" + success_msg + "</div>"
                                );                                
                            } else {
                                $('#message_wrp').html(
                                    "<div class='alert alert-danger rounded-sm'>" + success_msg + "</div>"
                                );                                 
                            }
                        }).catch(() => {
                            $('#message_wrp').html(
                                "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>"
                            );

                        }).finally(() => {
                            this.formLoading = false;
                            this.btnStatus = '<i class="fa fa-check"></i> Mandate Status';
                        });
                },

                //open reject modal
                openMandateCancellationModal() {
                    $('#cancellationModal').modal('show');
                },

                //mandate cancellation
                mandateCancellationForm() {
                    this.formLoading = true;
                    this.btnSubmit = '<i class="fas fa-spinner fa-spin"></i> Cancelling Mandate....';

                    fetch("/payment-mandate/cancel-mandate", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify(this.formData),
                        }).then(response => response.json())
                        .then(responseJson => {
                            //response code 
                            const http_response_code = responseJson.http_response_code;
                            this.status = responseJson.http_response_code
                            this.remarks = responseJson.remarks

                            if(http_response_code == 405){
                                $('#message_wrp_1').html(
                                    "<div class='alert alert-success rounded-sm'>Mandate cancelled.</div>"
                                );
                            } else {
                                $('#message_wrp_1').html(
                                    "<div class='alert alert-warning rounded-sm'>Failed to cancel mandate.</div>"
                                );
                            }  
                        }).catch(() => {
                            $('#message_wrp_1').html(
                                "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>"
                            );
                        }).finally(() => {
                            this.formLoading = false;
                            this.btnSubmit = 'Submit';
                        });
                },

                //verifyButton
                verifyButton() {
                    //display message
                    $('#message_wrp_1').html(
                        "<div class='alert alert-success rounded-sm'>Resend OTP and input the OTP PIN to confirm customer consent.</div>"
                    );

                    //todo: create modal for verifying Otp 
                    $('#verifyOtpModal').modal('show');
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
                            body: JSON.stringify(this.resendForm),
                        }).then(response => response.json())
                        .then(responseJson => {
                            console.log(responseJson);

                            //response code 
                            const http_response_code = responseJson.http_response_code;

                            if(http_response_code == 200){
                                //display message
                                $('#message_wrp_1').html(
                                    "<div class='alert alert-success rounded-sm'>OTP sent successfully</div>"
                                );
                            } else if(http_response_code == 500) {
                                //display message
                                $('#message_wrp_1').html(
                                    "<div class='alert alert-warning rounded-sm'>Failed to send OTP, Try again.</div>"
                                );
                            } else {
                                //display message
                                $('#message_wrp_1').html(
                                    "<div class='alert alert-warning rounded-sm'>Failed to send OTP, Try again.</div>"
                                );
                            }

                        }).catch(() => {
                            this.formMessage = "Something went wrong.";
                        }).finally(() => {

                        });
                },

                //verify OTP
                verifyOtp() {
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

                            //response code 
                            const http_response_code = responseJson.http_response_code;

                            if(http_response_code == 202){
                                //display message
                                $('#message_wrp_1').html(
                                    "<div class='alert alert-success rounded-sm'>OTP validated</div>"
                                );

                                //redirect to details page
                                var redirectionURL = '/payment-mandate/show/' + this.verifyForm.uuid;

                                setTimeout(function () {
                                    window.location.href = redirectionURL
                                }, 2000);
                            } else if(http_response_code == 500) {
                                //display message
                                $('#message_wrp_1').html(
                                    "<div class='alert alert-danger rounded-sm'>Failed to verify OTP, Try again or resend OTP.</div>"
                                );
                            } else {
                                //display message
                                $('#message_wrp_1').html(
                                    "<div class='alert alert-danger rounded-sm'>Failed to verify OTP, Try again or contact support.</div>"
                                );
                            }
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
