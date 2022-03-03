@extends('layouts.master')

@section('title')
    Customer Information
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Customer
        @endslot
        @slot('title')
            Customer Information
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

                    <table class="table table-stripped table-bordered">
                        <tr>
                            <td colspan="4">
                                <b>Customer Information</b>
                            </td>
                        </tr>
                        <tr>
                            <th width=25%>Fullname</th>
                            <td width=25%>
                                <span>{{ $customer->first_name . ' ' . $customer->middle_name . ' ' . $customer->last_name }}</span>
                            </td>

                            <th>Status</th>
                            <td>
                                <span>{{ $customer->status }}</span>
                            </td>
                        </tr>

                        <tr>
                            <th width=25%>Date of Birth</th>
                            <td width=25%>
                                <span>{{ $customer->dob }}</span>
                            </td>

                            <th>NIDA Number</th>
                            <td>
                                <span>{{ $customer->id_number }}</span>
                            </td>
                        </tr>

                        <tr>
                            <th width=25%>Marital status</th>
                            <td width=25%>
                                <span>{{ $customer->maritalStatus->name }}</span>
                            </td>

                            <th>Gender</th>
                            <td>
                                <span>{{ $customer->gender->name ?? '' }}</span>
                            </td>
                        </tr>

                        <tr>
                            <th width=25%>Sector</th>
                            <td width=25%>
                                <span>{{ $customer->sector->name ?? '' }}</span>
                            </td>

                            <th>Occupation</th>
                            <td>
                                <span>{{ $customer->occupation->name ?? '' }}</span>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4">
                                <b>Customer Account</b>
                            </td>
                        </tr>

                        <tr>
                            <th width=25%>Account</th>
                            <td width=25%>
                                <span>{{ $customer->customerAccount->account->name ?? '' }}</span>
                            </td>

                            <th>Account Number/Phone</th>
                            <td>
                                <span>{{ $customer->customerAccount->account_number ?? '' }}</span>
                            </td>
                        </tr>

                        <tr>
                            <th>Phone</th>
                            <td colspan="4">
                                <span>{{ $customer->customerAccount->phone ?? '' }}</span>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4">
                                <b>Customer Loan Details</b>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4">

                                @if ($customer->customerAccount->debitOrders)
                                    <table class="table table-bordered" id="dynamicTable">
                                        <tr>
                                            <th width="3%">#</th>
                                            <th width="15%">Loan Purpose</th>
                                            <th width="12%">Loan Amount</th>
                                            <th width="10%">Installment Amount</th>
                                            <th width="10%">Peridiocity</th>
                                            <th width="10%">Number of Installment</th>
                                            <th width="12%">Start Date</th>
                                            <th width="15%">Evidence of Aggrement</th>
                                        </tr>

                                        @foreach ($customer->customerAccount->debitOrders as $val)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $val->loanPurpose->name ?? '' }}</td>
                                                <td>{{ number_format($val->total_amount) ?? '' }}</td>
                                                <td>{{ number_format($val->installment_amount) ?? '' }}</td>
                                                <td>{{ $val->periodicity }}</td>
                                                <td>{{ $val->installment_number ?? '' }}</td>
                                                <td>{{ date('d-m-Y', strtotime($val->start_date)) }}</td>
                                                <td>
                                                    @if ($val->evidence_of_aggrement != null)
                                                <a download=""
                                                    href="{{ asset('assets/uploads/docs' . $val->evidence_of_aggrement) }}">
                                                    {{ $val->evidence_of_aggrement }}
                                                </a>
                                            @endif
                                                </td>  
                                            </tr>
                                        @endforeach


                                    </table>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
                <!--./col -->
            </div>
            <!--./row-->
        </div>
        <!--./col -->
    </div>
    <!-- end row -->
@endsection
