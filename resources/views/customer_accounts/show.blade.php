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
        border: 1px solid #e7edf6;
        border-radius: 16px;
        background: #fbfcfe;
        padding: 1.2rem;
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
        margin-bottom: 0.15rem;
        color: var(--cad-primary);
        font-weight: 700;
    }

    .cad-section__head p {
        margin-bottom: 0;
        color: var(--cad-muted);
    }

    .cad-kv {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.85rem;
    }

    .cad-kv__item {
        border: 1px solid #e4ebf5;
        border-radius: 14px;
        background: #fff;
        padding: 0.8rem 0.95rem;
    }

    .cad-kv__label {
        display: block;
        font-size: 0.74rem;
        color: var(--cad-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.2rem;
    }

    .cad-kv__value {
        color: var(--cad-primary);
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

    @media (max-width: 767.98px) {
        .cad-kv {
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
            <span class="cad-kicker">Customer Account Profile</span>
            <h2 class="cad-title">{{ $customer_account->account_name ?? $customer_account->phone }}</h2>
            <p class="cad-subtitle">Review account holder details, connected payment channel, and linked mandates from one customer account workspace.</p>
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

                    <div class="cad-section">
                        <div class="cad-section__head">
                            <div>
                                <h5>Account Overview</h5>
                                <p>Core account holder and payment channel details for this customer account.</p>
                            </div>
                        </div>

                        <div class="cad-kv">
                            <div class="cad-kv__item">
                                <span class="cad-kv__label">Payment Channel</span>
                                <span class="cad-kv__value">{{ $customer_account->accountType->name ?? '-' }}</span>
                            </div>
                            <div class="cad-kv__item">
                                <span class="cad-kv__label">Bank/MNO Name</span>
                                <span class="cad-kv__value">{{ $customer_account->account->name ?? '-' }}</span>
                            </div>
                            <div class="cad-kv__item">
                                <span class="cad-kv__label">Account Holder</span>
                                <span class="cad-kv__value">{{ $customer_account->account_name ?? '-' }}</span>
                            </div>
                            <div class="cad-kv__item">
                                <span class="cad-kv__label">Account Number</span>
                                <span class="cad-kv__value">{{ $customer_account->account_number ?? $customer_account->phone }}</span>
                            </div>
                            <div class="cad-kv__item">
                                <span class="cad-kv__label">Phone</span>
                                <span class="cad-kv__value">{{ $customer_account->phone ?? '-' }}</span>
                            </div>
                            <div class="cad-kv__item">
                                <span class="cad-kv__label">ID Number</span>
                                <span class="cad-kv__value">{{ $customer_account->customer->id_number ?? 'NIL' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <h5 class="text-capitalize">Payment Mandates</h5>
                        </div>

                        <div class="text-sm-end">
                            <a href="{{ route('payment-mandate.create-deduction', [$customer_account->uuid]) }}"
                                title="New Payment Mandate" class="btn btn-primary text-medium btn-sm">
                                <i class="bx bx-plus font-size-12"></i> Create Mandate
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
                        <div class="alert alert-danger">No any payment mandate at the moment!</div>
                    @endif
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
