@php
    $isActualCollection = $collectionType === \App\Services\CollectionAnalyticsService::TYPE_ACTUAL;
    $collectionsLabel = $isActualCollection ? 'Settled Records' : 'Collections';
    $secondaryCountLabel = $isActualCollection ? 'Paid In Full' : 'Success';
    $secondaryCountValue = $isActualCollection
        ? $listStats['fully_paid_collections'] ?? 0
        : $listStats['success_collections'] ?? 0;
    $tertiaryCountLabel = $isActualCollection ? 'Partial Paid' : 'Failed';
    $tertiaryCountValue = $isActualCollection
        ? $listStats['partial_paid_collections'] ?? 0
        : $listStats['failed_collections'] ?? 0;
    $paidAmountLabel = $isActualCollection ? 'Settled Amount' : 'Paid Amount';
@endphp

<div class="sp-list-shell collection-detail-shell">
    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            {{ $title }}
        @endslot
    @endcomponent

    <div class="collection-detail-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="collection-detail-hero__eyebrow">
                    {{ $isActualCollection ? 'Actual Collection Detail' : 'Expected Collection Detail' }}
                </div>
                <div class="collection-detail-hero__title">{{ strtoupper($partner->business_name) }}</div>
                <p class="collection-detail-hero__hint">
                    {{ $isActualCollection
                        ? 'This view shows settled installments only, split between fully paid and partial paid records using effective collection date.'
                        : 'This view shows all scheduled installments in the filtered period, together with success, failed, and outstanding progress so far.' }}
                </p>
            </div>

            <div class="text-sm-end">
                <a href="{{ route($backRoute) }}?{{ http_build_query($post_data) }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-arrow-back"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-xl-2">
            <div class="detail-summary border-top-secondary shadow h-96 py-2">
                <div class="detail-summary__label text-muted">{{ $collectionsLabel }}</div>
                <div class="detail-summary__value text-secondary">{{ number_format($listStats['collections'] ?? 0) }}
                </div>
            </div>
        </div>
        <div class="col-md-3 col-xl-2">
            <div class="detail-summary border-top-success shadow h-96 py-2">
                <div class="detail-summary__label text-muted">{{ $secondaryCountLabel }}</div>
                <div class="detail-summary__value text-success">{{ number_format($secondaryCountValue) }}</div>
            </div>
        </div>
        <div class="col-md-3 col-xl-2">
            <div class="detail-summary border-top-danger shadow h-96 py-2">
                <div class="detail-summary__label text-muted">{{ $tertiaryCountLabel }}</div>
                <div class="detail-summary__value text-danger">{{ number_format($tertiaryCountValue) }}</div>
            </div>
        </div>
        <div class="col-md-3 col-xl-2">
            <div class="detail-summary border-top-primary shadow h-96 py-2">
                <div class="detail-summary__label text-muted">Expected Amount</div>
                <div class="detail-summary__value text-primary">
                    {{ number_format($listStats['expected_amount'] ?? 0, 2) }}</div>
            </div>
        </div>

        <div class="col-md-4 col-xl-4">
            <div class="detail-summary border-top-success shadow h-96 py-2">
                <div class="detail-summary__split">
                    <div class="detail-summary__metric">
                        <div class="detail-summary__label text-muted">{{ $paidAmountLabel }}</div>
                        <div class="detail-summary__value text-success">
                            {{ number_format($listStats['paid_amount'] ?? 0, 2) }}</div>
                    </div>
                    <div class="detail-summary__metric detail-summary__metric--count">
                        <div class="detail-summary__label text-muted">Success Rate</div>
                        <div class="detail-summary__value text-success">
                            {{ number_format($listStats['success_rate'] ?? 0, 2) }}%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card sp-list-card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div></div>

                <div class="text-sm-end">
                    <a href="{{ route($exportRoute, array_merge(['uuid' => $partner->uuid], $post_data, ['format' => 'xls'])) }}"
                        class="btn btn-outline-success btn-sm">
                        <i class="bx bx-export"></i> Export XLS
                    </a>
                    <a href="{{ route($exportRoute, array_merge(['uuid' => $partner->uuid], $post_data, ['format' => 'pdf'])) }}"
                        class="btn btn-outline-danger btn-sm">
                        <i class="bx bxs-file-pdf"></i> Export PDF
                    </a>
                </div>
            </div>

            <div class="table-responsive sp-list-table collection-detail-table">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th width="4%">#</th>
                            <th>Mandate Ref</th>
                            <th>Channel</th>
                            <th>Account Name</th>
                            <th class="text-end">Expected Amt</th>
                            <th class="text-end">{{ $paidAmountLabel }}</th>
                            <th class="text-end">Outstanding</th>
                            <th>Deduction Date</th>
                            <th>Collection Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($arr_installments as $val)
                            @php
                                $normalizedStatus = strtoupper((string) $val['status']);
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $val['reference'] }}</td>
                                <td>{{ $val['channel'] }}</td>
                                <td>{{ $val['account_name'] }}</td>
                                <td class="text-end">{{ number_format($val['amount'], 2) }}</td>
                                <td class="text-end">{{ number_format($val['last_paid_amount'], 2) }}</td>
                                <td class="text-end">{{ number_format($val['outstanding_amount'], 2) }}</td>
                                <td>{{ $val['payment_date'] ?? '-' }}</td>
                                <td>{{ $normalizedStatus === 'OPEN' ? '-' : $val['effective_payment_date'] ?? '-' }}
                                </td>
                                <td>
                                    @if ($normalizedStatus === 'CLOSED')
                                        <span class="badge bg-success-subtle text-success">FULLY PAID</span>
                                    @elseif ($normalizedStatus === 'PARTIAL_PAID')
                                        <span class="badge bg-warning-subtle text-warning">PARTIAL PAID</span>
                                    @elseif (in_array($normalizedStatus, ['FAILED', 'FUNDSCHECK'], true))
                                        <span
                                            class="badge bg-danger-subtle text-danger">{{ $val['status_label'] }}</span>
                                    @else
                                        <span
                                            class="badge bg-primary-subtle text-primary">{{ $val['status_label'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">No installment data found for the
                                    selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (!empty($arr_installments))
                        <tfoot>
                            <tr>
                                <th colspan="5">Total</th>
                                <th class="text-end">{{ number_format($listStats['expected_amount'] ?? 0, 2) }}</th>
                                <th class="text-end">{{ number_format($listStats['paid_amount'] ?? 0, 2) }}</th>
                                <th class="text-end">{{ number_format($listStats['outstanding_amount'] ?? 0, 2) }}</th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
