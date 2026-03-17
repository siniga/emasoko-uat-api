@extends('layouts.master')

@section('title')
    Collection Batches
@endsection

<style>
    .cb-shell {
        --cb-primary: #153567;
        --cb-border: #dce6f5;
        --cb-muted: #6b7c93;
    }

    .cb-filter {
        border: 1px solid #e7edf6;
        border-radius: 16px;
        background: #fbfcfe;
        padding: 1.2rem;
        box-shadow: 0 16px 40px rgba(29, 53, 87, 0.08);
    }

    .cb-filter__title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--cb-primary);
        margin-bottom: 0.2rem;
    }

    .cb-filter__hint {
        color: var(--cb-muted);
        font-size: 0.88rem;
        margin-bottom: 1rem;
    }

    .cb-summary {
        border: 1px solid var(--cb-border);
        border-radius: 16px;
        background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        padding: 1rem;
        height: 100%;
    }

    .cb-summary__label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--cb-muted);
        margin-bottom: 0.35rem;
    }

    .cb-summary__value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--cb-primary);
    }

    .cb-table {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #dfe8f4;
    }

    .cb-table thead th {
        background: #edf4ff;
        color: var(--cb-primary);
        font-weight: 700;
        border-color: #dce7fa;
    }

    .cb-row-actions .dropdown-toggle {
        border: 0;
        background: transparent;
        color: var(--cb-primary);
        border-radius: 999px;
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .cb-row-actions .dropdown-toggle:hover,
    .cb-row-actions .dropdown-toggle:focus {
        background: #edf4ff;
        color: var(--cb-primary);
        box-shadow: none;
    }

    .cb-row-actions .dropdown-toggle::after {
        display: none;
    }

    .cb-row-actions .dropdown-menu {
        min-width: 10rem;
        border: 1px solid #dfe8f4;
        box-shadow: 0 16px 30px rgba(21, 53, 103, 0.12);
        border-radius: 12px;
        padding: 0.35rem 0;
    }

    .cb-row-actions .dropdown-item {
        font-size: 0.875rem;
        padding: 0.55rem 0.9rem;
    }
</style>

@section('content')
    <div class="cb-shell">
        @component('components.breadcrumb')
            @slot('li_1')
                Dashboard
            @endslot
            @slot('title')
                Collection Batches
            @endslot
        @endcomponent

        <div class="row">
            <div class="col-lg-12">
                <div class="cb-filter mb-4">
                    <div class="cb-filter__title">Filter Collection Batches</div>
                    <div class="cb-filter__hint">Narrow batch history by provider, date range, or mandate reference.</div>

                    {!! Form::open(['url' => route('collections.batches'), 'method' => 'POST', 'class' => 'form-horizontal']) !!}
                    {{ Form::token() }}

                    <div class="row">
                        <div class="col-lg-4">
                            <div class="mb-3">
                                @php $options = ['' => '-- Service Provider --']; @endphp
                                @foreach ($providers as $val)
                                    @php $options[$val->id] = $val->business_name; @endphp
                                @endforeach
                                {{ Form::select('partner_id', $options, old('partner_id', $post_data['partner_id']), ['class' => 'form-control select2', 'id' => 'partner_id']) }}
                            </div>
                        </div>

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

                        <div class="col-lg-4">
                            <div class="mb-3">
                                {{ Form::text('mandate_reference', old('mandate_reference'), ['class' => 'form-control', 'placeholder' => 'Search by mandate reference...']) }}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mb-0 text-sm-end d-none d-sm-block">
                                <button type="submit" name="filter" class="btn btn-secondary">
                                    <i class="bx bx-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-3">
                        <div class="cb-filter__title mb-1">Filtered Summary</div>
                        <div class="cb-filter__hint mb-3">These totals follow the filters currently applied above.</div>

                        <div class="row g-3">
                            <div class="col-md-2">
                                <div class="cb-summary">
                                    <div class="cb-summary__label">Total Batches</div>
                                    <div class="cb-summary__value">{{ number_format($listStats['total'] ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="cb-summary">
                                    <div class="cb-summary__label">Uploaded</div>
                                    <div class="cb-summary__value text-info">{{ number_format($listStats['uploaded'] ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="cb-summary">
                                    <div class="cb-summary__label">Processed</div>
                                    <div class="cb-summary__value text-success">{{ number_format($listStats['processed'] ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="cb-summary">
                                    <div class="cb-summary__label">Failed</div>
                                    <div class="cb-summary__value text-danger">{{ number_format($listStats['failed'] ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="cb-summary">
                                    <div class="cb-summary__label">Total Collections in Scope</div>
                                    <div class="cb-summary__value">{{ number_format($listStats['collections'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{ Form::close() }}
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                @if (session()->get('success'))
                                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                                @elseif(session()->get('warning'))
                                    <div class="alert alert-warning">{{ session()->get('warning') }}</div>
                                @elseif(session()->get('danger'))
                                    <div class="alert alert-danger">{{ session()->get('danger') }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <span class="font-weight-500">No. of collection batches:
                                    {{ number_format($number_of_batches) }}</span>
                            </div>

                            <div class="col-md-6">
                                <div class="text-sm-end d-none d-sm-block">
                                    <a class="btn btn-primary btn-sm" href="{{ route('collections.create') }}">
                                        <i class="fas fa-plus"></i> Create Batch
                                    </a>
                                </div>
                            </div>
                        </div>

                        @if (isset($batches) && $batches->isNotEmpty())
                            <div class="table-responsive cb-table">
                                <table class="table table-bordered dt-responsive nowrap w-100 mb-0" id="dynamicTable">
                                    <thead>
                                        <tr>
                                            <th width="3%">#</th>
                                            <th width="22%">Service Provider</th>
                                            <th>Reference</th>
                                            <th>Uploaded</th>
                                            <th>Processed</th>
                                            <th>Outcome</th>
                                            <th>Collections</th>
                                            <th>Collection Date</th>
                                            <th>Created Date</th>
                                            <th style="width: 40px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $serial = 1; @endphp
                                        @foreach ($batches as $values)
                                            <tr>
                                                <td>{{ $serial }}</td>
                                                <td>{{ $values->partner->business_name }}</td>
                                                <td>{{ $values->collectionReference ?? '' }}</td>
                                                <td>
                                                    @if ($values->pushed == 0)
                                                        <span class="badge badge-soft-danger">No</span>
                                                    @else
                                                        <span class="badge badge-soft-success">Yes</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($values->pushed == 0)
                                                        <span class="badge badge-soft-danger">No</span>
                                                    @elseif($values->processed == 0)
                                                        <span class="badge badge-soft-warning">In Progress</span>
                                                    @else
                                                        <span class="badge badge-soft-success">Yes</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($values->pushed == 0 && $values->failed == 0)
                                                        <span class="badge badge-soft-warning">In Progress</span>
                                                    @elseif($values->processed == 1 && $values->failed == 0)
                                                        <span class="badge badge-soft-success">Pass</span>
                                                    @elseif($values->processed == 1 && $values->failed == 1)
                                                        <span class="badge badge-soft-danger">Failed</span>
                                                    @else
                                                        <span class="badge badge-soft-secondary">Pending</span>
                                                    @endif
                                                </td>
                                                <td class="text-sm-end">{{ number_format($values->number_of_collections) }}</td>
                                                <td>{{ date('d-m-Y', strtotime($values->collectionDate)) }}</td>
                                                <td>{{ date('d-m-Y', strtotime($values->created_at)) }}</td>
                                                <td class="text-center">
                                                    <div class="dropdown cb-row-actions">
                                                        <button class="btn dropdown-toggle" type="button"
                                                            data-bs-toggle="dropdown" aria-expanded="false"
                                                            title="Batch Actions">
                                                            <i class="bx bx-dots-vertical-rounded fs-5"></i>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-end">
                                                            <a href="{{ route('collections.show', $values->id) }}"
                                                                class="dropdown-item">View Details</a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            @php $serial++; @endphp
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                @if (method_exists($batches, 'links'))
                                    {{ $batches->links() }}
                                @endif
                            </div>
                        @else
                            <div class="alert alert-danger mb-0">No collection batches at the moment.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
@endsection
