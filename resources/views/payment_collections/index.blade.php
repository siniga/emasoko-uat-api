@extends('layouts.master')

@section('title')
    Payment Collections
@endsection

@section('content')
    <div class="sp-list-shell">
        @component('components.breadcrumb')
            @slot('li_1') Collections @endslot
            @slot('title') Payment Collections @endslot
        @endcomponent

        <div class="card sp-list-card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('payment-collections.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label">From</label>
                            <input type="date" name="start_at" class="form-control" value="{{ $filters['start_at'] }}">
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label">To</label>
                            <input type="date" name="end_at" class="form-control" value="{{ $filters['end_at'] }}">
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label">Service Provider</label>
                            <select name="partner_id" class="form-control select2" data-placeholder="All providers">
                                <option value="">All providers</option>
                                @foreach ($providers as $provider)
                                    <option value="{{ $provider->partner_id }}" @selected(($filters['partner_id'] ?? null) == $provider->partner_id)>
                                        {{ $provider->partner_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label">Channel</label>
                            <select name="channel" class="form-control select2" data-placeholder="All channels">
                                <option value="">All channels</option>
                                @foreach ($channels as $channel)
                                    <option value="{{ $channel->slug }}" @selected(($filters['channel'] ?? null) === $channel->slug)>{{ $channel->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label">Payment Status</label>
                            <select name="collection_status" class="form-control select2" data-placeholder="All statuses">
                                <option value="">All statuses</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected(($filters['collection_status'] ?? null) === $status)>{{ ucfirst(strtolower($status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label">Mandate Type</label>
                            <select name="mandate_type" class="form-control select2" data-placeholder="All types">
                                <option value="">All types</option>
                                @foreach (['NORMAL', 'GEPG', 'LUKU'] as $type)
                                    <option value="{{ $type }}" @selected(($filters['mandate_type'] ?? null) === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <label class="form-label">Search</label>
                            <input type="search" name="keyword" class="form-control" value="{{ $filters['keyword'] ?? '' }}" placeholder="Mandate, payment reference, provider or account">
                        </div>
                        <div class="col-xl-4 col-md-6 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bx bx-filter-alt"></i> Filter</button>
                            <a href="{{ route('payment-collections.index') }}" class="btn btn-light"><i class="bx bx-reset"></i> Reset</a>
                            <a href="{{ route('payment-collections.export', $filters) }}" class="btn btn-outline-success">
                                <i class="bx bx-export"></i> Export XLS
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card sp-list-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-nowrap align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Service Provider</th>
                                <th>Channel</th>
                                <th>Mandate Type</th>
                                <th>Mandate Reference</th>
                                <th>Payment Reference</th>
                                <th>Raw Status</th>
                                <th>Payment Status</th>
                                <th class="text-end">Expected Amount</th>
                                <th class="text-end">Paid Amount</th>
                                <th>Collection Date</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($collections as $collection)
                                <tr>
                                    <td>{{ ($collections->firstItem() ?? 1) + $loop->index }}</td>
                                    <td>{{ $collection->partner_name }}</td>
                                    <td>{{ $collection->channel }}</td>
                                    <td>{{ $collection->mandate_type }}</td>
                                    <td>{{ $collection->mandate_reference }}</td>
                                    <td>{{ $collection->collection_payment_reference ?: 'N/A' }}</td>
                                    <td>{{ $collection->raw_status ?: 'N/A' }}</td>
                                    <td>
                                        @php
                                            $statusClass = $collection->collection_status === 'SUCCESS'
                                                ? 'badge-soft-success'
                                                : ($collection->collection_status === 'PARTIAL' ? 'badge-soft-warning' : 'badge-soft-danger');
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ $collection->collection_status }}</span>
                                    </td>
                                    <td class="text-end">{{ number_format($collection->expected_amount, 2) }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($collection->paid_amount, 2) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($collection->effective_payment_date)->format('d M Y') }}</td>
                                    <td title="{{ $collection->collection_remarks }}">{{ \Illuminate\Support\Str::limit($collection->collection_remarks, 60) ?: 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center text-muted py-4">No payment collections found for the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-light fw-semibold">
                                <td colspan="8">Filtered Total ({{ number_format($summary->collection_count ?? 0) }} records)</td>
                                <td class="text-end">{{ number_format($summary->expected_amount ?? 0, 2) }}</td>
                                <td class="text-end text-success">{{ number_format($summary->paid_amount ?? 0, 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="p-3">{{ $collections->links() }}</div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(function () {
            $('.select2').select2({ width: '100%' });
        });
    </script>
@endsection
