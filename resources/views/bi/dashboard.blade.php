@extends('layouts.master')

@section('title')
    BI Dashboard
@endsection

@section('css')
    <style>
        .bi-kpi-card {
            border-radius: 18px;
            border: 1px solid #dce6f5;
            box-shadow: 0 16px 36px rgba(21, 53, 103, 0.08);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .bi-kpi-card .card-body {
            padding: 1rem 1.1rem;
        }

        .bi-kpi-card__row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }

        .bi-kpi-card__label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgba(255, 255, 255, 0.84);
            margin-bottom: 0.35rem;
        }

        .bi-kpi-card__value,
        .bi-kpi-card__side {
            color: #fff;
            font-size: 1.4rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .bi-kpi-card__meta {
            margin-top: 0.55rem;
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.82rem;
        }

        .bi-kpi-card--primary {
            background: linear-gradient(135deg, #153567 0%, #1f4a88 100%);
        }

        .bi-kpi-card--success {
            background: linear-gradient(135deg, #156f52 0%, #1f9b74 100%);
        }

        .bi-kpi-card--info {
            background: linear-gradient(135deg, #01579b 0%, #0288d1 100%);
        }

        .bi-kpi-card--warning {
            background: linear-gradient(135deg, #9c5a05 0%, #d17b0f 100%);
        }

        .bi-snapshot-card {
            border: 1px solid #dce6f5;
            border-radius: 16px;
            box-shadow: 0 14px 30px rgba(21, 53, 103, 0.08);
            margin-bottom: 1.5rem;
        }

        .bi-snapshot-card .card-body {
            padding: 1rem;
        }

        .bi-snapshot-card__label {
            color: #7b8798;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.35rem;
        }

        .bi-snapshot-card__value {
            color: #153567;
            font-size: 1.55rem;
            font-weight: 800;
            margin-bottom: 0.35rem;
            line-height: 1.1;
        }

        .bi-snapshot-card__meta {
            color: #6b7c93;
            font-size: 0.84rem;
            margin-bottom: 0;
        }

        .bi-chart-card {
            border: 1px solid #dce6f5;
            border-radius: 18px;
            box-shadow: 0 16px 36px rgba(21, 53, 103, 0.08);
        }

        .bi-chart-card .card-body {
            padding: 1.2rem;
        }

        .bi-chart-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .bi-chart-title {
            color: #153567;
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.15rem;
        }

        .bi-chart-hint {
            color: #6b7c93;
            font-size: 0.88rem;
            margin-bottom: 0;
        }

        .bi-pipeline__item + .bi-pipeline__item {
            margin-top: 1rem;
        }

        .bi-pipeline__row {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.35rem;
            align-items: baseline;
        }

        .bi-pipeline__label {
            color: #424242;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .bi-pipeline__value {
            font-size: 0.86rem;
            font-weight: 700;
        }

        .bi-pipeline__value.pipeline-primary { color: #1565C0; }
        .bi-pipeline__value.pipeline-success { color: #2E7D32; }
        .bi-pipeline__value.pipeline-warning { color: #C49000; }
        .bi-pipeline__value.pipeline-danger { color: #C62828; }

        .bi-pipeline__track {
            height: 8px;
            background: #edf1f7;
            border-radius: 999px;
            overflow: hidden;
        }

        .bi-pipeline__bar {
            height: 100%;
            border-radius: 999px;
        }

        .bi-pipeline__bar.pipeline-primary { background: #1565C0; }
        .bi-pipeline__bar.pipeline-success { background: #2E7D32; }
        .bi-pipeline__bar.pipeline-warning { background: #F9A825; }
        .bi-pipeline__bar.pipeline-danger { background: #C62828; }

        .bi-pipeline__footer {
            margin-top: 1rem;
            padding-top: 0.9rem;
            border-top: 1px solid #e7edf6;
            color: #6b7c93;
            font-size: 0.84rem;
        }
    </style>
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
            <div class="card bi-kpi-card bi-kpi-card--primary">
                <div class="card-body">
                    <div class="bi-kpi-card__row">
                        <div>
                            <div class="bi-kpi-card__label">Active Mandates</div>
                            <div class="bi-kpi-card__value">{{ number_format($stats['approved_mandate_value']) }}</div>
                            <div class="bi-kpi-card__meta">Portfolio value in live approved mandates</div>
                        </div>
                        <div class="text-end">
                            <div class="bi-kpi-card__side">{{ number_format($stats['active_mandate_count']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bi-kpi-card bi-kpi-card--success">
                <div class="card-body">
                    <div class="bi-kpi-card__row">
                        <div>
                            <div class="bi-kpi-card__label">Expected Collection ({{ $stats['month'] }})</div>
                            <div class="bi-kpi-card__value">{{ number_format($stats['this_month_expected']) }}</div>
                            <div class="bi-kpi-card__meta">{{ number_format($stats['today_expected']) }} due today</div>
                        </div>
                        <div class="text-end">
                            <div class="bi-kpi-card__side">{{ number_format($stats['today_expected']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bi-kpi-card bi-kpi-card--info">
                <div class="card-body">
                    <div class="bi-kpi-card__row">
                        <div>
                            <div class="bi-kpi-card__label">Actual Collection ({{ $stats['month'] }})</div>
                            <div class="bi-kpi-card__value">{{ number_format($stats['this_month_paid']) }}</div>
                            <div class="bi-kpi-card__meta">{{ number_format($stats['today_paid']) }} collected today</div>
                        </div>
                        <div class="text-end">
                            <div class="bi-kpi-card__side">{{ number_format($stats['today_paid']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bi-kpi-card bi-kpi-card--warning">
                <div class="card-body">
                    <div class="bi-kpi-card__row">
                        <div>
                            <div class="bi-kpi-card__label">Overdue Outstanding</div>
                            <div class="bi-kpi-card__value">{{ number_format($stats['overdue_unpaid_amount']) }}</div>
                            <div class="bi-kpi-card__meta">{{ number_format($stats['overdue_unpaid_count']) }} overdue installments</div>
                        </div>
                        <div class="text-end">
                            <div class="bi-kpi-card__side">{{ number_format($stats['overdue_unpaid_count']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card bi-snapshot-card">
                <div class="card-body">
                    <div class="bi-snapshot-card__label">Today Collection Rate</div>
                    <div class="bi-snapshot-card__value">{{ number_format($stats['today_rate'], 2) }}%</div>
                    <p class="bi-snapshot-card__meta">Expected {{ number_format($stats['today_expected']) }} · Actual {{ number_format($stats['today_paid']) }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bi-snapshot-card">
                <div class="card-body">
                    <div class="bi-snapshot-card__label">Month Collection Rate</div>
                    <div class="bi-snapshot-card__value">{{ number_format($stats['this_month_rate'], 2) }}%</div>
                    <p class="bi-snapshot-card__meta">Expected {{ number_format($stats['this_month_expected']) }} · Actual {{ number_format($stats['this_month_paid']) }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bi-snapshot-card">
                <div class="card-body">
                    <div class="bi-snapshot-card__label">Partial Paid Exposure</div>
                    <div class="bi-snapshot-card__value">{{ number_format($stats['partial_paid_amount']) }}</div>
                    <p class="bi-snapshot-card__meta">{{ number_format($stats['partial_paid_count']) }} installments need follow-up</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bi-snapshot-card">
                <div class="card-body">
                    <div class="bi-snapshot-card__label">Failed Today</div>
                    <div class="bi-snapshot-card__value">{{ number_format($stats['failed_today_amount']) }}</div>
                    <p class="bi-snapshot-card__meta">{{ number_format($stats['failed_today_count']) }} deductions failed today</p>
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
            <div class="card bi-chart-card">
                <div class="card-body">
                    <div class="bi-chart-head">
                        <div>
                            <h4 class="bi-chart-title">Payment Mandate Values</h4>
                            <p class="bi-chart-hint">Monthly trend of total, approved, pending, and failed mandate values.</p>
                        </div>
                        <div class="input-group input-group-sm" style="max-width: 220px;">
                                <select class="form-select form-select-sm" id="mandate-value-select-year">
                                    @php $currentYear = date('Y'); @endphp
                                    <option value="">Select</option>
                                    @foreach (range(2024, $currentYear) as $value)
                                        <option value="{{ $value }}" {{ (int) $postData['year'] === (int) $value ? 'selected' : '' }}>{{ $value }}</option>
                                    @endforeach
                                </select>
                            <label class="input-group-text">Year</label>
                        </div>
                    </div>

                    <div id="mandate-value-chart"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card bi-chart-card">
                <div class="card-body">
                    <div class="bi-chart-head">
                        <div>
                            <h4 class="bi-chart-title">Mandate Pipeline - Live</h4>
                            <p class="bi-chart-hint">Current portfolio split by total mandate value.</p>
                        </div>
                    </div>
                    <div class="bi-pipeline">
                        @foreach ($mandatePipeline as $item)
                            <div class="bi-pipeline__item">
                                <div class="bi-pipeline__row">
                                    <span class="bi-pipeline__label">{{ $item['label'] }}</span>
                                    <span class="bi-pipeline__value pipeline-{{ $item['tone'] }}">
                                        {{ format_short_amount($item['amount']) }} TZS · {{ number_format($item['percent'], 1) }}%
                                    </span>
                                </div>
                                <div class="bi-pipeline__track">
                                    <div class="bi-pipeline__bar pipeline-{{ $item['tone'] }}" style="width: {{ min(max($item['percent'], 0), 100) }}%"></div>
                                </div>
                            </div>
                        @endforeach

                        <div class="bi-pipeline__footer">
                            Total mandate value: <strong>{{ format_short_amount($stats['total_mandate_value']) }} TZS</strong><br>
                            Highest single: <strong>{{ format_short_amount($highestSingleMandate) }} TZS</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card bi-chart-card">
                <div class="card-body">
                    <div class="bi-chart-head">
                        <div>
                            <h4 class="bi-chart-title">Expected vs Collected</h4>
                            <p class="bi-chart-hint">Monthly scheduled collections against actual deductions.</p>
                        </div>
                        <div class="input-group input-group-sm" style="max-width: 220px;">
                            <select class="form-select form-select-sm" id="collected-mandate-value-select-year">
                                @php $currentYear = date('Y'); @endphp
                                <option value="">Select</option>
                                @foreach (range(2024, $currentYear + 5) as $value)
                                    <option value="{{ $value }}" {{ (int) $postData['year'] === (int) $value ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                            <label class="input-group-text">Year</label>
                        </div>
                    </div>

                    <div id="collected-mandate-value-chart"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card bi-chart-card">
                <div class="card-body">
                    <div class="bi-chart-head">
                        <div>
                            <h4 class="bi-chart-title">Weekly Collections</h4>
                            <p class="bi-chart-hint">Short-term movement of due versus collected amounts.</p>
                        </div>
                        <div class="input-group input-group-sm" style="max-width: 220px;">
                            <input type="date" name="start_at" class="form-control" id="collected-mandate-value-weekly-date" />
                        </div>
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
