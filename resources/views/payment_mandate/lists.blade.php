@extends('layouts.master')

@section('title')
    Payment Mandate
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            Payment Mandate
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            {!! Form::open(['url' => route('payment-mandate.lists'), 'method' => 'POST', 'class' => 'form-horizontal']) !!}
                            {{ Form::token() }}

                            <div class="row">
                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        {{ Form::date('start_at', old('start_at', $post_data['start_at']), ['placeholder="Start Date..."', 'class="form-control"']) }}
                                    </div>
                                </div>

                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        {{ Form::date('end_at', old('end_at', $post_data['end_at']), ['placeholder="End Date..."', 'class="form-control"']) }}
                                    </div>
                                </div>

                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        @php
                                            $options = [
                                                0 => 'Pending',
                                                1 => 'Approved',
                                                2 => 'Failed',
                                            ];
                                            $options = ['' => '-- Mandate Status --'] + $options;
                                        @endphp

                                        {{ Form::select('status', $options, old('status', $post_data['status']), ['class' => 'form-control']) }}
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        @php
                                            $account_type_options = [];
                                            $options = [];
                                        @endphp
                                        @foreach ($account_types as $val)
                                            @php
                                                $options[$val->id] = $val->name;
                                            @endphp
                                        @endforeach
                                        @php
                                            $account_type_options = $options;
                                            $options = ['' => '-- Payment Channel --'] + $options;
                                        @endphp

                                        {{ Form::select('account_type_id', $options, old('account_type_id', $post_data['account_type_id']), ['class' => 'form-control account_type_id', 'id' => 'account_type_id']) }}
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        @php $options = []; @endphp
                                        @foreach ($accounts as $val)
                                            @php
                                                $options[$val->id] = $val->name;
                                            @endphp
                                        @endforeach
                                        @php
                                            $options = ['' => '-- Bank/MNO name --'] + $options;
                                        @endphp

                                        {{ Form::select('account_id', $options, old('account_id', $post_data['account_id']), ['class' => 'form-control account_id', 'id' => 'account_id']) }}
                                    </div>
                                </div>         
                            </div>

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        {{ Form::text('keyword', old('keyword'), ['class="form-control"', 'placeholder="Search by name of account holder or number..."']) }}
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <div class="text-sm-end d-none d-sm-block">
                                            <button type="submit" name="filter" class="btn btn-secondary">
                                                <i class="bx bx-search"></i> Filter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{ Form::close() }}
                        </div>
                    </div>
                </div>
            </div>

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

                        @if (isset($debit_orders) && $debit_orders->isNotEmpty())
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <span class="font-weight-500">No. of payment mandate:
                                        {{ number_format($number_of_debit_orders) }}</span>
                                </div>

                                <div class="col-md-6">
                                    <div class="text-sm-end d-none d-sm-block">
                                        <a class="btn btn-primary btn-sm"
                                            href="{{ route('payment-mandate.create-account') }}">
                                            <i class="fas fa-plus"></i> Create New
                                        </a>

                                        <a title="Export" class="btn btn-outline-success btn-sm"
                                            href="{{ route('payment-mandate.export-xls', ['start_at' => $post_data['start_at'], 'end_at' => $post_data['end_at'], 'account_type_id' => $post_data['account_type_id'], 'account_id' => $post_data['account_id'], 'status' => $post_data['status'], 'keyword' => $post_data['keyword']]) }}">
                                            <i class="bx bx-export"></i> Export XLS
                                        </a>

                                        <a title="Export" class="btn btn-outline-danger btn-sm"
                                            href="{{ route('payment-mandate.export-pdf', ['start_at' => $post_data['start_at'], 'end_at' => $post_data['end_at'], 'account_type_id' => $post_data['account_type_id'], 'account_id' => $post_data['account_id'], 'status' => $post_data['status'], 'keyword' => $post_data['keyword']]) }}">
                                            <i class="fas fa-file-pdf"></i> Export PDF
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered dt-responsive  nowrap w-100" id="dynamicTable">
                                    <thead>
                                        <tr>
                                            <th width="3%">#</th>
                                            <th width="20%">Account Name</th>
                                            <th>Bank Name/MNO</th>
                                            <th>Account Number</th>
                                            <th>Mandate Reference</th>
                                            <th>Primary Phone</th>
                                            <th>Mandate Status</th>
                                            <th>Created Date</th>
                                            <th style="width: 120px; !important">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $serial = 1; @endphp
                                        @foreach ($debit_orders as $values)
                                            <tr>
                                                <td>{{ $serial }}</td>
                                                <td>
                                                    {{ strtoupper($values->customerAccount->account_name) ?? $values->customerAccount->phone }}<br>
                                                    ID: {{ $values->customerAccount->customer->id_number ?? 'NIL' }}
                                                </td>
                                                <td>{{ $values->customerAccount->account->name ?? '' }}</td>
                                                <td>
                                                    {{ $values->customerAccount->account_number ?? $values->customerAccount->phone }}
                                                </td>
                                                <td>
                                                    {{ $values->reference }}
                                                </td>
                                                <td>{{ $values->customerAccount->phone ?? '' }}</td>

                                                <td>
                                                    @if ($values->status == 100)
                                                        <span class="badge badge-pill badge-soft-danger">Pending Registration</span>
                                                    @elseif($values->status == 200)
                                                        <span class="badge badge-pill badge-soft-info">Under Review</span>
                                                    @elseif($values->status == 201)
                                                        <span class="badge badge-pill badge-soft-info">Pending Verification</span>
                                                    @elseif($values->status == 202)
                                                        <span class="badge badge-pill bg-success">Verified</span>
                                                    @elseif($values->status == 240)
                                                        <span class="badge badge-pill bg-success">Active</span>
                                                    @elseif($values->status == 249)
                                                        <span class="badge badge-pill badge-soft-warning">Processing</span>
                                                    @elseif($values->status == 403) 
                                                        <span class="badge badge-pill badge-soft-danger">Failed</span>
                                                    @elseif($values->status == 405) 
                                                        <span class="badge badge-pill badge-soft-danger">Cancelled</span>  
                                                    @elseif($values->status == 500 || $values->status == 430)
                                                        <span class="badge badge-pill badge-soft-danger">Failed Verification</span>
                                                    @elseif($values->status == 501)
                                                        <span class="badge badge-pill badge-soft-danger">Inactive</span>
                                                    @elseif($values->status == 999)
                                                        <span class="badge badge-pill badge-soft-danger">Canceled</span>
                                                    @endif
                                                </td>

                                                <td>
                                                    {{ date('d-m-Y H:i', strtotime($values->updated_at)) }}
                                                </td>

                                                <td>
                                                    @if ($values->status == 100)
                                                        <a href="{{ route('payment-mandate.preview', $values->uuid) }}"
                                                            title="Preview application" class="btn btn-xss">
                                                            <i class='bx bxs-check-square bx-xs text-success'></i>
                                                        </a>
                                                    @endif

                                                    @if ($values->status != 100)
                                                        <a href="{{ route('payment-mandate.show', $values->uuid) }}" title="View"
                                                            class="btn btn-xss">
                                                            <i class="bx bxs-folder-open"></i>
                                                        </a>
                                                    @endif

                                                    @if ($values->status == 100)
                                                        <a href="{{ route('payment-mandate.edit', $values->uuid) }}"
                                                            title="Edit" class="btn btn-xss">
                                                            <i class="bx bx-edit text-secondary"></i>
                                                        </a>
                                                    @endif

                                                    @if ($values->status == 100 || $values->status == 200)
                                                        <a href="{{ route('payment-mandate.delete', $values->uuid) }}"
                                                            title="Delete" class="btn btn-xss delete">
                                                            <i class="bx bx-trash text-danger"></i>
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                            @php $serial++; @endphp
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-end">
                                    @if (method_exists($debit_orders, 'links'))
                                        {{ $debit_orders->links() }}
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="alert alert-danger"> No any payment mandate at the moment!</div>
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
            //on select account category
            $('.account_type_id').on('change', function(e) {
                e.preventDefault();
                accountTypeId = $(this).val();

                //query for data
                $.ajax({
                    url: '/account-types/' + accountTypeId,
                    type: "get",
                    success: function(data) {
                        $('.account_id').html(data);
                    }
                });
            });

        });
    </script>
@endsection
