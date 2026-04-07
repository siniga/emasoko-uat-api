@extends('layouts.master')

@section('css')
    <link href="{{ URL::asset('/assets/css/blade-show.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('title')
    Customer Account Information
@endsection

@section('content')
    <div class="detail-shell">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Customer Account Information</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('customer-accounts.lists') }}">Customer
                                    Accounts</a></li>
                            <li class="breadcrumb-item active">Information</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>

        <div class="card detail-hero mb-4">
            <div class="detail-hero__body">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <span class="detail-kicker">Customer Account Profile</span>
                        <h2 class="detail-title">{{ $customer_account->account_name ?? $customer_account->phone }}</h2>
                        <p class="detail-subtitle">Review account holder details, connected payment channel, and linked
                            mandates from one customer account workspace.</p>
                        <div class="detail-meta">
                            <span class="detail-chip">{{ $customer_account->accountType->name ?? 'NIL' }}</span>
                            <span class="detail-chip">{{ $customer_account->account->name ?? 'NIL' }}</span>
                            <span class="detail-chip">{{ $customer_account->status ?? 'NIL' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label">Mandates</div>
                    <div class="detail-summary__value">{{ number_format($detailStats['mandates_total']) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="detail-summary bg-success-1 shadow h-96 py-2">
                    <div class="detail-summary__label">Approved Mandates</div>
                    <div class="detail-summary__value">{{ number_format($detailStats['mandates_approved']) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="detail-summary bg-danger-1 shadow h-96 py-2">
                    <div class="detail-summary__label">Pending Mandates</div>
                    <div class="detail-summary__value">{{ number_format($detailStats['mandates_pending']) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="detail-summary bg-danger shadow h-96 py-2">
                    <div class="detail-summary__label">Approved Value</div>
                    <div class="detail-summary__value">{{ number_format($detailStats['mandates_approved_value']) }}</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card detail-card">
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

                        <div class="detail-section">
                            <div class="detail-section__head">
                                <div>
                                    <h5>Account Overview</h5>
                                    <p>Core identity and channel details arranged the same way as mandate overview.</p>
                                </div>

                                <div class="actions">
                                    @if ($customer_account->status == 'partial' || $customer_account->status == 'rejected')
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-primary" type="button">
                                                Actions
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="mdi mdi-chevron-down font-size-14"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a href="{{ route('customer-accounts.edit', $customer_account->id) }}"
                                                    title="Edit" class="dropdown-item">
                                                    <i class="bx bx-pencil me-2"></i> Edit
                                                </a>

                                                <a href="{{ route('customer-accounts.delete', $customer_account->id) }}"
                                                    title="Delete" class="dropdown-item text-danger delete">
                                                    <i class="bx bx-trash me-2"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="detail-grid">
                                <div class="detail-grid__item">
                                    <span class="detail-grid__label">Payment Channel</span>
                                    <span
                                        class="detail-grid__value">{{ $customer_account->accountType->name ?? '-' }}</span>
                                </div>
                                <div class="detail-grid__item">
                                    <span class="detail-grid__label">Bank/MNO Name</span>
                                    <span class="detail-grid__value">{{ $customer_account->account->name ?? '-' }}</span>
                                </div>
                                <div class="detail-grid__item">
                                    <span class="detail-grid__label">Account Holder</span>
                                    <span class="detail-grid__value">{{ $customer_account->account_name ?? '-' }}</span>
                                </div>
                                <div class="detail-grid__item">
                                    <span class="detail-grid__label">Account Number</span>
                                    <span
                                        class="detail-grid__value">{{ mask_account_number($customer_account->account_number ?? $customer_account->phone) }}</span>
                                </div>
                                <div class="detail-grid__item">
                                    <span class="detail-grid__label">Phone</span>
                                    <span class="detail-grid__value">{{ $customer_account->phone ?? '-' }}</span>
                                </div>
                                <div class="detail-grid__item">
                                    <span class="detail-grid__label">ID Number</span>
                                    <span
                                        class="detail-grid__value">{{ $customer_account->customer->id_number ?? 'NIL' }}</span>
                                </div>
                                <div class="detail-grid__item">
                                    <span class="detail-grid__label">Created On</span>
                                    <span
                                        class="detail-grid__value">{{ date('M d, Y H:i', strtotime($customer_account->created_at)) }}</span>
                                </div>
                                <div class="detail-grid__item">
                                    <span class="detail-grid__label">Customer Status</span>
                                    <span
                                        class="detail-grid__value text-capitalize">{{ $customer_account->status ?? '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <div class="detail-section__head">
                                <div>
                                    <h5>Payment Mandates</h5>
                                    <p>Mandates linked to this account, presented in the same operational style as mandate
                                        details.</p>
                                </div>
                                <div class="detail-section__action">
                                    <a href="{{ route('payment-mandate.create-deduction', [$customer_account->uuid]) }}"
                                        title="New Payment Mandate" class="btn btn-primary btn-sm">
                                        <i class="bx bx-plus font-size-12 me-1"></i> Create Mandate
                                    </a>
                                </div>
                            </div>

                            @if ($customer_account->debitOrders->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-bordered dt-responsive nowrap w-100 mb-0" id="dynamicTable">
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
