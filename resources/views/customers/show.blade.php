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
            Payment Mandate Overview
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

                    <div class="row">
                        <div class="col-lg-12">
                            <h5 class="text-capitalize">Customer Information
                            </h5>
                            <hr />

                            <span class="text-sm-end d-none d-sm-block">
                                <a href="{{ route('customers.edit', $customer->uuid) }}" title="Edit"
                                    class="btn btn-secondary btn-sm waves-effect btn-label waves-light">
                                        <i class="bx bx-pencil label-icon"></i> Edit
                                </a>&nbsp;&nbsp;
                            </span>
                        </div>
                        <!--./col -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="col-lg-12">
                            <table class="table table-stripped table-bordered">
                                <tr>
                                    <th width=25%>Fullname</th>
                                    <td width=25%>
                                        <span>{{ $customer->full_name }}</span>
                                    </td>

                                    <th>Status</th>
                                    <td>
                                        <span>{{ ucfirst($customer->status) }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <th width=25%>Date of Birth</th>
                                    <td width=25%>
                                        <span>{{ $customer->dob }}</span>
                                    </td>

                                    <th>NIDA Number</th>
                                    <td>
                                        <span>{{ $customer->id_number ?? 'NIL' }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <th width=25%>Marital status</th>
                                    <td width=25%>
                                        <span>{{ $customer->maritalStatus->name ?? 'NIL' }}</span>
                                    </td>

                                    <th>Gender</th>
                                    <td>
                                        <span>{{ $customer->gender->name ?? 'NIL' }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <th width=25%>Sector</th>
                                    <td width=25%>
                                        <span>{{ $customer->sector->name ?? 'NIL' }}</span>
                                    </td>

                                    <th>Occupation</th>
                                    <td>
                                        <span>{{ $customer->occupation->name ?? 'NIL' }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <!--./col -->
                    </div>
                    <!--./row-->

                    <div class="row mt-4">
                        <div class="col-lg-12">
                            <h5 class="text-capitalize">Deduction Channel</h5>
                            <hr />
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
                                        <span>{{ $customer->customerAccount->accountType->name ?? '' }}</span>
                                    </td>

                                    <th>Bank/Institution</th>
                                    <td>
                                        <span>{{ $customer->customerAccount->account->name ?? '' }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <th width=25%>Account Name</th>
                                    <td width=25%>
                                        <span>{{ $customer->customerAccount->account_name ?? '' }}</span>
                                    </td>

                                    <th>Account Number</th>
                                    <td>
                                        <span>{{ $customer->customerAccount->account_number ?? '' }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Phone <small>(Linked to Bank Account)</small></th>
                                    <td colspan="4">
                                        <span>{{ $customer->customerAccount->phone ?? '' }}</span>
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
                            @if ($customer->customerAccount->debitOrders)
                                <table class="table table-bordered" id="dynamicTable">
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="12%">Loan Amount</th>
                                        <th width="10%">Debit Type</th>
                                        <th width="10%">Installment Amount</th>
                                        <th width="10%">Peridiocity</th>
                                        <th width="10%">Number of Deduction</th>
                                        <th width="12%">Start Date</th>
                                        <th width="12%">End Date</th>
                                        <th width="10%">Evidence of Aggrement</th>
                                        <th width="8%">Status</th>
                                    </tr>

                                    @foreach ($customer->customerAccount->debitOrders as $val)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ number_format($val->total_amount) ?? '' }}</td>
                                            <td>{{ $val->debit_type ?? '' }}</td>
                                            <td>{{ number_format($val->max_installment_amount) ?? '' }}</td>
                                            <td>{{ $val->periodicity }}</td>
                                            <td>{{ $val->installment_number ?? '' }}</td>
                                            <td>{{ date('d-m-Y', strtotime($val->start_date)) }}</td>
                                            <td>{{ date('d-m-Y', strtotime($val->end_date)) }}</td>
                                            <td>
                                                @if ($val->evidence_of_aggrement != null)
                                                    <a download=""
                                                        href="{{ asset('assets/uploads/docs' . $val->evidence_of_aggrement) }}">
                                                        {{ $val->evidence_of_aggrement }}
                                                    </a>
                                                @endif
                                            </td>
                                            <td>Active</td>
                                        </tr>
                                    @endforeach
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
