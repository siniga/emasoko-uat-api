@extends('layouts.master')

@section('title')
    Customer Accounts
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Customer Accounts</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Customer Accounts</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            {!! Form::open(['url' => route('customer-accounts.lists'), 'method' => 'POST', 'class' => 'form-horizontal']) !!}
                            {{ Form::token() }}

                            <div class="row">
                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        {{ Form::date('start_at', old('start_at', $data['start_at']), ['placeholder="Start Date..."', 'class="form-control"']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        {{ Form::date('end_at', old('end_at', $data['end_at']), ['placeholder="End Date..."', 'class="form-control"']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-2">
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
                                            $options = ['' => 'Payment Channel'] + $options;
                                        @endphp

                                        {{ Form::select('account_type_id', $options, old('account_type_id', $data['account_type_id']), ['class' => 'form-control account_type_id', 'id' => 'account_type_id']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        @php $options = []; @endphp
                                        @foreach ($accounts as $val)
                                            @php
                                                $options[$val->id] = $val->name;
                                            @endphp
                                        @endforeach
                                        @php
                                            $options = ['' => 'Bank/MNO name'] + $options;
                                        @endphp

                                        {{ Form::select('account_id', $options, old('account_id', $data['account_id']), ['class' => 'form-control account_id', 'id' => 'account_id']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        @php
                                            $options = [
                                                'partial' => 'Partial Approved',
                                                'approved' => 'Approved',
                                                'rejected' => 'Rejected',
                                            ];
                                            $options = ['' => 'Account Status'] + $options;
                                        @endphp

                                        {{ Form::select('status', $options, old('status', $data['status']), ['class' => 'form-control']) }}
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        {{ Form::text('keyword', old('keyword', $data['keyword']), ['class="form-control"', 'placeholder="Search by name of account holder or number..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <div class="text-sm-end d-none d-sm-block">
                                            <button type="submit" name="filter" class="btn btn-secondary">
                                                <i class="bx bx-search"></i> Filter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->
                            {{ Form::close() }}
                        </div>
                        <!--./col-lg-12 -->
                    </div>
                    <!--./row -->
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

                        @if (isset($customer_accounts) && $customer_accounts->isNotEmpty())
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <span class="font-weight-500">No. of customer accounts:
                                        {{ number_format(count($customer_accounts)) }}</span>
                                </div>

                                <div class="col-md-6">
                                    <div class="text-sm-end d-none d-sm-block">
                                        <a title="Export" class="btn btn-outline-success btn-sm"
                                            href="{{ route('customer-accounts.export-xls', ['start_at' => $data['start_at'], 'end_at' => $data['end_at'], 'account_type_id' => $data['account_type_id'], 'account_id' => $data['account_id'], 'status' => $data['status'], 'keyword' => $data['keyword']]) }}">
                                            <i class="bx bx-export"></i> Export XLS</a>

                                        <a class="btn btn-outline-danger btn-sm"
                                            href="{{ route('customer-accounts.export-pdf', ['start_at' => $data['start_at'], 'end_at' => $data['end_at'], 'account_type_id' => $data['account_type_id'], 'account_id' => $data['account_id'], 'status' => $data['status'], 'keyword' => $data['keyword']]) }}">
                                            <i class="fas fa-file-pdf"></i> Export PDF
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="16%">Account Name</th>
                                        <th width="14%">NIDA Number</th>
                                        <th width="10%">Payment Channel</th>
                                        <th width="12%">Bank Name/MNO</th>
                                        <th width="12%">Account Number</th>
                                        <th width="10%">Created</th>
                                        <th width="10%">Status</th>
                                        <th style="width: 80px; !important">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $serial = 1; @endphp
                                    @foreach ($customer_accounts as $values)
                                        <tr>
                                            <td>{{ $serial }}</td>
                                            <td>
                                                @if ($values->account_name == null)
                                                    {{ $values->phone }}
                                                @else
                                                    {{ $values->account_name }}
                                                @endif
                                            </td>
                                            <td>
                                                <small>{{ $values->customer->id_number ?? 'NIN' }}</small>
                                            </td>
                                            <td>{{ $values->accountType->name ?? 'NIL' }}</td>
                                            <td>{{ $values->account->name ?? 'NIL' }}</td>

                                            <td>
                                                {{ $values->account_number ?? $values->phone }}
                                            </td>

                                            <td>{{ date('d-m-Y H:i', strtotime($values->created_at)) }}</td>

                                            <td>
                                                @if ($values->status == 'partial')
                                                    <span class="badge bg-info">Partial Approved</span>
                                                @elseif($values->status == 'approved')
                                                    <span class="badge bg-success">Approved</span>
                                                @elseif($values->status == 'rejected')
                                                    <span class="badge bg-danger">Rejected</span>
                                                @endif
                                            </td>

                                            <td>
                                                <a href="{{ route('customer-accounts.show', $values->id) }}" title="View"
                                                    class="btn btn-xss">
                                                    <i class="bx bxs-folder-open text-primary"></i>
                                                </a>

                                                @if ($values->status == 'partial' || $values->status == 'rejected')
                                                    <a href="{{ route('customer-accounts.edit', $values->id) }}"
                                                        title="Edit" class="btn btn-xss">
                                                        <i class="bx bx-edit text-secondary"></i>
                                                    </a>

                                                    <a href="{{ route('customer-accounts.delete', $values->id) }}"
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
                        @else
                            <div class="alert alert-danger"> No any customer account at the moment!</div>
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
