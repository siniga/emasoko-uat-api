@extends('layouts.master')

@section('title')
    Collection Batches
@endsection

@section('content')
    <div class="sp-list-shell">
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
                <div class="sp-list-filter mb-3">
                    {!! Form::open(['url' => route('collections.batches'), 'method' => 'POST', 'class' => 'form-horizontal']) !!}
                    {{ Form::token() }}

                    <div class="row">
                        <div class="col-lg-3">
                            <div class="mb-3">
                                @php $options = ['' => '-- Service Provider --']; @endphp
                                @foreach ($providers as $val)
                                    @php $options[$val->id] = $val->business_name; @endphp
                                @endforeach
                                {{ Form::select('partner_id', $options, old('partner_id', $post_data['partner_id']), ['class' => 'form-control select2', 'id' => 'partner_id']) }}
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="mb-3">
                                {{ Form::date('start_at', old('start_at', $post_data['start_at']), ['class' => 'form-control']) }}
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="mb-3">
                                {{ Form::date('end_at', old('end_at', $post_data['end_at']), ['class' => 'form-control']) }}
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="mb-3">
                                {{ Form::select(
                                    'cl_status',
                                    [
                                        '' => '-- Status --',
                                        'uploaded' => 'Uploaded',
                                        'processed' => 'Processed',
                                        'failed' => 'Failed',
                                    ],
                                    old('cl_status'),
                                    ['class' => 'form-control select2', 'id' => 'cl_status'],
                                ) }}
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                {{ Form::text('mandate_reference', old('mandate_reference', $post_data['mandate_reference']), ['class' => 'form-control', 'placeholder' => 'Search by mandate reference...']) }}
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-0 text-sm-end d-none d-sm-block">
                                <button type="reset" name="reset" class="btn btn-outline-danger">
                                    <i class='bx bx-reset'></i> Clear
                                </button>

                                <button type="submit" name="filter" class="btn btn-secondary">
                                    <i class="bx bx-search"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                    {{ Form::close() }}
                </div>

                <div class="row mb-3">
                    <div class="col-md-2">
                        <div class="detail-summary shadow h-96 py-2">
                            <div class="detail-summary__label text-muted">Total Batches</div>
                            <div class="detail-summary__value text-primary">
                                {{ number_format($listStats['total'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="detail-summary shadow h-96 py-2">
                            <div class="detail-summary__label text-muted">Uploaded</div>
                            <div class="detail-summary__value text-secondary">
                                {{ number_format($listStats['uploaded'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="detail-summary shadow h-96 py-2">
                            <div class="detail-summary__label text-muted">Processed</div>
                            <div class="detail-summary__value text-success">
                                {{ number_format($listStats['processed'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="detail-summary shadow h-96 py-2">
                            <div class="detail-summary__label text-muted">Failed</div>
                            <div class="detail-summary__value text-danger">
                                {{ number_format($listStats['failed'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-summary shadow h-96 py-2">
                            <div class="detail-summary__label text-muted">Total No. of Collections</div>
                            <div class="detail-summary__value text-draft">
                                {{ number_format($listStats['collections'] ?? 0) }}</div>
                        </div>
                    </div>
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
                            <div class="col-md-4">
                                <span class="font-weight-500">No. of collection batches:
                                    {{ number_format($number_of_batches) }}</span>
                            </div>

                            <div class="col-md-8 text-sm-end">
                                <div class="text-sm-end d-none d-sm-block">
                                    @if (($monthlyFailedEligibility['allowed'] ?? false) === true)
                                        <a class="btn btn-outline-primary btn-sm"
                                            href="{{ route('collections.create-monthly-failed') }}">
                                            <i class='bx bxs-bookmark-plus'></i>
                                            Create Failed {{ $monthlyFailedEligibility['month_label'] ?? '' }}
                                            ({{ number_format($monthlyFailedEligibility['eligible_count'] ?? 0) }})
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-outline-secondary btn-sm" disabled
                                            title="{{ $monthlyFailedEligibility['reason'] ?? 'Not available' }}">
                                            <i class='bx bxs-bookmark-plus'></i>
                                            Create Failed {{ $monthlyFailedEligibility['month_label'] ?? '' }}
                                        </button>
                                    @endif
                                    <a class="btn btn-primary btn-sm" href="{{ route('collections.create') }}">
                                        <i class="bx bx-list-plus"></i> Create Collection Batch
                                    </a>

                                    <a class="btn btn-secondary btn-sm" href="{{ route('collections.create-failed') }}">
                                        <i class="bx bx-list-check"></i> Create Failed Batches
                                    </a>
                                </div>
                            </div>
                        </div>

                        @if (!empty($monthlyFailedEligibility['reason']))
                            <div class="alert alert-info py-2 px-3 mb-3">
                                {{ $monthlyFailedEligibility['reason'] }}
                            </div>
                        @endif

                        @if (isset($batches) && $batches->isNotEmpty())
                            <div class="table-responsive sp-list-table">
                                <table class="table table-bordered dt-responsive nowrap w-100 mb-0" id="dynamicTable">
                                    <thead>
                                        <tr>
                                            <th width="3%">#</th>
                                            <th width="22%">Service Provider</th>
                                            <th>Reference</th>
                                            <th>Upload State</th>
                                            <th>Processing State</th>
                                            <th>Workflow</th>
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
                                                    <span
                                                        class="badge {{ $values->pushed ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                                        {{ $values->upload_state_label }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge {{ $values->processed ? 'badge-soft-success' : 'badge-soft-warning' }}">
                                                        {{ $values->processing_state_label }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $values->lifecycle_badge_class }}">
                                                        {{ $values->lifecycle_label }}
                                                    </span>
                                                </td>
                                                <td class="text-sm-end">{{ number_format($values->number_of_collections) }}
                                                </td>
                                                <td>{{ date('d-m-Y', strtotime($values->collectionDate)) }}</td>
                                                <td>{{ date('d-m-Y', strtotime($values->created_at)) }}</td>
                                                <td class="text-center">
                                                    <div class="dropdown sp-row-actions">
                                                        <button class="btn dropdown-toggle" type="button"
                                                            data-bs-toggle="dropdown" aria-expanded="false"
                                                            title="Batch Actions">
                                                            <i class="bx bx-dots-vertical-rounded fs-5"></i>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-end">
                                                            <a href="{{ route('collections.show', $values->id) }}"
                                                                class="dropdown-item">View</a>
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
