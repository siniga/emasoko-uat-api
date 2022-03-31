@extends('layouts.master')

@section('title')
    My Usage
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Pending Mandate
        @endslot
        @slot('title')
            Pending Payment Mandate
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">

                    <div class="table-responsive">
                        @if (isset($pending_debit_orders) && count($pending_debit_orders) > 0)
                            <table id="dt" class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="20%">Account Name</th>
                                        <th width="16%">NIDA Number</th>
                                        <th width="10%">Payment Channel</th>
                                        <th width="10%">Bank Name/MNO</th>
                                        <th width="12%">Account Number</th>
                                        <th width="12%">Phone<br /><small>(Connected to Bank)</small></th>
                                        <th width="8%">Consent Status</th>
                                        <th style="width: 60px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $serial = 1; @endphp
                                    @foreach ($pending_debit_orders as $values)
                                        <tr>
                                            <td>{{ $serial }}</td>
                                            <td>{{ strtoupper($values->customerAccount->account_name ?? '') }}
                                            </td>
                                            <td><small>{{ $values->customerAccount->id_number ?? 'NIL' }}</small>
                                            </td>
                                            <td>{{ $values->customerAccount->accountType->name ?? '' }}</td>
                                            <td>{{ $values->customerAccount->account->name ?? '' }}</td>
                                            <td>
                                                {{ $values->customerAccount->account_number ?? '' }}
                                            </td>
                                            <td>{{ $values->customerAccount->phone ?? '' }}</td>

                                            <td>
                                                @if ($values->approved == 0)
                                                    <span class="badge badge-pill badge-soft-danger">Pending</span>
                                                @elseif($values->approved == 1)
                                                    <span class="badge badge-pill badge-soft-success">Approved</span>
                                                @elseif($values->approved == 99)
                                                    <span class="badge badge-pill badge-soft-danger">Rejected</span>
                                                @endif
                                            </td>

                                            <td>
                                                <a href="{{ route('payment-mandate.details', $values->customer_account_uuid) }}"
                                                    title="View">
                                                    <i class="bx bx-folder-open bx-border bx-xs"></i>
                                                </a>

                                                <a href="#"
                                                    title="Send Remainder">
                                                    <i class="bx bx bx-comment bx-border bx-xs"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @php $serial++; @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-danger"> No any pending payment mandate</div>
                        @endif
                    </div>
                    <!--./table-responsive -->


                </div>
                <!--./card-body -->
            </div>
            <!--./card -->
        </div>
        <!--./col-lg-12 -->
    </div>
    <!-- end row -->
@endsection
