@extends('layouts.master')

@section('title')
    Customer Account Information
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            Customer Account Information
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

                    <div class="d-flex justify-content-between mb-2">
                        <div>

                        </div>

                        <div class="text-sm-end d-none d-sm-block">
                            @if ($customer_account->status == 'partial' || $customer_account->status == 'rejected')
                                <a href="{{ route('customer-accounts.edit', $customer_account->id) }}" title="Edit"
                                    class="btn btn-outline-primary text-medium btn-sm">
                                    <i class="bx bx-edit font-size-12"></i> Edit
                                </a>

                                <a href="{{ route('customer-accounts.delete', $customer_account->id) }}" title="Delete"
                                    class="btn btn-outline-danger text-medium btn-sm delete">
                                    <i class="bx bx-trash font-size-12"></i> Delete
                                </a>
                            @endif
                        </div>
                    </div>

                    <table class="table table-stripped table-bordered">
                        <tr>
                            <th colspan="2" class="font-weight-600">Customer Account Information</th>
                        </tr>
                        <tr>
                            <td width=25%>Payment Channel</td>
                            <td width=75%>
                                <span>{{ $customer_account->accountType->name ?? '' }}</span>
                            </td>
                        </tr>

                        <tr>
                            <td>Bank/MNO Name</td>
                            <td>
                                <span>{{ $customer_account->account->name ?? '' }}</span>
                            </td>
                        </tr>

                        <tr>
                            <td>Name of Account Holder</td>
                            <td>
                                <span>{{ $customer_account->account_name ?? '' }}</span>
                            </td>
                        </tr>

                        <tr>
                            <td>Account Number</td>
                            <td>
                                <span>{{ $customer_account->account_number ?? $customer_account->phone }}</span>
                            </td>
                        </tr>

                        <tr>
                            <td>Phone <small>(Connected to Bank)</small></td>
                            <td colspan="4">
                                <span>{{ $customer_account->phone ?? '' }}</span>
                            </td>
                        </tr>
                    </table>

                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <h5 class="text-capitalize">Payment Mandates</h5>
                        </div>

                        <div class="text-sm-end">
                            <a href="{{ route('payment-mandate.create-deduction-info', [$customer_account->id]) }}"
                                title="New Payment Mandate" class="btn btn-outline-primary text-medium btn-sm">
                                <i class="bx bx-plus font-size-12"></i> Create Payment Mandate
                            </a>
                        </div>
                    </div>

                    @if ($customer_account->debitOrders->isNotEmpty())
                        <table class="table table-bordered" id="dynamicTable">
                            <tr>
                                <th width="3%">#</th>
                                <th width="12%">Loan Amount</th>
                                <th width="10%">Debit Type</th>
                                <th width="10%">Installment Amount</th>
                                <th width="10%">Peridiocity</th>
                                <th width="10%">Number of Deduction</th>
                                <th width="10%">Deduction <br />Start Date</th>
                                <th width="10%">Deduction <br />End Date</th>
                                <th width="15%">Evidence <br />of Aggrement</th>
                                <th width="8%">Approved</th>
                            </tr>

                            @foreach ($customer_account->debitOrders as $val)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ number_format($val->total_amount) ?? '' }}</td>
                                    <td>{{ $val->debit_type ?? '' }}</td>
                                    <td>
                                        @if ($val->debit_type == 'FIXED')
                                            {{ number_format($val->installment_amount) ?? '' }}
                                        @elseif($val->debit_type == 'DYNAMIC')
                                            Min:
                                            {{ number_format($val->min_installment_amount) ?? '' }}<br />
                                            Max: {{ number_format($val->max_installment_amount) ?? '' }}
                                        @endif
                                    </td>
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
                                    <td>
                                        @if ($val->approved == 1)
                                            <span class="badge badge-pill badge-soft-success">Yes</span>
                                        @elseif($val->approved == 0)
                                            <span class="badge badge-pill badge-soft-danger">No</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @else
                        <div class="alert alert-danger">No any payment mandate at the moment!</div>
                    @endif
                </div>
                <!--./card-body -->
            </div>
            <!--./card-->
        </div>
        <!--./col -->
    </div>
    <!-- end row -->
@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function() {



        });
    </script>
@endsection
