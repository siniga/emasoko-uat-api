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
            Pending Mandate Consent Status
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">

                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            @php
                                $notify = get_pending_debit_orders();
                            @endphp
                            <a class="nav-link active" data-bs-toggle="tab" href="#pending" role="tab">
                                <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                                <span class="d-none d-sm-block">Pending
                                    @if ($notify != null)
                                        <span class="badge bg-danger">{{ $notify }}</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#approved" role="tab">
                                <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                                <span class="d-none d-sm-block">Approved</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#rejected" role="tab">
                                <span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
                                <span class="d-none d-sm-block">Rejected</span>
                            </a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content p-3 text-muted">
                        <div class="tab-pane active" id="pending" role="tabpanel">
                            <div class="table-responsive">
                                @if (isset($debit_orders) && $debit_orders)
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
                                            @foreach ($debit_orders as $values)
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
                                                            <span class="badge bg-warning">Pending</span>
                                                        @elseif($values->approved == 1)
                                                            <span class="badge bg-success">Approved</span>
                                                        @elseif($values->approved == 99)
                                                            <span class="badge bg-danger">Rejected</span>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        <a href="{{ route('payment-mandate.details', $values->customer_account_uuid) }}"
                                                            title="View" class="btn btn-outline-primary btn-xss">
                                                            <i class="bx bx-folder-open text-center"></i>
                                                        </a>
                                                        {{-- <a href="{{ route('customers.edit', $values->uuid) }}" title="Edit"
                                                    class="btn btn-outline-secondary btn-xss">
                                                    <i class="bx bx-pencil text-center"></i>
                                                </a> --}}
                                                        {{-- <a href="#" title="Delete" class="btn btn-outline-danger btn-xss delete">
                                                    <i class="bx bx-trash"></i>
                                                </a> --}}
                                                    </td>
                                                </tr>
                                                @php $serial++; @endphp
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <div class="alert alert-danger"> No any pending debit order</div>
                                @endif
                            </div>
                        </div>
                        <!--./tab-pane -->

                        <div class="tab-pane" id="approved" role="tabpanel">
                            <p class="mb-0">

                            </p>
                        </div>
                        <!--./tab-pane -->

                        <div class="tab-pane" id="rejected" role="tabpanel">
                            <p class="mb-0">

                            </p>
                        </div>
                        <!--./tab-pane -->

                    </div>
                    <!--./tab-content -->


                </div>
                <!--./card-body -->
            </div>
            <!--./card -->
        </div>
        <!--./col-lg-12 -->
    </div>
    <!-- end row -->
@endsection
