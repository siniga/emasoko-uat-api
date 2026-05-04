@php
    $isActualCollection = $collectionType === \App\Services\CollectionAnalyticsService::TYPE_ACTUAL;
    $isMonthlyCollection = $collectionPeriod === \App\Services\CollectionAnalyticsService::PERIOD_MONTHLY;
    $collectionsLabel = $isActualCollection ? 'Settled Records' : 'Collections';
    $secondaryCountLabel = $isActualCollection ? 'Paid In Full' : 'Success';
    $secondaryCountValue = $isActualCollection
        ? $listStats['fully_paid_collections'] ?? 0
        : $listStats['success_collections'] ?? 0;
    $tertiaryCountLabel = $isActualCollection ? 'Partial Paid' : 'Failed';
    $tertiaryCountValue = $isActualCollection
        ? $listStats['partial_paid_collections'] ?? 0
        : $listStats['failed_collections'] ?? 0;
    $secondaryRowKey = $isActualCollection ? 'fully_paid_count' : 'success_count';
    $tertiaryRowKey = $isActualCollection ? 'partial_paid_count' : 'failed_count';
    $paidAmountLabel = $isActualCollection ? 'Settled Amount' : 'Paid Amount';
@endphp

<div class="sp-list-shell collection-summary-shell">
    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            {{ $title }}
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="sp-list-filter collection-summary-filter mb-4">
                {!! Form::open(['url' => route($filterRoute), 'method' => 'POST', 'class' => 'form-horizontal']) !!}
                {{ Form::token() }}

                <div class="row">
                    <div class="col-lg-3">
                        <div class="mb-3">
                            <select name="channel" class="form-control">
                                <option value="">-- All Channels --</option>
                                @foreach ($payment_channels as $channel)
                                    <option value="{{ $channel->slug }}"
                                        {{ ($post_data['channel'] ?? null) === $channel->slug ? 'selected' : '' }}>
                                        {{ strtoupper($channel->name ?? $channel->slug) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($isMonthlyCollection)
                        <div class="col-lg-3">
                            <div class="mb-3">
                                <select name="month" class="form-control">
                                    @foreach (range(1, 12) as $month)
                                        <option value="{{ $month }}"
                                            {{ (int) ($post_data['month'] ?? date('m')) === $month ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $month, 1)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="mb-3">
                                <select name="year" class="form-control">
                                    @foreach (range(2024, date('Y')) as $year)
                                        <option value="{{ $year }}"
                                            {{ (int) ($post_data['year'] ?? date('Y')) === $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @else
                        <div class="col-lg-3">
                            <div class="mb-3">
                                {{ Form::date('payment_at', old('payment_at', $post_data['payment_at'] ?? now()->format('Y-m-d')), ['class' => 'form-control']) }}
                            </div>
                        </div>
                    @endif

                    <div class="col-lg-3">
                        <div class="mb-3 text-end">
                            <div class="text-sm-end d-none d-sm-block">
                                <a href="{{ route($filterRoute) }}" class="btn btn-outline-danger">
                                    <i class="bx bx-reset"></i> Clear
                                </a>

                                <button type="submit" name="filter" class="btn btn-secondary">
                                    <i class="bx bx-search"></i> Filter Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3 mt-2">
                    <div class="row g-3">
                        <div class="col-md-2 col-xl-2">
                            <div class="detail-summary border-top-secondary shadow h-96 py-2">
                                <div class="detail-summary__label text-muted">{{ $collectionsLabel }}</div>
                                <div class="detail-summary__value text-secondary">
                                    {{ number_format($listStats['collections'] ?? 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-xl-2">
                            <div class="detail-summary border-top-success shadow h-96 py-2">
                                <div class="detail-summary__label text-muted">{{ $secondaryCountLabel }}</div>
                                <div class="detail-summary__value text-success">
                                    {{ number_format($secondaryCountValue) }}</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-xl-2">
                            <div class="detail-summary border-top-danger shadow h-96 py-2">
                                <div class="detail-summary__label text-muted">{{ $tertiaryCountLabel }}</div>
                                <div class="detail-summary__value text-danger">{{ number_format($tertiaryCountValue) }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2 col-xl-2">
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
                </div>
                {{ Form::close() }}
            </div>

            <div class="card sp-list-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                        <div></div>

                        <div class="text-sm-end">
                            <a href="{{ route($summaryExportRoute, array_merge($post_data, ['format' => 'xls'])) }}"
                                class="btn btn-success btn-sm">
                                <i class="bx bx-export"></i> Export XLS
                            </a>
                            <a href="{{ route($summaryExportRoute, array_merge($post_data, ['format' => 'pdf'])) }}"
                                class="btn btn-outline-danger btn-sm ms-sm-2 mt-2 mt-sm-0">
                                <i class="bx bxs-file-pdf"></i> Export PDF
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive sp-list-table collection-summary-table">
                        <table class="table table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th width="4%">#</th>
                                    <th>Service Provider</th>
                                    <th class="text-end">{{ $collectionsLabel }}</th>
                                    <th class="text-end">{{ $secondaryCountLabel }}</th>
                                    <th class="text-end">{{ $tertiaryCountLabel }}</th>
                                    <th class="text-end">Expected Amount</th>
                                    <th class="text-end">{{ $paidAmountLabel }}</th>
                                    <th class="text-end">Outstanding</th>
                                    <th class="text-end">Success Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($arr_data as $values)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <a href="{{ route($detailRoute, $values['uuid']) }}?{{ http_build_query($post_data) }}"
                                                class="collection-summary-table__link">
                                                {{ strtoupper($values['partner_name']) }}
                                            </a>
                                        </td>
                                        <td class="text-end">{{ number_format($values['number_of_collection']) }}</td>
                                        <td class="text-end text-success fw-semibold">
                                            {{ number_format($values[$secondaryRowKey] ?? 0) }}</td>
                                        <td class="text-end text-danger fw-semibold">
                                            {{ number_format($values[$tertiaryRowKey] ?? 0) }}</td>
                                        <td class="text-end">{{ number_format($values['total_amount'], 2) }}</td>
                                        <td class="text-end">{{ number_format($values['paid_amount'], 2) }}</td>
                                        <td class="text-end">{{ number_format($values['outstanding_amount'], 2) }}</td>
                                        <td class="text-end">{{ number_format($values['percent'], 2) }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No collection data found
                                            for the selected filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if (!empty($arr_data))
                                <tfoot>
                                    <tr>
                                        <th colspan="2">Total</th>
                                        <th class="text-end">{{ number_format($listStats['collections'] ?? 0) }}</th>
                                        <th class="text-end text-success">{{ number_format($secondaryCountValue) }}
                                        </th>
                                        <th class="text-end text-danger">{{ number_format($tertiaryCountValue) }}</th>
                                        <th class="text-end">{{ number_format($listStats['expected_amount'] ?? 0, 2) }}
                                        </th>
                                        <th class="text-end">{{ number_format($listStats['paid_amount'] ?? 0, 2) }}
                                        </th>
                                        <th class="text-end">
                                            {{ number_format($listStats['outstanding_amount'] ?? 0, 2) }}</th>
                                        <th class="text-end">{{ number_format($listStats['success_rate'] ?? 0, 2) }}%
                                        </th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
