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
                                <button x-html="btnCancel" title="Mandate Cancellation" class="btn btn-danger btn-sm"
                                    x-on:click="cancelMandate();"></button>

                                <button x-html="btnStatus" title="Get Mandate Status" class="btn btn-success btn-sm"
                                    x-on:click="fetchMandateStatus();"></button>

                                @if ($debit_order->approved == 0)
                                    <a href="{{ route('payment-mandate.edit', $debit_order->id) }}" title="Edit"
                                        class="btn btn-outline-secondary text-medium btn-sm">
                                        <i class="bx bx-edit font-size-12"></i> Edit
                                    </a>

                                    <a href="{{ route('payment-mandate.delete', $debit_order->id) }}" title="Delete"
                                        class="btn btn-outline-danger text-medium btn-sm delete">
                                        <i class="bx bx-trash font-size-12"></i> Delete
                                    </a>
                                @endif
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

                                        <th>Status</th>
                                        <td>
                                            <span>
                                                @if ($debit_order->customerAccount->status == 'partial')
                                                    <span class="badge bg-info">Partial Approved</span>
                                                @elseif($debit_order->customerAccount->status == 'approved')
                                                    <span class="badge bg-success">Approved</span>
                                                @elseif($debit_order->customerAccount->status == 'rejected')
                                                    <span class="badge bg-danger">Rejected</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>

                                    @if ($debit_order->customerAccount->remarks)
                                        <tr>
                                            <th>Remarks</th>
                                            <td colspan="3">
                                                <span>{{ $debit_order->customerAccount->remarks ?? '' }}</span>
                                            </td>
                                        </tr>
                                    @endif
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
                                        <th width="25%">Application Date</th>
                                        <td width="25%">{{ date('d,M-Y', strtotime($debit_order->created_at)) }}</td>

                                        <th width="25%">Consent Status</th>
                                        <td width="25%">
                                            @if ($debit_order->approved == 'PENDING')
                                                <span class="badge badge-pill badge-soft-danger">Pending</span>
                                            @elseif($debit_order->approved == 'APPROVED')
                                                <span class="badge badge-pill badge-soft-success">Approved</span>
                                            @elseif($debit_order->approved == 'REJECTED')
                                                <span class="badge badge-pill badge-soft-danger">Rejected</span>
                                            @endif
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
                                </table>


                                <table class="table table-nowrap align-middle table-bordered">
                                    <thead>
                                        <tr>
                                            <td colspan="4" class="font-weight-600 text-uppercase">
                                                Deduction History
                                            </td>
                                        </tr>

                                        {{-- <tr>
                                        <th width="3%">#</th>
                                        <th width="20%">Staff Name</th>
                                        <th width="20%">NIDA Number</th>
                                        <th width="25%">Email</th>
                                        <th width="20%">Phone</th>
                                        <th width="20%">Title</th>
                                    </tr> --}}
                                    </thead>


                                </table>

                                <div class="alert alert-danger">No any deduction at the moment!</div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- <div id="offcanvas-wrp"></div> --}}
<!--./offcanvas -->

@section('script')
    <script type="text/javascript">
        //initiate data
        function initData() {
            return {
                sidebarOpen: false,
                eModal: "Mandate_Information",
                status: "{{ $debit_order->status }}",
                approved: "{{ $debit_order->approved }}",

                formData: {
                    mandate_id: "{{ $debit_order->id }}",
                    uuid: "{{ $debit_order->uuid }}",
                },

                formLoading: false,
                formMessage: "",
                btnCancel: '<i class="fa fa-times"></i> Mandate Cancellation',
                btnStatus: '<i class="fa fa-check"></i> Mandate Status',

                //approval mandate status
                fetchMandateStatus(){
                    this.formLoading = true;
                    this.btnStatus = '<i class="fas fa-spinner fa-spin"></i> Fetch Mandate status....';

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
                            console.log(responseJson);
                            this.status = responseJson.status

                            $('#message_wrp').html(
                                "<div class='alert alert-success rounded-sm'>Mandate registration status initiated.</div>"
                            );
                        }).catch(() => {
                            //display message
                            $('#message_wrp').html(
                                "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>"
                            );

                        }).finally(() => {
                            this.formLoading = false;
                            this.btnStatus = '<i class="fa fa-check"></i> Mandate Status';
                        });
                },

                //cancel mandate
                cancelMandate(){
                    this.formLoading = true;
                    this.btnCancel = '<i class="fas fa-spinner fa-spin"></i> Cancelling Mandate....';

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
                            console.log(responseJson);
                            this.status = responseJson.status

                            $('#message_wrp').html(
                                "<div class='alert alert-success rounded-sm'>Mandate cancellation initiated</div>"
                            );
                        }).catch(() => {
                            //display message
                            $('#message_wrp').html(
                                "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>"
                            );

                        }).finally(() => {
                            this.formLoading = false;
                            this.btnCancel = '<i class="fa fa-times"></i> Mandate Cancellation';
                        });
                },

            }
        }
    </script>
@endsection
