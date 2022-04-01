@extends('layouts.master')

@section('title')
    Payment Mandate Overview
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Payment Mandate
        @endslot
        @slot('title')
            Summary of Payment Mandate
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    @if (session()->get('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div><br />
                    @elseif(session()->get('danger'))
                        <div class="alert alert-danger">
                            {{ session()->get('danger') }}
                        </div><br />
                    @endif

                    <div class="row mt-4">
                        <div class="col-lg-12">
                            <h5 class="text-capitalize">Deduction Channel</h5>
                            <hr />

                            {{-- <span class="text-sm-end d-none d-sm-block">
                                <a href="#" title="Send remainder sms"
                                    class="btn btn-secondary btn-sm waves-effect btn-label waves-light">
                                    Send SMS <i class="bx bx bx-comment label-icon"></i>
                                </a>
                            </span> --}}
                        </div>
                        <!--./col -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="col-lg-12">
                            <table class="table table-stripped table-bordered">
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
                                    <th width=25%>Name of Account Holder</th>
                                    <td width=25%>
                                        <span>{{ $debit_order->customerAccount->account_name ?? '' }}</span>
                                    </td>

                                    <th>Account Number</th>
                                    <td>
                                        <span>{{ $debit_order->customerAccount->account_number ?? '' }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Phone <small>(Connected to Bank)</small></th>
                                    <td colspan="4">
                                        <span>{{ $debit_order->customerAccount->phone ?? '' }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <th width=25%>Status</th>
                                    <td width=25%>
                                        <span>{{ $debit_order->customerAccount->status ?? '' }}</span>
                                    </td>

                                    <th>Remarks</th>
                                    <td>
                                        <span>{{ $debit_order->customerAccount->remarks ?? '' }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <!--./col -->
                    </div>
                    <!--./row -->

                    <div class="row mt-4">
                        <div class="col-lg-12">
                            <h5 class="text-capitalize">Customer Deduction Details</h5>
                            <hr />
                        </div>
                        <!--./col -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="col-lg-12">
                            @if (isset($debit_order) && $debit_order)
                                <table class="table table-bordered" id="dynamicTable">
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="12%">Loan Amount</th>
                                        <th width="10%">Debit Type</th>
                                        <th width="10%">Installment Amount</th>
                                        <th width="8%">Peridiocity</th>
                                        <th width="10%">Number of Deduction</th>
                                        <th width="9%">Deduction <br />Start Date</th>
                                        <th width="9%">Deduction <br />End Date</th>
                                        <th width="10%">Evidence of Aggrement</th>
                                        <th width="8%">Consent Status</th>
                                        <th style="width: 40px;">Actions</th>
                                    </tr>

                                    <tr>
                                        <td>1</td>
                                        <td>{{ number_format($debit_order->total_amount) ?? '' }}</td>
                                        <td>{{ $debit_order->debit_type ?? '' }}</td>
                                        <td>
                                            @if ($debit_order->debit_type == 'FIXED')
                                                {{ number_format($debit_order->installment_amount) ?? '' }}
                                            @elseif($debit_order->debit_type == 'DYNAMIC')
                                                Min:
                                                {{ number_format($debit_order->min_installment_amount) ?? '' }}<br />
                                                Max: {{ number_format($debit_order->max_installment_amount) ?? '' }}
                                            @endif
                                        </td>
                                        <td>{{ $debit_order->periodicity }}</td>
                                        <td>{{ $debit_order->installment_number ?? '' }}</td>
                                        <td>{{ date('d-m-Y', strtotime($debit_order->start_date)) }}</td>
                                        <td>{{ date('d-m-Y', strtotime($debit_order->end_date)) }}</td>
                                        <td>
                                            @if ($debit_order->evidence_of_aggrement != null)
                                                <a download=""
                                                    href="{{ asset('assets/uploads/docs' . $debit_order->evidence_of_aggrement) }}">
                                                    {{ $debit_order->evidence_of_aggrement }}
                                                </a>
                                            @else
                                                NIL
                                            @endif
                                        </td>
                                        <td>
                                            @if ($debit_order->approved == 0)
                                                <span class="badge badge-pill badge-soft-danger">Pending</span>
                                            @elseif($debit_order->approved == 1)
                                                <span class="badge badge-pill badge-soft-success">Approved</span>
                                            @elseif($debit_order->approved == 99)
                                                <span class="badge badge-pill badge-soft-danger">Rejected</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($debit_order->approved == 0)
                                                <a href="#" title="Edit Mandate">
                                                    <i class="bx bx-edit-alt bx-border bx-xs"></i>
                                                </a>
                                            @endif

                                            <a href="#" title="Send Remainder">
                                                <i class="bx bx bx-comment bx-border bx-xs"></i>
                                            </a>

                                            <a href="#" title="Delete" class="delete">
                                                <i class="bx bx-trash bx-border bx-xs text-danger"></i>
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </div>
                        <!--./col -->
                    </div>
                    <!--./row -->

                </div>
                <!--./card-body -->
            </div>
            <!--./card-->
        </div>
        <!--./col -->
    </div>
    <!-- end row -->
@endsection
