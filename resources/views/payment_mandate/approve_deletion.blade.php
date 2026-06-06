@extends('layouts.master')

@section('title')
    Approve Mandate Deletion
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            Approve Mandate Deletion
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <div class="row">
                            <div class="col-md-12">
                                @if (session()->get('success'))
                                    <div class="alert alert-success">
                                        {{ session()->get('success') }}
                                    </div>
                                @elseif(session()->get('warning'))
                                    <div class="alert alert-warning">
                                        {{ session()->get('warning') }}
                                    </div>
                                @elseif(session()->get('danger'))
                                    <div class="alert alert-danger">
                                        {{ session()->get('danger') }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if (isset($dd_requests) && $dd_requests->isNotEmpty())
                            {!! Form::open(['url' => route('payment-mandate.approve-deletion'), 'method' => 'POST']) !!}
                            {{ Form::token() }}

                            <div class="row mb-3">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-12 mb-2">
                                    <div class="text-sm-end">
                                        <button type="submit" class="btn btn-success btn-sm waves-effect waves-light"
                                            name="approve_deletion" value="approve_deletion">
                                            <i class='bx bx-trash'></i> Approve Deletion
                                        </button>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered dt-responsive  nowrap w-100" id="dynamicTable">
                                        <thead>
                                            <tr>
                                                <th width="3%"><input type="checkbox" id="select_all" /></th>
                                                <th width="20%">Account Name</th>
                                                <th>Bank Name/MNO</th>
                                                <th>Account Number</th>
                                                <th>Mandate Reference</th>
                                                <th>Primary Phone</th>
                                                <th>Deletion</th>
                                                <th>Created Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $serial = 1; @endphp
                                            @foreach ($dd_requests as $values)
                                                @if($values->debitOrder)
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" class="checkbox" id="request_id"
                                                                name="request_id[]" value="{{ $values->id }}">
                                                        </td>
                                                        <td>
                                                            {{ strtoupper($values->debitOrder->customerAccount->account_name) ?? $values->debitOrder->customerAccount->phone }}<br>
                                                            ID:
                                                            {{ $values->debitOrder->customerAccount->customer->id_number ?? 'NIL' }}
                                                        </td>
                                                        <td>{{ $values->debitOrder->customerAccount->account->name ?? '' }}</td>
                                                        <td>
                                                            {{ $values->debitOrder->customerAccount->debitOrder->account_number ?? $values->debitOrder->customerAccount->phone }}
                                                        </td>
                                                        <td>
                                                            {{ $values->debitOrder->reference }}
                                                        </td>
                                                        <td>{{ $values->debitOrder->customerAccount->phone ?? '' }}</td>

                                                        <td>
                                                            @if ($values->status == 'PENDING')
                                                                <span class="badge badge-pill badge-soft-danger">Pending</span>
                                                            @elseif($values->status == 'APPROVED')
                                                                <span class="badge badge-pill bg-success">Approved</span>
                                                            @elseif($values->status == 'REJECTED')
                                                                <span class="badge badge-pill badge-soft-danger">Rejected</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ date('d-m-Y H:i', strtotime($values->created_at)) }}</td>
                                                    </tr>
                                                @endif
                                                @php $serial++; @endphp
                                            @endforeach
                                        </tbody>
                                    </table>

                                    <div class="d-flex justify-content-end">
                                        @if (method_exists($dd_requests, 'links'))
                                            {{ $dd_requests->links() }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            {{ Form::close() }}
                        @else
                            <div class="alert alert-danger"> No any deletion requests at the moment!</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->

@endsection
@section('script')
    <script type="text/javascript">
        $(document).ready(function() {

        });
    </script>
@endsection
