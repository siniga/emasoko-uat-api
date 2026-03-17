@extends('layouts.master')

@section('title')
    Customer Accounts
@endsection

<style>
    .ca-shell {
        --ca-primary: #153567;
        --ca-border: #dce6f5;
        --ca-muted: #6b7c93;
    }

    .ca-summary {
        border: 1px solid var(--ca-border);
        border-radius: 16px;
        background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        padding: 1rem;
        height: 100%;
    }

    .ca-summary__label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--ca-muted);
        margin-bottom: 0.35rem;
    }

    .ca-summary__value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--ca-primary);
    }

    .ca-filter {
        border: 1px solid #e7edf6;
        border-radius: 16px;
        background: #fbfcfe;
        padding: 1.2rem;
        box-shadow: 0 16px 40px rgba(29, 53, 87, 0.08);
    }

    .ca-filter__title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--ca-primary);
        margin-bottom: 0.2rem;
    }

    .ca-filter__hint {
        color: var(--ca-muted);
        font-size: 0.88rem;
        margin-bottom: 1rem;
    }

    .ca-table {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #dfE8F4;
    }

    .ca-table thead th {
        background: #edf4ff;
        color: var(--ca-primary);
        font-weight: 700;
        border-color: #dce7fa;
    }
</style>

@section('content')
    <div class="ca-shell">
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
            <div class="ca-filter mb-4">
                    <div class="ca-filter__title">Filter Accounts</div>
                    <div class="ca-filter__hint">Narrow the customer account portfolio by date, payment channel, provider, account status, or customer keyword.</div>
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

                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        {{ Form::date('end_at', old('end_at', $data['end_at']), ['placeholder="End Date..."', 'class="form-control"']) }}
                                    </div>
                                </div>

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
                                            $options = ['' => '-- Payment Channel --'] + $options;
                                        @endphp

                                        {{ Form::select('account_type_id', $options, old('account_type_id', $data['account_type_id']), ['class' => 'form-control account_type_id', 'id' => 'account_type_id']) }}
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

                                        {{ Form::select('account_id', $options, old('account_id', $data['account_id']), ['class' => 'form-control account_id', 'id' => 'account_id']) }}
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        @php
                                            $options = [
                                                'partial' => 'Partial Approved',
                                                'approved' => 'Approved',
                                                'rejected' => 'Rejected',
                                            ];
                                            $options = ['' => '-- Account Status --'] + $options;
                                        @endphp

                                        {{ Form::select('status', $options, old('status', $data['status']), ['class' => 'form-control']) }}
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        {{ Form::text('keyword', old('keyword', $data['keyword']), ['class="form-control"', 'placeholder="Search by name of account holder or number..."']) }}
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

                            <div class="border-top pt-3 mt-2">
                                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                    <div>
                                        <div class="ca-filter__title mb-1">Filtered Summary</div>
                                        <div class="ca-filter__hint mb-0">These totals follow the filters currently applied above.</div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="ca-summary">
                                            <div class="ca-summary__label">Total Accounts</div>
                                            <div class="ca-summary__value">{{ number_format($listStats['total']) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="ca-summary">
                                            <div class="ca-summary__label">Approved</div>
                                            <div class="ca-summary__value text-success">{{ number_format($listStats['approved']) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="ca-summary">
                                            <div class="ca-summary__label">Partial Approved</div>
                                            <div class="ca-summary__value text-warning">{{ number_format($listStats['partial']) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="ca-summary">
                                            <div class="ca-summary__label">Rejected</div>
                                            <div class="ca-summary__value text-danger">{{ number_format($listStats['rejected']) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{ Form::close() }}
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

                                        {{-- <a class="btn btn-outline-danger btn-sm"
                                            href="{{ route('customer-accounts.export-pdf', ['start_at' => $data['start_at'], 'end_at' => $data['end_at'], 'account_type_id' => $data['account_type_id'], 'account_id' => $data['account_id'], 'status' => $data['status'], 'keyword' => $data['keyword']]) }}">
                                            <i class="fas fa-file-pdf"></i> Export PDF
                                        </a> --}}
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive ca-table">
                                <table class="table table-bordered dt-responsive  nowrap w-100" id="dynamicTable">
                                    <thead>
                                        <tr>
                                            <th width="3%">#</th>
                                            <th width="30%">Account Name</th>
                                            <th>Bank Name/MNO</th>
                                            <th>Account Number</th>
                                            <th>Phone</th>
                                            <th>Created</th>
                                            <th style="width: 40px; !important">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $serial = 1; @endphp
                                        @foreach ($customer_accounts as $values)
                                            <tr>
                                                <td>{{ $serial }}</td>
                                                <td>
                                                    {{ strtoupper($values->account_name) ?? $values->phone }}<br>
                                                    ID: {{ $values->customer->id_number ?? 'NIL' }}
                                                    <br>
                                                    Provider:
                                                    {{ optional(optional(optional($values->createdBy)->partners)->first())->partner->business_name ?? 'NIL' }}
                                                </td>
                                                <td>{{ $values->account->name ?? 'NIL' }}</td>
                                                <td>{{ $values->account_number ?? $values->phone }}</td>
                                                <td>{{ $values->phone }}</td>
                                                <td>{{ date('d-m-Y H:i', strtotime($values->created_at)) }}</td>
                                                <td>
                                                    <a href="{{ route('customer-accounts.show', $values->id) }}"
                                                        title="View" class="btn btn-xss">
                                                        <i class="bx bxs-folder-open"></i>
                                                    </a>

                                                    {{-- <a href="{{ route('customer-accounts.edit', $values->id) }}"
                                                        title="Edit" class="btn btn-xss">
                                                        <i class="bx bx-edit text-secondary"></i>
                                                    </a>

                                                    <a href="{{ route('customer-accounts.delete', $values->id) }}"
                                                        title="Delete" class="btn btn-xss delete">
                                                        <i class="bx bx-trash text-danger"></i>
                                                    </a> --}}
                                                </td>
                                            </tr>
                                            @php $serial++; @endphp
                                        @endforeach
                                    </tbody>
                                </table>

                                <div class="d-flex justify-content-end">
                                    @if (method_exists($customer_accounts, 'links'))
                                        {{ $customer_accounts->links() }}
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="alert alert-danger"> No any customer account at the moment!</div>
                        @endif
                    </div>
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
