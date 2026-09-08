@extends('layouts.master')

@section('title')
    Quick Actions
@endsection

@section('content')
    <div class="sp-list-shell">
        @component('components.breadcrumb')
            @slot('li_1')
                Dashboard
            @endslot
            @slot('title')
                Quick Actions
            @endslot
        @endcomponent

        @if (session()->get('success'))
            <div class="alert alert-success">{{ session()->get('success') }}</div>
        @elseif(session()->get('warning'))
            <div class="alert alert-warning">{{ session()->get('warning') }}</div>
        @elseif(session()->get('danger'))
            <div class="alert alert-danger">{{ session()->get('danger') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="row g-4">
            <div class="col-xl-6 col-lg-12">
                <div class="card sp-list-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title mb-1">Mandate Action</h5>
                                <p class="text-muted mb-0">Validate mandate references and process cancellation, closing, or archiving immediately.</p>
                            </div>
                            <span class="badge badge-soft-primary">Admin / Manager</span>
                        </div>

                        {!! Form::open(['url' => route('quick-actions.mandate'), 'method' => 'POST']) !!}
                        {{ Form::token() }}

                        <div class="mb-3">
                            <label class="form-label">Action <span class="text-danger">*</span></label>
                            {{ Form::select('action', [
                                '' => '-- Select Action --',
                                'cancellation' => 'Cancel Mandate',
                                'closing' => 'Close Mandate',
                                'archive' => 'Archive Mandate',
                            ], old('action'), ['class' => 'form-control select2', 'required' => true]) }}
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mandate References <span class="text-danger">*</span></label>
                            {{ Form::textarea('mandate_references', old('mandate_references'), [
                                'class' => 'form-control',
                                'rows' => 4,
                                'placeholder' => 'Example: 120820261001,120820261002',
                                'required' => true,
                            ]) }}
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            {{ Form::textarea('remarks', old('remarks'), [
                                'class' => 'form-control',
                                'rows' => 2,
                                'placeholder' => 'Optional reason, mostly for cancellation.',
                            ]) }}
                        </div>

                        <div class="text-sm-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-check-circle"></i> Process Mandate
                            </button>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>

            <div class="col-xl-6 col-lg-12">
                <div class="card sp-list-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title mb-1">Create & Push Collection Batch</h5>
                                <p class="text-muted mb-0">Create NMB batches from mandate references using each mandate next debit date, then push them directly to the bank.</p>
                            </div>
                            <span class="badge badge-soft-success">Direct Push</span>
                        </div>

                        {!! Form::open(['url' => route('quick-actions.collection-batch'), 'method' => 'POST']) !!}
                        {{ Form::token() }}

                        <div class="mb-3">
                            <label class="form-label">Mandate References <span class="text-danger">*</span></label>
                            {{ Form::textarea('collection_mandate_references', old('collection_mandate_references'), [
                                'class' => 'form-control',
                                'rows' => 7,
                                'placeholder' => 'Example: 120820261001,120820261002',
                                'required' => true,
                            ]) }}
                        </div>

                        <div class="text-sm-end">
                            <button type="submit" class="btn btn-success">
                                <i class="bx bx-cloud-upload"></i> Create & Push Batch
                            </button>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>

        @if (session('quick_mandate_results'))
            <div class="card sp-list-card mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Mandate Action Results</h5>
                    <div class="table-responsive sp-list-table">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Action</th>
                                    <th>Service Provider</th>
                                    <th>Channel</th>
                                    <th>Status</th>
                                    <th>Lifecycle</th>
                                    <th>Response</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (session('quick_mandate_results') as $result)
                                    <tr>
                                        <td>{{ $result['reference'] }}</td>
                                        <td>{{ $result['action'] }}</td>
                                        <td>{{ $result['partner'] ?? 'NIL' }}</td>
                                        <td>{{ $result['channel'] ?? 'NIL' }}</td>
                                        <td>
                                            <span class="badge {{ $result['success'] ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                                {{ $result['success'] ? 'Success' : 'Failed' }}
                                            </span>
                                        </td>
                                        <td>{{ $result['lifecycle_status'] ?? 'NIL' }}</td>
                                        <td>{{ $result['response_code'] ?? 'NIL' }}</td>
                                        <td>{{ $result['message'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if (session('quick_collection_results') || session('quick_collection_messages') || session('quick_collection_skipped'))
            <div class="card sp-list-card mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Collection Batch Results</h5>

                    @if (session('quick_collection_messages'))
                        <div class="alert alert-warning">
                            @foreach (session('quick_collection_messages') as $message)
                                <div>{{ $message }}</div>
                            @endforeach
                        </div>
                    @endif

                    @if (session('quick_collection_results'))
                        <div class="table-responsive sp-list-table">
                            <table class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Batch Reference</th>
                                        <th>Service Provider</th>
                                        <th>Collections</th>
                                        <th>Collection Date</th>
                                        <th>Status</th>
                                        <th>Response</th>
                                        <th>Correlation ID</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (session('quick_collection_results') as $result)
                                        <tr>
                                            <td>{{ $result['batch_reference'] }}</td>
                                            <td>{{ $result['partner'] ?? 'NIL' }}</td>
                                            <td class="text-sm-end">{{ number_format($result['collections'] ?? 0) }}</td>
                                            <td>{{ $result['collection_date'] ? date('d-m-Y', strtotime($result['collection_date'])) : 'NIL' }}</td>
                                            <td>
                                                <span class="badge {{ $result['success'] ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                                    {{ $result['success'] ? 'Pushed' : 'Failed' }}
                                                </span>
                                            </td>
                                            <td>{{ $result['response_code'] ?? 'NIL' }}</td>
                                            <td>{{ $result['correlation_id'] ?? 'NIL' }}</td>
                                            <td>{{ $result['message'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if (session('quick_collection_skipped'))
                        <div class="mt-3">
                            <h6 class="mb-2">Skipped Candidate Reasons</h6>
                            <div class="table-responsive sp-list-table">
                                <table class="table table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Reason</th>
                                            <th>Mandate Reference</th>
                                            <th>Installment ID</th>
                                            <th>Context</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (session('quick_collection_skipped') as $skip)
                                            <tr>
                                                <td>{{ $skip['reason'] ?? 'NIL' }}</td>
                                                <td>{{ $skip['mandate_reference'] ?? 'NIL' }}</td>
                                                <td>{{ $skip['installment_id'] ?? 'NIL' }}</td>
                                                <td>{{ collect($skip)->except(['reason', 'mandate_reference', 'installment_id', 'debit_order_id'])->filter()->map(fn ($value, $key) => $key . ': ' . $value)->implode(' | ') ?: 'NIL' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
