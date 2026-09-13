@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@section('content')
    <div class="sp-list-shell">
        @component('components.breadcrumb')
            @slot('li_1') Dashboard @endslot
            @slot('title') {{ $title }} @endslot
        @endcomponent

        <div class="card sp-list-card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('leaderboard.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label">From</label>
                            <input type="date" name="start_at" class="form-control" value="{{ $period['start_at'] }}">
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label">To</label>
                            <input type="date" name="end_at" class="form-control" value="{{ $period['end_at'] }}">
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label">Service Provider</label>
                            <select name="partner_id" class="form-control select2" data-placeholder="All providers">
                                <option value="">All providers</option>
                                @foreach ($providers as $provider)
                                    <option value="{{ $provider->id }}" @selected(($filters['partner_id'] ?? null) == $provider->id)>
                                        {{ $provider->business_name }}
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
                            <label class="form-label">Mandate Type</label>
                            <select name="mandate_type" class="form-control select2" data-placeholder="All types">
                                <option value="">All types</option>
                                @foreach ($mandateTypes as $mandateType)
                                    <option value="{{ $mandateType }}" @selected(($filters['mandate_type'] ?? null) === $mandateType)>{{ $mandateType }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label">Rank By</label>
                            <select name="sort_by" class="form-control select2">
                                <option value="actual_collection" @selected(($filters['sort_by'] ?? 'actual_collection') === 'actual_collection')>Actual Collection</option>
                                <option value="collection_success_rate" @selected(($filters['sort_by'] ?? null) === 'collection_success_rate')>Collection Success</option>
                                <option value="mandates_created" @selected(($filters['sort_by'] ?? null) === 'mandates_created')>Mandates Created</option>
                                <option value="mandates_approved" @selected(($filters['sort_by'] ?? null) === 'mandates_approved')>Mandates Approved</option>
                            </select>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <label class="form-label">Search</label>
                            <input type="search" name="keyword" class="form-control" value="{{ $filters['keyword'] ?? '' }}" placeholder="Name or reference">
                        </div>
                        <div class="col-xl-4 col-md-6 d-flex gap-2">
                            <button type="submit" class="btn btn-secondary"><i class="bx bx-filter-alt"></i> Apply Filter</button>
                            <a href="{{ route('leaderboard.index') }}" class="btn btn-outline-danger">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6">
                <div class="detail-summary shadow h-100 py-2">
                    <div class="detail-summary__label text-muted">Mandates Created</div>
                    <div class="detail-summary__value text-primary">{{ number_format($summary['mandates_created']) }}</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="detail-summary shadow h-100 py-2">
                    <div class="detail-summary__label text-muted">Mandates Approved</div>
                    <div class="detail-summary__value text-success">{{ number_format($summary['mandates_approved']) }}</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="detail-summary shadow h-100 py-2">
                    <div class="detail-summary__label text-muted">Actual Collection</div>
                    <div class="detail-summary__value text-success">TZS {{ number_format($summary['actual_collection'], 2) }}</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="detail-summary shadow h-100 py-2" title="Posted actual collection divided by scheduled expected collection for this period.">
                    <div class="detail-summary__label text-muted">Collection Success</div>
                    <div class="detail-summary__value text-primary">{{ number_format($summary['collection_success_rate'], 2) }}%</div>
                </div>
            </div>
        </div>

        <div class="card sp-list-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-nowrap align-middle">
                        <thead>
                            <tr>
                                <th class="text-center">Rank</th>
                                <th>{{ $mode === \App\Services\LeaderboardService::MODE_SERVICE_PROVIDERS ? 'Service Provider' : 'User' }}</th>
                                <th class="text-end">Created</th>
                                <th class="text-end">Approved</th>
                                <th class="text-end">Approval Rate</th>
                                <th class="text-end">Expected</th>
                                <th class="text-end">Actual</th>
                                <th class="text-end" title="Actual collection divided by expected collection.">Success</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="text-center">
                                        <span class="badge {{ $row['rank'] <= 3 ? 'badge-soft-primary' : 'badge-soft-secondary' }}">#{{ $row['rank'] }}</span>
                                    </td>
                                    <td class="fw-medium">{{ $row['name'] }}</td>
                                    <td class="text-end">{{ number_format($row['mandates_created']) }}</td>
                                    <td class="text-end">{{ number_format($row['mandates_approved']) }}</td>
                                    <td class="text-end">{{ number_format($row['approval_rate'], 2) }}%</td>
                                    <td class="text-end">{{ number_format($row['expected_collection'], 2) }}</td>
                                    <td class="text-end fw-medium">{{ number_format($row['actual_collection'], 2) }}</td>
                                    <td class="text-end"><span class="badge badge-soft-success">{{ number_format($row['collection_success_rate'], 2) }}%</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No leaderboard participants found for these filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $rows->links() }}</div>
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
