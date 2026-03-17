@extends('layouts.master')

@section('title')
    Payment Mandate
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
        border: 1px solid #dfe8f4;
    }

    .ca-table thead th {
        background: #edf4ff;
        color: var(--ca-primary);
        font-weight: 700;
        border-color: #dce7fa;
    }

    .ca-row-actions {
        position: relative;
    }

    .ca-row-actions .dropdown-toggle {
        border: 0;
        background: transparent;
        color: var(--ca-primary);
        border-radius: 999px;
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .ca-row-actions .dropdown-toggle:hover,
    .ca-row-actions .dropdown-toggle:focus {
        background: #edf4ff;
        color: var(--ca-primary);
        box-shadow: none;
        border-color: transparent;
    }

    .ca-row-actions .dropdown-toggle::after {
        display: none;
    }

    .ca-row-actions .dropdown-menu {
        min-width: 10rem;
        border: 1px solid #dfe8f4;
        box-shadow: 0 16px 30px rgba(21, 53, 103, 0.12);
        border-radius: 12px;
        padding: 0.35rem 0;
    }

    .ca-row-actions .dropdown-item {
        font-size: 0.875rem;
        padding: 0.55rem 0.9rem;
    }
</style>

@section('content')
    <div class="ca-shell">
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
                <div class="ca-filter mb-4">
                    <div class="ca-filter__title">Filter Payment Mandates</div>
                    <div class="ca-filter__hint">Narrow the mandate portfolio by date, lifecycle, payment channel, provider, or mandate keyword.</div>

                    {!! Form::open(['url' => route('payment-mandate.lists'), 'method' => 'POST', 'class' => 'form-horizontal']) !!}
                    {{ Form::token() }}

                    <div class="row">
                        <div class="col-lg-2">
                            <div class="mb-3">
                                {{ Form::date('start_at', old('start_at', $post_data['start_at']), ['class' => 'form-control']) }}
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="mb-3">
                                {{ Form::date('end_at', old('end_at', $post_data['end_at']), ['class' => 'form-control']) }}
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="mb-3">
                                @php
                                    $options = [
                                        \App\Models\DebitOrder::LIFECYCLE_DRAFT => 'Draft',
                                        \App\Models\DebitOrder::LIFECYCLE_SUBMITTED => 'Submitted',
                                        \App\Models\DebitOrder::LIFECYCLE_AWAITING_OTP => 'Awaiting OTP',
                                        \App\Models\DebitOrder::LIFECYCLE_VERIFIED => 'Verified',
                                        \App\Models\DebitOrder::LIFECYCLE_ACTIVE => 'Active',
                                        \App\Models\DebitOrder::LIFECYCLE_CANCELLATION_REQUESTED => 'Cancellation Requested',
                                        \App\Models\DebitOrder::LIFECYCLE_CANCELLED => 'Cancelled',
                                        \App\Models\DebitOrder::LIFECYCLE_CLOSED => 'Closed',
                                        \App\Models\DebitOrder::LIFECYCLE_ARCHIVED => 'Archived',
                                        \App\Models\DebitOrder::LIFECYCLE_FAILED => 'Failed',
                                    ];
                                    $options = ['' => '-- Lifecycle Status --'] + $options;
                                @endphp

                                {{ Form::select('status', $options, old('status', $post_data['status']), ['class' => 'form-control']) }}
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="mb-3">
                                @php
                                    $options = ['' => '-- Payment Channel --'];
                                @endphp
                                @foreach ($account_types as $val)
                                    @php
                                        $options[$val->id] = $val->name;
                                    @endphp
                                @endforeach

                                {{ Form::select('account_type_id', $options, old('account_type_id', $post_data['account_type_id']), ['class' => 'form-control account_type_id', 'id' => 'account_type_id']) }}
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="mb-3">
                                @php
                                    $options = ['' => '-- Bank/MNO name --'];
                                @endphp
                                @foreach ($accounts as $val)
                                    @php
                                        $options[$val->id] = $val->name;
                                    @endphp
                                @endforeach

                                {{ Form::select('account_id', $options, old('account_id', $post_data['account_id']), ['class' => 'form-control account_id', 'id' => 'account_id']) }}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4">
                            <div class="mb-3">
                                @php
                                    $options = ['' => '-- Service Provider --'];
                                @endphp
                                @foreach ($partners as $val)
                                    @php
                                        $options[$val->id] = $val->business_name;
                                    @endphp
                                @endforeach

                                {{ Form::select('partner_id', $options, old('partner_id', $post_data['partner_id']), ['class' => 'form-control select2', 'id' => 'partner_id']) }}
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                {{ Form::text('keyword', old('keyword', $post_data['keyword']), ['class' => 'form-control', 'placeholder' => 'Search by account holder, account number, or mandate reference']) }}
                            </div>
                        </div>

                        <div class="col-lg-2">
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
                            <div class="col-md-2">
                                <div class="ca-summary">
                                    <div class="ca-summary__label">Total Mandates</div>
                                    <div class="ca-summary__value">{{ number_format($listStats['total'] ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="ca-summary">
                                    <div class="ca-summary__label">Draft</div>
                                    <div class="ca-summary__value">{{ number_format($listStats['draft'] ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="ca-summary">
                                    <div class="ca-summary__label">Submitted</div>
                                    <div class="ca-summary__value text-info">{{ number_format($listStats['submitted'] ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="ca-summary">
                                    <div class="ca-summary__label">Awaiting OTP</div>
                                    <div class="ca-summary__value text-warning">{{ number_format($listStats['awaiting_otp'] ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="ca-summary">
                                    <div class="ca-summary__label">Active</div>
                                    <div class="ca-summary__value text-success">{{ number_format($listStats['active'] ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="ca-summary">
                                    <div class="ca-summary__label">Failed</div>
                                    <div class="ca-summary__value text-danger">{{ number_format($listStats['failed'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{ Form::close() }}
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
                                        <span class="font-weight-500">No. of payment mandates:
                                            {{ number_format($number_of_debit_orders) }}</span>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="text-sm-end d-none d-sm-block">
                                            @if (Auth::user()->hasRole(['admin']) || Auth::user()->hasRole(['Company_Approver']))
                                                <div class="btn-group">
                                                    <button class="btn btn-secondary btn-sm text-sm" type="button">
                                                        Actions
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-sm btn-secondary dropdown-toggle dropdown-toggle-split"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="mdi mdi-chevron-down"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item text-sm"
                                                            href="{{ route('payment-mandate.request-cancellation') }}">Request
                                                            Cancellation</a>
                                                        <a class="dropdown-item text-sm"
                                                            href="{{ route('payment-mandate.request-closing') }}">Request Mandate
                                                            Close</a>
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item text-sm"
                                                            href="{{ route('payment-mandate.request-deletion') }}">Request
                                                            Deletion</a>
                                                    </div>
                                                </div>
                                            @endif

                                            <a class="btn btn-primary btn-sm" href="{{ route('payment-mandate.create-account') }}">
                                                <i class="bx bx-list-plus"></i> Create New
                                            </a>

                                            <a title="Export" class="btn btn-outline-success btn-sm"
                                                href="{{ route('payment-mandate.export-xls', ['start_at' => $post_data['start_at'], 'end_at' => $post_data['end_at'], 'account_type_id' => $post_data['account_type_id'], 'account_id' => $post_data['account_id'], 'partner_id' => $post_data['partner_id'], 'status' => $post_data['status'], 'keyword' => $post_data['keyword']]) }}">
                                                <i class="bx bx-export"></i> Export XLS
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive ca-table">
                                    <table class="table table-bordered dt-responsive nowrap w-100" id="dynamicTable">
                                        <thead>
                                            <tr>
                                                <th width="3%">#</th>
                                                <th width="20%">Account Name</th>
                                                <th>Bank Name/MNO</th>
                                                <th>Account Number</th>
                                                <th>Mandate Reference</th>
                                                <th>Lifecycle</th>
                                                <th>Created</th>
                                                <th width="5%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $serial = 1; @endphp
                                            @foreach ($debit_orders as $values)
                                                @php
                                                    $lifecycle = $values->lifecycle_status ?? $values->resolved_lifecycle_status;
                                                    $canPreview = $lifecycle === \App\Models\DebitOrder::LIFECYCLE_DRAFT;
                                                    $canEdit = in_array($lifecycle, [
                                                        \App\Models\DebitOrder::LIFECYCLE_DRAFT,
                                                        \App\Models\DebitOrder::LIFECYCLE_SUBMITTED,
                                                        \App\Models\DebitOrder::LIFECYCLE_FAILED,
                                                    ], true);
                                                    $canDelete = in_array($lifecycle, [
                                                        \App\Models\DebitOrder::LIFECYCLE_DRAFT,
                                                        \App\Models\DebitOrder::LIFECYCLE_SUBMITTED,
                                                    ], true);
                                                @endphp
                                                <tr>
                                                    <td>{{ $serial }}</td>
                                                    <td>
                                                        {{ strtoupper($values->customerAccount->account_name ?? $values->customerAccount->phone) }}<br>
                                                        ID: {{ $values->customerAccount->customer->id_number ?? 'NIL' }}
                                                    </td>
                                                    <td>{{ $values->customerAccount->account->name ?? 'NIL' }}</td>
                                                    <td>{{ mask_account_number($values->customerAccount->account_number ?? $values->customerAccount->phone) }}</td>
                                                    <td>{{ $values->reference }}</td>
                                                    <td>{!! mandate_status_badge($values) !!}</td>
                                                    <td>{{ date('d-m-Y H:i', strtotime($values->created_at)) }}</td>
                                                    <td class="text-center">
                                                        <div class="dropdown ca-row-actions">
                                                            <button class="btn dropdown-toggle" type="button"
                                                                data-bs-toggle="dropdown" aria-expanded="false"
                                                                title="Mandate Actions">
                                                                <i class="bx bx-dots-vertical-rounded fs-5"></i>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-end">
                                                                @if ($canPreview)
                                                                    <a href="{{ route('payment-mandate.preview', $values->uuid) }}"
                                                                        class="dropdown-item">
                                                                        Preview
                                                                    </a>
                                                                @else
                                                                    <a href="{{ route('payment-mandate.show', $values->uuid) }}"
                                                                        class="dropdown-item">
                                                                        View Details
                                                                    </a>
                                                                @endif

                                                                @if ($canEdit)
                                                                    <a href="{{ route('payment-mandate.edit', $values->uuid) }}"
                                                                        class="dropdown-item">
                                                                        Edit
                                                                    </a>
                                                                @endif

                                                                @if ($canDelete)
                                                                    <a href="{{ route('payment-mandate.delete', $values->uuid) }}"
                                                                        class="dropdown-item text-danger delete">
                                                                        Delete
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        </div>
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
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            $('.account_type_id').on('change', function(e) {
                e.preventDefault();
                const accountTypeId = $(this).val();

                $.ajax({
                    url: '/account-types/' + accountTypeId,
                    type: 'get',
                    success: function(data) {
                        $('.account_id').html(data);
                    }
                });
            });
        });
    </script>
@endsection
