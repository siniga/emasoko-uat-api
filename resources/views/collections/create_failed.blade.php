@extends('layouts.master')

@section('title')
    Create Failed Collection Batch
@endsection

@section('content')
    <div class="sp-list-shell">
        @component('components.breadcrumb')
            @slot('li_1')
                Dashboard
            @endslot
            @slot('title')
                Create Failed Collection Batch
            @endslot
        @endcomponent

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="text-muted mb-0">Choose one or more eligible failed batches to recreate for the next cycle.</p>
                    </div>
                </div>

                @if (session()->get('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @elseif(session()->get('warning'))
                    <div class="alert alert-warning">{{ session()->get('warning') }}</div>
                @elseif(session()->get('danger'))
                    <div class="alert alert-danger">{{ session()->get('danger') }}</div>
                @endif

                @if ($rows->isNotEmpty())
                    {!! Form::open(['url' => route('collections.store-failed'), 'method' => 'POST']) !!}
                    {{ Form::token() }}

                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive nowrap w-100 mb-0" id="dynamicTable">
                            <thead>
                                <tr>
                                    <th width="4%" class="text-center">
                                        <input type="checkbox" class="form-check-input" id="check-all">
                                    </th>
                                    <th width="22%">Reference</th>
                                    <th>Service Provider</th>
                                    <th>Collections</th>
                                    <th>Failed</th>
                                    <th>Collection Date</th>
                                    <th>Created Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        <td class="text-center">
                                            <input
                                                type="checkbox"
                                                name="batch_ids[]"
                                                value="{{ $row['id'] }}"
                                                class="form-check-input batch-checkbox"
                                                {{ (string) $selectedBatchId === (string) $row['id'] ? 'checked' : '' }}>
                                        </td>
                                        <td>
                                            <a href="{{ route('collections.show', $row['id']) }}">{{ $row['collectionReference'] }}</a>
                                        </td>
                                        <td>{{ $row['partner_name'] }}</td>
                                        <td class="text-sm-end">{{ number_format($row['number_of_collections']) }}</td>
                                        <td class="text-sm-end text-danger fw-semibold">{{ number_format($row['number_of_failed']) }}</td>
                                        <td>{{ date('d-m-Y', strtotime($row['collectionDate'])) }}</td>
                                        <td>{{ date('d-m-Y', strtotime($row['created_at'])) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('collections.batches') }}" class="btn btn-outline-danger">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Batches</button>
                    </div>
                    {!! Form::close() !!}
                @else
                    <div class="alert alert-warning mb-0">No eligible failed collection batches were found for recreation.</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            const checkAll = document.getElementById('check-all');
            const checkboxes = Array.from(document.querySelectorAll('.batch-checkbox'));

            if (!checkAll) {
                return;
            }

            checkAll.addEventListener('change', function() {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = checkAll.checked;
                });
            });
        });
    </script>
@endsection
