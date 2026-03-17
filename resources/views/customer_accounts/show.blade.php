@extends('layouts.master')

<style>
    .cad-shell {
        --cad-primary: #153567;
        --cad-border: #dce6f5;
        --cad-muted: #6b7c93;
    }

    .cad-hero {
        border: 0;
        border-radius: 20px;
        background: linear-gradient(135deg, #153567 0%, #1f4b8b 100%);
        box-shadow: 0 18px 40px rgba(21, 53, 103, 0.18);
        overflow: hidden;
    }

    .cad-hero__body {
        padding: 1.5rem;
    }

    .cad-kicker {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.8rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .cad-title {
        color: #fff;
        font-size: 1.65rem;
        font-weight: 700;
        margin: 0.9rem 0 0.45rem;
    }

    .cad-subtitle {
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 0;
    }

    .cad-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 1rem;
    }

    .cad-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: #fff;
        border-radius: 999px;
        padding: 0.45rem 0.85rem;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .cad-card {
        border: 1px solid var(--cad-border);
        border-radius: 18px;
        box-shadow: 0 16px 40px rgba(29, 53, 87, 0.08);
    }

    .cad-summary {
        border: 1px solid var(--cad-border);
        border-radius: 16px;
        background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        padding: 1rem;
        height: 100%;
    }

    .cad-summary__label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--cad-muted);
        margin-bottom: 0.35rem;
    }

    .cad-summary__value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--cad-primary);
    }

    .cad-section {
        padding: 0;
        margin-bottom: 1.25rem;
    }

    .cad-section__head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .cad-section__head h5 {
        margin-bottom: 0.2rem;
        color: var(--cad-primary);
        font-weight: 700;
        font-size: 1rem;
    }

    .cad-section__head p {
        margin-bottom: 0;
        color: var(--cad-muted);
        font-size: 0.86rem;
    }

    .cad-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.9rem 1rem;
    }

    .cad-grid__item {
        border-bottom: 1px dashed #e5ebf5;
        padding-bottom: 0.65rem;
    }

    .cad-grid__label {
        display: block;
        font-size: 0.76rem;
        color: var(--cad-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.25rem;
    }

    .cad-grid__value {
        color: #243b5a;
        font-weight: 600;
        word-break: break-word;
    }

    .cad-table {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #dfe8f4;
    }

    .cad-table thead th {
        background-color: #edf4ff !important;
        color: var(--cad-primary);
        font-weight: 700;
        border-color: #dce7fa;
    }

    .cad-actions {
        display: flex;
        justify-content: flex-end;
        position: relative;
        z-index: 2;
    }

    .cad-actions .btn-group .btn {
        border-color: #d7e3f4;
        color: var(--cad-primary);
        background: #f8fbff;
        font-weight: 600;
    }

    .cad-actions .btn-group .btn:hover,
    .cad-actions .btn-group .btn:focus {
        background: #edf4ff;
        color: var(--cad-primary);
    }

    .cad-actions .dropdown-menu {
        border: 1px solid #dde7f5;
        border-radius: 14px;
        padding: 0.45rem 0;
        box-shadow: 0 16px 36px rgba(21, 53, 103, 0.12);
    }

    .cad-actions .dropdown-item {
        padding: 0.55rem 1rem;
    }

    .cad-section__action {
        flex-shrink: 0;
    }

    @media (max-width: 767.98px) {
        .cad-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@section('title')
    Customer Account Information
@endsection

@section('content')
    <div class="cad-shell">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Customer Account Information</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('customer-accounts.lists') }}">Customer Accounts</a></li>
                    <li class="breadcrumb-item active">Information</li>
                </ol>
            </div>

        </div>
        </div>
    </div>

    <div class="card cad-hero mb-4">
        <div class="cad-hero__body">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="cad-kicker">Customer Account Profile</span>
                    <h2 class="cad-title">{{ $customer_account->account_name ?? $customer_account->phone }}</h2>
                    <p class="cad-subtitle">Review account holder details, connected payment channel, and linked mandates from one customer account workspace.</p>
                    <div class="cad-meta">
                        <span class="cad-chip">{{ $customer_account->accountType->name ?? 'NIL' }}</span>
                        <span class="cad-chip">{{ $customer_account->account->name ?? 'NIL' }}</span>
                        <span class="cad-chip">{{ $customer_account->status ?? 'NIL' }}</span>
                    </div>
                </div>
                <div class="col-lg-4 mt-3 mt-lg-0">
                    <div class="cad-actions">
                        @if ($customer_account->status == 'partial' || $customer_account->status == 'rejected')
                            <div class="btn-group">
                                <button class="btn btn-sm btn-light" type="button">
                                    Account Actions
                                </button>
                                <button type="button" class="btn btn-sm btn-light dropdown-toggle dropdown-toggle-split"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-chevron-down font-size-14"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="{{ route('customer-accounts.edit', $customer_account->id) }}" title="Edit"
                                        class="dropdown-item">
                                        <i class="bx bx-edit font-size-12 me-2"></i> Edit
                                    </a>

                                    <a href="{{ route('customer-accounts.delete', $customer_account->id) }}" title="Delete"
                                        class="dropdown-item text-danger delete">
                                        <i class="bx bx-trash font-size-12 me-2"></i> Delete
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="cad-summary">
                <div class="cad-summary__label">Mandates</div>
                <div class="cad-summary__value">{{ number_format($detailStats['mandates_total']) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="cad-summary">
                <div class="cad-summary__label">Approved Mandates</div>
                <div class="cad-summary__value text-success">{{ number_format($detailStats['mandates_approved']) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="cad-summary">
                <div class="cad-summary__label">Pending Mandates</div>
                <div class="cad-summary__value text-warning">{{ number_format($detailStats['mandates_pending']) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="cad-summary">
                <div class="cad-summary__label">Approved Value</div>
                <div class="cad-summary__value">{{ number_format($detailStats['mandates_approved_value']) }}</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card cad-card">
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

                    <div class="cad-section">
                        <div class="cad-section__head">
                            <div>
                                <h5>Account Overview</h5>
                                <p>Core identity and channel details arranged the same way as mandate overview.</p>
                            </div>
                        </div>

                        <div class="cad-grid">
                            <div class="cad-grid__item">
                                <span class="cad-grid__label">Payment Channel</span>
                                <span class="cad-grid__value">{{ $customer_account->accountType->name ?? '-' }}</span>
                            </div>
                            <div class="cad-grid__item">
                                <span class="cad-grid__label">Bank/MNO Name</span>
                                <span class="cad-grid__value">{{ $customer_account->account->name ?? '-' }}</span>
                            </div>
                            <div class="cad-grid__item">
                                <span class="cad-grid__label">Account Holder</span>
                                <span class="cad-grid__value">{{ $customer_account->account_name ?? '-' }}</span>
                            </div>
                            <div class="cad-grid__item">
                                <span class="cad-grid__label">Account Number</span>
                                <span class="cad-grid__value">{{ mask_account_number($customer_account->account_number ?? $customer_account->phone) }}</span>
                            </div>
                            <div class="cad-grid__item">
                                <span class="cad-grid__label">Phone</span>
                                <span class="cad-grid__value">{{ $customer_account->phone ?? '-' }}</span>
                            </div>
                            <div class="cad-grid__item">
                                <span class="cad-grid__label">ID Number</span>
                                <span class="cad-grid__value">{{ $customer_account->customer->id_number ?? 'NIL' }}</span>
                            </div>
                            <div class="cad-grid__item">
                                <span class="cad-grid__label">Created On</span>
                                <span class="cad-grid__value">{{ date('M d, Y H:i', strtotime($customer_account->created_at)) }}</span>
                            </div>
                            <div class="cad-grid__item">
                                <span class="cad-grid__label">Customer Status</span>
                                <span class="cad-grid__value text-capitalize">{{ $customer_account->status ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="cad-section">
                        <div class="cad-section__head">
                            <div>
                                <h5>Payment Mandates</h5>
                                <p>Mandates linked to this account, presented in the same operational style as mandate details.</p>
                            </div>
                            <div class="cad-section__action">
                                <a href="{{ route('payment-mandate.create-deduction', [$customer_account->uuid]) }}"
                                    title="New Payment Mandate" class="btn btn-primary btn-sm">
                                    <i class="bx bx-plus font-size-12 me-1"></i> Create Mandate
                                </a>
                            </div>
                        </div>

                        @if ($customer_account->debitOrders->isNotEmpty())
                            <div class="table-responsive cad-table">
                                <table class="table table-bordered mb-0" id="dynamicTable">
                                    <thead>
                                        <tr>
                                            <th width="3%">#</th>
                                            <th width="12%">Total Amount</th>
                                            <th width="10%">Debit Type</th>
                                            <th width="10%">Installment Amount</th>
                                            <th width="10%">Peridiocity</th>
                                            <th width="10%">Number of Deduction</th>
                                            <th width="10%">Deduction <br />Start Date</th>
                                            <th width="10%">Deduction <br />End Date</th>
                                            <th width="15%">Evidence <br />of Aggrement</th>
                                            <th width="8%">Approved</th>
                                        </tr>
                                    </thead>
                                    <tbody>
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
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-danger mb-0">No any payment mandate at the moment!</div>
                        @endif
                    </div>
                </div>
                <!--./card-body -->
            </div>
            <!--./card-->
        </div>
        <!--./col -->
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
