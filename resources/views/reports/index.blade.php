@extends('layouts.master')

@section('title')
    Reports
@endsection

<style>
    [x-cloak] {
        display: none !important;
    }

    .rp-shell {
        --rp-primary: #153567;
        --rp-muted: #6b7c93;
        --rp-border: #dce6f5;
    }

    .rp-filter {
        border: 1px solid #e7edf6;
        border-radius: 18px;
        background: #fbfcfe;
        padding: 1.25rem;
        box-shadow: 0 16px 40px rgba(29, 53, 87, 0.08);
    }

    .rp-filter__title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--rp-primary);
        margin-bottom: 0.25rem;
    }

    .rp-filter__hint {
        color: var(--rp-muted);
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .rp-type-card {
        border: 1px solid var(--rp-border);
        border-radius: 14px;
        background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        padding: 1rem;
        height: 100%;
    }

    .rp-type-card__label {
        color: var(--rp-primary);
        font-size: 0.95rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .rp-type-card__text {
        color: var(--rp-muted);
        font-size: 0.86rem;
        margin-bottom: 0;
    }

    .rp-summary {
        border: 1px solid var(--rp-border);
        border-radius: 14px;
        background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        padding: 1rem;
        height: 100%;
    }

    .rp-summary__label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--rp-muted);
        margin-bottom: 0.35rem;
    }

    .rp-summary__value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--rp-primary);
    }

    .rp-table {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #dfe8f4;
    }

    .rp-table thead th {
        background: #edf4ff;
        color: var(--rp-primary);
        font-weight: 700;
        border-color: #dce7fa;
        white-space: nowrap;
    }
</style>

@section('content')
    <div class="rp-shell" x-data="reportForm({ reportType: '{{ $filters['report_type'] ?? '' }}' })" x-init="init()">
        @component('components.breadcrumb')
            @slot('li_1')
                Dashboard
            @endslot
            @slot('title')
                Reports
            @endslot
        @endcomponent

        <div class="row">
            <div class="col-lg-12">
                <div class="rp-filter">
                    <div class="rp-filter__title">Reporting Workspace</div>
                    <div class="rp-filter__hint">Generate consistent exports from shared report queries for mandate portfolio, expected collection, and actual collection performance.</div>

                    <div class="row g-3 mb-4">
                        @foreach ($reportDefinitions as $definition)
                            <div class="col-lg-4">
                                <div class="rp-type-card">
                                    <div class="rp-type-card__label">{{ $definition['label'] }}</div>
                                    <p class="rp-type-card__text">{{ $definition['description'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {!! Form::open(['url' => route('reports.lists'), 'method' => 'POST', 'class' => 'form-horizontal']) !!}
                    {{ Form::token() }}

                    <div class="row">
                        <div class="col-lg-3">
                            <div class="mb-3">
                                @php
                                    $reportTypeOptions = ['' => '-- Report Type --'];
                                    foreach ($reportDefinitions as $key => $definition) {
                                        $reportTypeOptions[$key] = $definition['label'];
                                    }
                                @endphp
                                {{ Form::select('report_type', $reportTypeOptions, old('report_type', $filters['report_type']), ['id' => 'report_type', 'class' => 'form-control', 'x-model' => 'reportType', '@change' => 'syncStatusFilter()', 'required' => '']) }}
                            </div>
                        </div>

                        <div class="col-lg-3" x-show="statusFilter === 'mandate'" x-cloak>
                            <div class="mb-3">
                                {{ Form::select('mandate_status', ['' => '-- Mandate status --'] + $mandateStatusOptions, old('mandate_status', $filters['mandate_status']), ['class' => 'form-control']) }}
                            </div>
                        </div>

                        <div class="col-lg-3" x-show="statusFilter === 'collection'" x-cloak>
                            <div class="mb-3">
                                {{ Form::select('collection_status', ['' => '-- Collection status --'] + $collectionStatusOptions, old('collection_status', $filters['collection_status']), ['class' => 'form-control']) }}
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="mb-3">
                                {{ Form::date('start_at', old('start_at', $filters['start_at']), ['class' => 'form-control', 'required' => '']) }}
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="mb-3">
                                {{ Form::date('end_at', old('end_at', $filters['end_at']), ['class' => 'form-control', 'required' => '']) }}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-3">
                            <div class="mb-3">
                                @php
                                    $accountTypeOptions = ['' => '-- Payment Channel --'];
                                    foreach ($account_types as $val) {
                                        $accountTypeOptions[$val->id] = $val->name;
                                    }
                                @endphp
                                {{ Form::select('account_type_id', $accountTypeOptions, old('account_type_id', $filters['account_type_id']), ['class' => 'form-control account_type_id', 'id' => 'account_type_id', '@change' => 'loadAccounts']) }}
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="mb-3">
                                @php
                                    $accountOptions = ['' => '-- Bank/MNO name --'];
                                    foreach ($accounts as $val) {
                                        $accountOptions[$val->id] = $val->name;
                                    }
                                @endphp
                                {{ Form::select('account_id', $accountOptions, old('account_id', $filters['account_id']), ['class' => 'form-control account_id', 'id' => 'account_id', 'x-ref' => 'accountId']) }}
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                @php
                                    $partnerOptions = ['' => '-- Service Provider --'];
                                    foreach ($partners as $val) {
                                        $partnerOptions[$val->id] = $val->business_name;
                                    }
                                @endphp
                                {{ Form::select('partner_id', $partnerOptions, old('partner_id', $filters['partner_id']), ['class' => 'form-control select2', 'id' => 'partner_id']) }}
                            </div>
                        </div>
                    </div>

                    <div class="text-sm-end d-none d-sm-block">
                        <button type="submit" name="preview" value="preview" class="btn btn-secondary">
                            <i class="bx bx-search-alt"></i> Preview Report
                        </button>
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>

        @if ($reportPreview)
            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-start mb-4">
                                <div>
                                    <h5 class="mb-1" style="color:#153567;">{{ $reportPreview['title'] }}</h5>
                                    <p class="text-muted mb-0">
                                        {{ number_format($reportPreview['total_records']) }} record(s) matched the selected filters.
                                        @if ($reportPreview['preview_limited'])
                                            Showing the first 100 rows in preview.
                                        @endif
                                    </p>
                                </div>

                                <div>
                                    {!! Form::open(['url' => route('reports.lists'), 'method' => 'POST']) !!}
                                    {{ Form::token() }}
                                    @foreach ($filters as $key => $value)
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endforeach
                                    <button type="submit" name="export_xls" value="export_xls" class="btn btn-success btn-sm">
                                        <i class="bx bx-export"></i> Export XLS
                                    </button>
                                    <button type="submit" name="export_pdf" value="export_pdf" class="btn btn-outline-danger btn-sm ms-2">
                                        <i class="bx bxs-file-pdf"></i> Export PDF
                                    </button>
                                    {{ Form::close() }}
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                @foreach ($reportPreview['summary'] as $summary)
                                    <div class="col-md-3">
                                        <div class="rp-summary">
                                            <div class="rp-summary__label">{{ $summary['label'] }}</div>
                                            <div class="rp-summary__value text-{{ $summary['tone'] ?? 'primary' }}">{{ $summary['value'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="table-responsive rp-table">
                                <table class="table table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            @foreach ($reportPreview['headings'] as $heading)
                                                <th>{{ $heading }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($reportPreview['rows'] as $row)
                                            <tr>
                                                @foreach ($row as $cell)
                                                    <td>{{ $cell }}</td>
                                                @endforeach
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ count($reportPreview['headings']) }}" class="text-center text-muted">
                                                    No data matched the selected filters.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        function reportForm(config = {}) {
            return {
                reportType: config.reportType || '',
                statusFilter: '',
                init() {
                    this.syncStatusFilter();
                },
                syncStatusFilter() {
                    if (this.reportType === 'MANDATE') {
                        this.statusFilter = 'mandate';
                    } else if (this.reportType === 'EXPECTED_COLLECTION' || this.reportType === 'ACTUAL_COLLECTION') {
                        this.statusFilter = 'collection';
                    } else {
                        this.statusFilter = '';
                    }
                },
                async loadAccounts(event) {
                    const accountTypeId = event.target.value;
                    if (!accountTypeId || !this.$refs.accountId) {
                        return;
                    }

                    const response = await fetch('/account-types/' + accountTypeId);
                    this.$refs.accountId.innerHTML = await response.text();
                }
            }
        }
    </script>
@endsection
