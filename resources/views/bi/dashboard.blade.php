@extends('layouts.master')

@section('title')
    BI Dashboard
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-sm-0 font-size-18">BI Dashboard : {{ request('channel') ?? 'All Channels' }}</h4>
                    <p class="text-muted mb-0 mt-1">Operational view for collection health, exceptions, and short-term cash visibility.</p>
                </div>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">BI Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card border-left-info bg-primary shadow h-96 py-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-xss font-weight-600 text-white mb-1">Active Mandates</div>
                            <div class="h5 mb-0 font-weight-700 text-white">{{ number_format($stats['active_mandate_count']) }}</div>
                            <div class="text-white-50 mt-2">Portfolio value {{ number_format($stats['approved_mandate_value']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bx bx-check-shield fa-3x text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-info bg-success-1 shadow h-96 py-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-xss font-weight-600 text-white mb-1">Expected Collection ({{ $stats['month'] }})</div>
                            <div class="h5 mb-0 font-weight-700 text-white">{{ number_format($stats['this_month_expected']) }}</div>
                            <div class="text-white-50 mt-2">{{ number_format($stats['today_expected']) }} due today</div>
                        </div>
                        <div class="col-auto">
                            <i class="bx bx-calendar-event fa-3x text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-info bg-info shadow h-96 py-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-xss font-weight-600 text-white mb-1">Actual Collection ({{ $stats['month'] }})</div>
                            <div class="h5 mb-0 font-weight-700 text-white">{{ number_format($stats['this_month_paid']) }}</div>
                            <div class="text-white-50 mt-2">{{ number_format($stats['today_paid']) }} collected today</div>
                        </div>
                        <div class="col-auto">
                            <i class="bx bx-wallet-alt fa-3x text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-info bg-warning shadow h-96 py-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-xss font-weight-600 text-white mb-1">Overdue Outstanding</div>
                            <div class="h5 mb-0 font-weight-700 text-white">{{ number_format($stats['overdue_unpaid_amount']) }}</div>
                            <div class="text-white-50 mt-2">{{ number_format($stats['overdue_unpaid_count']) }} overdue installments</div>
                        </div>
                        <div class="col-auto">
                            <i class="bx bx-error-circle fa-3x text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card shadow h-96 py-2">
                <div class="card-body">
                    <div class="text-muted text-uppercase fw-semibold font-size-11 mb-1">Today Collection Rate</div>
                    <div class="d-flex align-items-end justify-content-between">
                        <div>
                            <h3 class="mb-1">{{ number_format($stats['today_rate'], 2) }}%</h3>
                            <div class="text-muted">Expected {{ number_format($stats['today_expected']) }}</div>
                        </div>
                        <span class="badge bg-soft-primary text-primary">Actual {{ number_format($stats['today_paid']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow h-96 py-2">
                <div class="card-body">
                    <div class="text-muted text-uppercase fw-semibold font-size-11 mb-1">Month Collection Rate</div>
                    <div class="d-flex align-items-end justify-content-between">
                        <div>
                            <h3 class="mb-1">{{ number_format($stats['this_month_rate'], 2) }}%</h3>
                            <div class="text-muted">Expected {{ number_format($stats['this_month_expected']) }}</div>
                        </div>
                        <span class="badge bg-soft-success text-success">Actual {{ number_format($stats['this_month_paid']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow h-96 py-2">
                <div class="card-body">
                    <div class="text-muted text-uppercase fw-semibold font-size-11 mb-1">Partial Paid Exposure</div>
                    <div class="d-flex align-items-end justify-content-between">
                        <div>
                            <h3 class="mb-1">{{ number_format($stats['partial_paid_amount']) }}</h3>
                            <div class="text-muted">{{ number_format($stats['partial_paid_count']) }} installments need follow-up</div>
                        </div>
                        <span class="badge bg-soft-warning text-warning">Partial</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow h-96 py-2">
                <div class="card-body">
                    <div class="text-muted text-uppercase fw-semibold font-size-11 mb-1">Failed Today</div>
                    <div class="d-flex align-items-end justify-content-between">
                        <div>
                            <h3 class="mb-1">{{ number_format($stats['failed_today_amount']) }}</h3>
                            <div class="text-muted">{{ number_format($stats['failed_today_count']) }} deductions failed today</div>
                        </div>
                        <span class="badge bg-soft-danger text-danger">Attention</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow h-96 py-2">
                <div class="card-body">
                    <div class="text-muted text-uppercase fw-semibold font-size-11 mb-1">Next 7 Days Pipeline</div>
                    <h3 class="mb-1">{{ number_format($stats['upcoming_next_7_days_amount']) }}</h3>
                    <div class="text-muted">{{ number_format($stats['upcoming_next_7_days_count']) }} installments due from tomorrow</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow h-96 py-2">
                <div class="card-body">
                    <div class="text-muted text-uppercase fw-semibold font-size-11 mb-1">Pending Mandates</div>
                    <h3 class="mb-1">{{ number_format($stats['number_of_pending_mandate']) }}</h3>
                    <div class="text-muted">Value {{ number_format($stats['pending_mandate_value']) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow h-96 py-2">
                <div class="card-body">
                    <div class="text-muted text-uppercase fw-semibold font-size-11 mb-1">Failed Mandates</div>
                    <h3 class="mb-1">{{ number_format($stats['number_of_failed_mandate']) }}</h3>
                    <div class="text-muted">Value {{ number_format($stats['failed_mandate_value']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="clearfix">
                        <div class="float-end">
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm" id="mandate-value-select-year">
                                    @php $currentYear = date('Y'); @endphp
                                    <option value="">Select</option>
                                    @foreach (range(2024, $currentYear) as $value)
                                        <option value="{{ $value }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                                <label class="input-group-text">Year</label>
                            </div>
                        </div>
                        <h4 class="card-title mb-3">Payment Mandate Values</h4>
                    </div>

                    <div id="mandate-value-chart"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Payment Mandate Rates</h4>
                    <div id="consent-rate-chart" class="apex-charts"></div>
                    <div class="text-center text-muted">
                        <div class="row">
                            <div class="col-4">
                                <div class="my-2">
                                    <p class="text-xs">
                                        <i class="mdi mdi-circle text-success-1 my-1"></i> Approved :
                                        {{ $stats['number_of_approved_mandate'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="my-2">
                                    <p class="text-xs">
                                        <i class="mdi mdi-circle text-warning my-1"></i> Pending :
                                        {{ $stats['number_of_pending_mandate'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="my-2">
                                    <p class="text-xs">
                                        <i class="mdi mdi-circle text-danger my-1"></i> Failed :
                                        {{ $stats['number_of_failed_mandate'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="clearfix">
                        <div class="float-end">
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm" id="collected-mandate-value-select-year">
                                    @php $currentYear = date('Y'); @endphp
                                    <option value="">Select</option>
                                    @foreach (range(2024, $currentYear + 5) as $value)
                                        <option value="{{ $value }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                                <label class="input-group-text">Year</label>
                            </div>
                        </div>
                        <h4 class="card-title mb-3">Expected vs Collected</h4>
                    </div>

                    <div id="collected-mandate-value-chart"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="clearfix">
                        <div class="float-end">
                            <div class="input-group input-group-sm">
                                <input type="date" name="start_at" class="form-control" id="collected-mandate-value-weekly-date" />
                            </div>
                        </div>
                        <h4 class="card-title mb-3">Weekly Collections</h4>
                    </div>

                    <div id="collected-mandate-value-weekly-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Partners Needing Attention</h4>
                        <span class="text-muted">{{ $stats['month'] }} performance view</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th width="4%">#</th>
                                    <th>Service Provider</th>
                                    <th class="text-end">Expected Amount</th>
                                    <th class="text-end">Collected Amount</th>
                                    <th class="text-end">Outstanding Amount</th>
                                    <th class="text-end">Rate (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($partnerAttention as $index => $partner)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <a href="{{ route('collections.actual-monthly-detail', $partner['uuid']) }}?month={{ $postData['month'] }}&year={{ $postData['year'] }}"
                                                class="fw-semibold">
                                                {{ strtoupper($partner['partner_name']) }}
                                            </a>
                                        </td>
                                        <td class="text-end">{{ number_format($partner['expected_amount']) }}</td>
                                        <td class="text-end">{{ number_format($partner['collected_amount']) }}</td>
                                        <td class="text-end">{{ number_format($partner['outstanding_amount']) }}</td>
                                        <td class="text-end">
                                            <span class="badge {{ $partner['rate'] >= 80 ? 'bg-soft-success text-success' : ($partner['rate'] >= 50 ? 'bg-soft-warning text-warning' : 'bg-soft-danger text-danger') }}">
                                                {{ number_format($partner['rate'], 2) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No partner performance data available for the selected channel.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Recent Successful Collections</h4>
                        <span class="badge bg-soft-success text-success">Cash movement</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Service Provider</th>
                                    <th>Collection Date</th>
                                    <th class="text-end">Paid</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentSuccessful as $installment)
                                    <tr>
                                        <td>
                                            <a href="{{ route('payment-mandate.show', $installment->debitOrder->uuid) }}" class="fw-semibold">
                                                {{ $installment->debitOrder->reference }}
                                            </a>
                                        </td>
                                        <td>{{ optional($installment->debitOrder->partner)->business_name }}</td>
                                        <td>{{ $installment->effective_payment_date ? \Carbon\Carbon::parse($installment->effective_payment_date)->format('d M Y') : '-' }}</td>
                                        <td class="text-end">{{ number_format($installment->current_balance) }}</td>
                                        <td>
                                            <span class="badge {{ $installment->status === 'CLOSED' ? 'bg-soft-success text-success' : 'bg-soft-warning text-warning' }}">
                                                {{ str_replace('_', ' ', $installment->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No recent successful collection activity found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Recent Failed Deductions</h4>
                        <span class="badge bg-soft-danger text-danger">Exceptions</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Service Provider</th>
                                    <th>Due Date</th>
                                    <th class="text-end">Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentFailed as $installment)
                                    <tr>
                                        <td>
                                            <a href="{{ route('payment-mandate.show', $installment->debitOrder->uuid) }}" class="fw-semibold">
                                                {{ $installment->debitOrder->reference }}
                                            </a>
                                        </td>
                                        <td>{{ optional($installment->debitOrder->partner)->business_name }}</td>
                                        <td>{{ $installment->payment_date ? \Carbon\Carbon::parse($installment->payment_date)->format('d M Y') : '-' }}</td>
                                        <td class="text-end">{{ number_format($installment->installment_amount) }}</td>
                                        <td>
                                            <span class="badge bg-soft-danger text-danger">
                                                {{ str_replace('_', ' ', $installment->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No recent failed deductions found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        window.DASHBOARD_FILTERS = @json($postData);
    </script>
    <script src="{{ URL::asset('/assets/js/charts/dashboard.js') }}"></script>
@endsection
