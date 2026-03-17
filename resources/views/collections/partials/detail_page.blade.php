<style>
    .ccd-shell {
        --ccd-primary: #153567;
        --ccd-border: #dce6f5;
        --ccd-muted: #6b7c93;
    }

    .ccd-hero {
        border: 1px solid #dce7f7;
        border-radius: 18px;
        padding: 1.35rem;
        background: linear-gradient(135deg, #153567 0%, #214a8b 100%);
        color: #fff;
        box-shadow: 0 18px 40px rgba(21, 53, 103, 0.18);
    }

    .ccd-hero__eyebrow {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        opacity: 0.75;
        margin-bottom: 0.35rem;
    }

    .ccd-hero__title {
        font-size: 1.35rem;
        font-weight: 700;
        margin-bottom: 0.35rem;
    }

    .ccd-hero__hint {
        max-width: 60rem;
        color: rgba(255, 255, 255, 0.85);
        margin-bottom: 0;
    }

    .ccd-summary {
        border: 1px solid var(--ccd-border);
        border-radius: 16px;
        background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        padding: 1rem;
        height: 100%;
    }

    .ccd-summary__label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--ccd-muted);
        margin-bottom: 0.35rem;
    }

    .ccd-summary__value {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--ccd-primary);
    }

    .ccd-table {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #dfe8f4;
    }

    .ccd-table thead th {
        background: #edf4ff;
        color: var(--ccd-primary);
        font-weight: 700;
        border-color: #dce7fa;
    }
</style>

<div class="ccd-shell">
    <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ $title }}</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item">
                                <a href="{{ route($backRoute) }}">
                                    {{ $collectionType === \App\Services\CollectionAnalyticsService::TYPE_ACTUAL ? 'Actual Collection' : 'Expected Collection' }}
                                </a>
                            </li>
                            <li class="breadcrumb-item active">{{ $partner->business_name }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="ccd-hero mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="ccd-hero__eyebrow">
                        {{ $collectionType === \App\Services\CollectionAnalyticsService::TYPE_ACTUAL ? 'Actual Collection Detail' : 'Expected Collection Detail' }}
                    </div>
                    <div class="ccd-hero__title">{{ strtoupper($partner->business_name) }}</div>
                    <p class="ccd-hero__hint">
                        {{
                            $collectionType === \App\Services\CollectionAnalyticsService::TYPE_ACTUAL
                                ? 'This detail view uses effective collection date and includes fully paid plus partial paid installments, matching the actual collection export.'
                                : 'This detail view uses deduction date and shows the same installment set used for the expected collection summary and export.'
                        }}
                    </p>
                </div>

                <div class="text-sm-end">
                    <a href="{{ route($exportRoute, $partner->uuid) }}?{{ http_build_query($post_data) }}"
                        class="btn btn-light btn-sm">
                        <i class="bx bx-export"></i> Export XLS
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="ccd-summary">
                    <div class="ccd-summary__label">Collections</div>
                    <div class="ccd-summary__value">{{ number_format($listStats['collections'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="ccd-summary">
                    <div class="ccd-summary__label">Expected Amount</div>
                    <div class="ccd-summary__value">{{ number_format($listStats['expected_amount'] ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="ccd-summary">
                    <div class="ccd-summary__label">Collected Amount</div>
                    <div class="ccd-summary__value text-success">{{ number_format($listStats['paid_amount'] ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="ccd-summary">
                    <div class="ccd-summary__label">Outstanding</div>
                    <div class="ccd-summary__value text-warning">{{ number_format($listStats['outstanding_amount'] ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="ccd-summary">
                    <div class="ccd-summary__label">Success Rate</div>
                    <div class="ccd-summary__value">{{ number_format($listStats['success_rate'] ?? 0, 2) }}%</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive ccd-table">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th width="4%">#</th>
                                <th>Mandate Ref</th>
                                <th>Channel</th>
                                <th>Account Name</th>
                                <th>Account No.</th>
                                <th class="text-end">Expected Amt</th>
                                <th class="text-end">Paid Amt</th>
                                <th class="text-end">Outstanding</th>
                                <th>Deduction Date</th>
                                <th>Collection Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($arr_installments as $val)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $val['reference'] }}</td>
                                    <td>{{ $val['channel'] }}</td>
                                    <td>{{ $val['account_name'] }}</td>
                                    <td>{{ function_exists('mask_account_number') ? mask_account_number($val['account_number']) : $val['account_number'] }}</td>
                                    <td class="text-end">{{ number_format($val['amount'], 2) }}</td>
                                    <td class="text-end">{{ number_format($val['last_paid_amount'], 2) }}</td>
                                    <td class="text-end">{{ number_format($val['outstanding_amount'], 2) }}</td>
                                    <td>{{ $val['payment_date'] ?? '-' }}</td>
                                    <td>{{ $val['effective_payment_date'] ?? '-' }}</td>
                                    <td>
                                        @php $normalizedStatus = strtoupper($val['status']); @endphp
                                        @if ($normalizedStatus === 'CLOSED')
                                            <span class="badge bg-success-subtle text-success">PAID</span>
                                        @elseif ($normalizedStatus === 'PARTIAL_PAID')
                                            <span class="badge bg-warning-subtle text-warning">PARTIAL PAID</span>
                                        @elseif (in_array($normalizedStatus, ['FAILED', 'FUNDSCHECK']))
                                            <span class="badge bg-danger-subtle text-danger">{{ $val['status_label'] }}</span>
                                        @else
                                            <span class="badge bg-primary-subtle text-primary">{{ $val['status_label'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">No installment data found for the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
