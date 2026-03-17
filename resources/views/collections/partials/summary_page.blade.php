<style>
    .cc-shell {
        --cc-primary: #153567;
        --cc-border: #dce6f5;
        --cc-muted: #6b7c93;
    }

    .cc-filter {
        border: 1px solid #e7edf6;
        border-radius: 16px;
        background: #fbfcfe;
        padding: 1.2rem;
        box-shadow: 0 16px 40px rgba(29, 53, 87, 0.08);
    }

    .cc-filter__title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--cc-primary);
        margin-bottom: 0.2rem;
    }

    .cc-filter__hint {
        color: var(--cc-muted);
        font-size: 0.88rem;
        margin-bottom: 1rem;
    }

    .cc-summary {
        border: 1px solid var(--cc-border);
        border-radius: 16px;
        background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        padding: 1rem;
        height: 100%;
    }

    .cc-summary__label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--cc-muted);
        margin-bottom: 0.35rem;
    }

    .cc-summary__value {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--cc-primary);
    }

    .cc-table {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #dfe8f4;
    }

    .cc-table thead th {
        background: #edf4ff;
        color: var(--cc-primary);
        font-weight: 700;
        border-color: #dce7fa;
    }
</style>

<div class="cc-shell">
    <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ $title }}</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">{{ $title }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="cc-filter mb-4">
                    <div class="cc-filter__title">Filter Collection</div>
                    <div class="cc-filter__hint">
                        {{
                            $collectionType === \App\Services\CollectionAnalyticsService::TYPE_ACTUAL
                                ? 'Review settled collections using effective collection date and keep totals aligned with the collection reports.'
                                : 'Review scheduled collections using deduction date and compare expected, collected, and outstanding amounts from one shared dataset.'
                        }}
                    </div>

                    {!! Form::open(['url' => route($filterRoute), 'method' => 'POST', 'class' => 'form-horizontal']) !!}
                    {{ Form::token() }}
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <select name="channel" class="form-select">
                                <option value="">-- All Channels --</option>
                                @foreach ($payment_channels as $channel)
                                    <option value="{{ $channel->slug }}" {{ ($post_data['channel'] ?? null) === $channel->slug ? 'selected' : '' }}>
                                        {{ $channel->slug }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if ($collectionPeriod === \App\Services\CollectionAnalyticsService::PERIOD_MONTHLY)
                            <div class="col-lg-3">
                                <select name="month" class="form-select">
                                    @foreach (range(1, 12) as $month)
                                        <option value="{{ $month }}" {{ (int) ($post_data['month'] ?? date('m')) === $month ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $month, 1)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <select name="year" class="form-select">
                                    @foreach (range(2024, date('Y')) as $year)
                                        <option value="{{ $year }}" {{ (int) ($post_data['year'] ?? date('Y')) === $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="col-lg-6">
                                {{ Form::date('payment_at', old('payment_at', $post_data['payment_at'] ?? now()->format('Y-m-d')), ['class' => 'form-control']) }}
                            </div>
                        @endif

                        <div class="col-lg-2">
                            <button type="submit" name="filter" class="btn btn-secondary w-100">
                                <i class="bx bx-search"></i> Filter
                            </button>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                            <div>
                                <div class="cc-filter__title mb-1">Filtered Summary</div>
                                <div class="cc-filter__hint mb-0">These totals use the same query rules as the detail page and export.</div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-2">
                                <div class="cc-summary">
                                    <div class="cc-summary__label">Providers</div>
                                    <div class="cc-summary__value">{{ number_format($listStats['providers'] ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="cc-summary">
                                    <div class="cc-summary__label">Collections</div>
                                    <div class="cc-summary__value">{{ number_format($listStats['collections'] ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="cc-summary">
                                    <div class="cc-summary__label">Expected Amount</div>
                                    <div class="cc-summary__value">{{ number_format($listStats['expected_amount'] ?? 0, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="cc-summary">
                                    <div class="cc-summary__label">Collected Amount</div>
                                    <div class="cc-summary__value text-success">{{ number_format($listStats['paid_amount'] ?? 0, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="cc-summary">
                                    <div class="cc-summary__label">Outstanding</div>
                                    <div class="cc-summary__value text-warning">{{ number_format($listStats['outstanding_amount'] ?? 0, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="cc-summary">
                                    <div class="cc-summary__label">Success Rate</div>
                                    <div class="cc-summary__value">{{ number_format($listStats['success_rate'] ?? 0, 2) }}%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{ Form::close() }}
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive cc-table">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th width="4%">#</th>
                                        <th>Service Provider</th>
                                        <th class="text-end">Collections</th>
                                        <th class="text-end">Expected Amount</th>
                                        <th class="text-end">Collected Amount</th>
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
                                                    class="fw-semibold">
                                                    {{ strtoupper($values['partner_name']) }}
                                                </a>
                                            </td>
                                            <td class="text-end">{{ number_format($values['number_of_collection']) }}</td>
                                            <td class="text-end">{{ number_format($values['total_amount'], 2) }}</td>
                                            <td class="text-end">{{ number_format($values['paid_amount'], 2) }}</td>
                                            <td class="text-end">{{ number_format($values['outstanding_amount'], 2) }}</td>
                                            <td class="text-end">{{ number_format($values['percent'], 2) }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">No collection data found for the selected filters.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
