@extends('layouts.master')

@section('title')
    Reports
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/reports-index.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="rp-shell" x-data="reportForm({
        reportType: '{{ $filters['report_type'] ?? '' }}',
        reportDefinitions: @js($reportDefinitions)
    })" x-init="init()">
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
                    <div class="rp-filter__head">
                        <div>
                            <div class="rp-filter__title">Reports</div>
                        </div>

                        <div class="rp-filter__actions">
                            <button type="button" class="btn btn-secondary btn-sm" x-show="reportType" x-cloak
                                @click="openReportModal(reportType)">
                                <i class="bx bx-slider-alt"></i> Edit Filters
                            </button>
                            <a href="{{ route('reports.lists') }}" class="btn btn-outline-danger btn-sm">
                                <i class="bx bx-reset"></i> Clear Filters
                            </a>
                        </div>
                    </div>

                    <div class="row g-3 mb-2">
                        @foreach ($reportDefinitions as $key => $definition)
                            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-0">
                                <label class="rp-type-card {{ ($filters['report_type'] ?? '') === $key ? 'rp-type-card--active' : '' }}"
                                    :class="reportType === '{{ $key }}' ? 'rp-type-card--active' : ''"
                                    @click="openReportModal('{{ $key }}')">
                                    <span class="rp-type-card__icon">
                                        <i class="bx bxs-report"></i>
                                    </span>
                                    <span class="rp-type-card__label">{{ $definition['label'] }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="reportFilterModal" tabindex="-1" aria-hidden="true" x-ref="reportFilterModal">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content rp-modal">
                    <div class="modal-header rp-modal__head">
                        <div>
                            <h5 class="modal-title rp-modal__title" x-text="selectedDefinition()?.label || 'Report Filters'"></h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        {!! Form::open(['url' => route('reports.lists'), 'method' => 'POST', 'class' => 'form-horizontal']) !!}
                        {{ Form::token() }}
                        <input type="hidden" name="report_type" :value="reportType">

                        <div class="row g-3">
                            <div class="col-lg-6" x-show="showField('mandate_status')" x-cloak>
                                <label class="rp-field__label">Mandate Status</label>
                                {{ Form::select('mandate_status', ['' => '-- Mandate status --'] + $mandateStatusOptions, old('mandate_status', $filters['mandate_status']), ['class' => 'form-control']) }}
                            </div>

                            <div class="col-lg-6" x-show="showField('collection_status')" x-cloak>
                                <label class="rp-field__label">Collection Status</label>
                                {{ Form::select('collection_status', ['' => '-- Collection status --'] + $collectionStatusOptions, old('collection_status', $filters['collection_status']), ['class' => 'form-control']) }}
                            </div>

                            <div class="col-lg-6" x-show="showField('start_at')" x-cloak>
                                <label class="rp-field__label" x-text="dateStartLabel()"></label>
                                {{ Form::date('start_at', old('start_at', $filters['start_at']), ['class' => 'form-control']) }}
                            </div>

                            <div class="col-lg-6" x-show="showField('end_at')" x-cloak>
                                <label class="rp-field__label" x-text="dateEndLabel()"></label>
                                {{ Form::date('end_at', old('end_at', $filters['end_at']), ['class' => 'form-control']) }}
                            </div>

                            <div class="col-lg-6" x-show="showField('account_type_id')" x-cloak>
                                @php
                                    $accountTypeOptions = ['' => '-- Payment Channel --'];
                                    foreach ($account_types as $val) {
                                        $accountTypeOptions[$val->id] = $val->name;
                                    }
                                @endphp
                                <label for="account_type_id" class="rp-field__label">Payment Channel</label>
                                {{ Form::select('account_type_id', $accountTypeOptions, old('account_type_id', $filters['account_type_id']), ['class' => 'form-control account_type_id', 'id' => 'account_type_id', '@change' => 'loadAccounts']) }}
                            </div>

                            <div class="col-lg-6" x-show="showField('account_id')" x-cloak>
                                @php
                                    $accountOptions = ['' => '-- Bank/MNO name --'];
                                    foreach ($accounts as $val) {
                                        $accountOptions[$val->id] = $val->name;
                                    }
                                @endphp
                                <label for="account_id" class="rp-field__label">Bank/MNO</label>
                                {{ Form::select('account_id', $accountOptions, old('account_id', $filters['account_id']), ['class' => 'form-control account_id', 'id' => 'account_id', 'x-ref' => 'accountId']) }}
                            </div>

                            <div class="col-lg-12" x-show="showField('partner_id')" x-cloak>
                                @php
                                    $partnerOptions = ['' => '-- Service Provider --'];
                                    foreach ($partners as $val) {
                                        $partnerOptions[$val->id] = $val->business_name;
                                    }
                                @endphp
                                <label for="partner_id" class="rp-field__label">Service Provider</label>
                                {{ Form::select('partner_id', $partnerOptions, old('partner_id', $filters['partner_id']), ['class' => 'form-control select2', 'id' => 'partner_id', 'x-ref' => 'partnerId']) }}
                            </div>
                        </div>

                        <div class="rp-modal__actions">
                            <button type="button" class="btn btn-outline-danger" @click="clearModalFields()">
                                <i class="bx bx-reset"></i> Reset Modal Filters
                            </button>
                            <button type="submit" name="preview" value="preview" class="btn btn-secondary">
                                <i class="bx bx-search-alt"></i> Preview Report
                            </button>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>

        @if ($reportPreview)
            @php
                $hiddenPreviewColumns = [];

                if (($reportPreview['type'] ?? null) === \App\Services\Reports\ReportQueryService::TYPE_MANDATE) {
                    $hiddenPreviewColumns = collect($reportPreview['headings'])
                        ->filter(fn ($heading) => strtolower((string) $heading) === 'remarks')
                        ->keys()
                        ->all();
                }
            @endphp
            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card rp-preview-card">
                        <div class="card-body">
                            <div class="rp-preview-head">
                                <div>
                                    <div class="rp-preview-head__title">{{ $reportPreview['title'] }}</div>
                                </div>

                                <div class="rp-preview-head__actions">
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

                            <div class="table-responsive">
                                <table class="table table-bordered dt-responsive nowrap w-100 mb-0">
                                    <thead>
                                        <tr>
                                            @foreach ($reportPreview['headings'] as $headingIndex => $heading)
                                                @continue(in_array($headingIndex, $hiddenPreviewColumns, true))
                                                <th scope="col">{{ $heading }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($reportPreview['rows'] as $row)
                                            <tr>
                                                @foreach ($row as $cellIndex => $cell)
                                                    @continue(in_array($cellIndex, $hiddenPreviewColumns, true))
                                                    <td>{{ $cell }}</td>
                                                @endforeach
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ count($reportPreview['headings']) - count($hiddenPreviewColumns) }}" class="text-center text-muted">
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
                reportDefinitions: config.reportDefinitions || {},
                modal: null,
                init() {
                    const modalElement = this.$refs.reportFilterModal;
                    if (modalElement) {
                        this.modal = new bootstrap.Modal(modalElement);
                    }

                    this.$nextTick(() => {
                        this.initModalSelects();
                    });
                },
                selectedDefinition() {
                    return this.reportDefinitions[this.reportType] || null;
                },
                openReportModal(type) {
                    this.reportType = type;
                    if (this.modal) {
                        this.modal.show();
                    }

                    this.$nextTick(() => {
                        this.initModalSelects();
                    });
                },
                initModalSelects() {
                    const modalElement = this.$refs.reportFilterModal;
                    const partnerElement = this.$refs.partnerId;

                    if (!modalElement || !partnerElement || typeof $ === 'undefined' || !$.fn.select2) {
                        return;
                    }

                    const $partner = $(partnerElement);

                    if ($partner.hasClass('select2-hidden-accessible')) {
                        $partner.select2('destroy');
                    }

                    $partner.select2({
                        dropdownParent: $(modalElement),
                        width: '100%',
                    });
                },
                showField(field) {
                    const fieldMap = {
                        MANDATE: ['mandate_status', 'start_at', 'end_at', 'account_type_id', 'account_id', 'partner_id'],
                        EXPECTED_COLLECTION: ['collection_status', 'start_at', 'end_at', 'account_type_id', 'account_id', 'partner_id'],
                        ACTUAL_COLLECTION: ['start_at', 'end_at', 'account_type_id', 'account_id', 'partner_id'],
                        OVERDUE_INSTALLMENTS: ['start_at', 'end_at', 'account_type_id', 'account_id', 'partner_id'],
                        PARTNER_PERFORMANCE: ['start_at', 'end_at', 'account_type_id', 'account_id', 'partner_id'],
                        MANDATE_LIFECYCLE: ['start_at', 'end_at', 'account_type_id', 'account_id', 'partner_id'],
                        CRB_REPORT: ['mandate_status', 'start_at', 'end_at', 'account_type_id', 'account_id', 'partner_id'],
                        ACCOUNTING_REPORT: ['collection_status', 'start_at', 'end_at', 'account_type_id', 'account_id', 'partner_id'],
                        DELINQUENT_AGE_ANALYSIS: ['start_at', 'end_at', 'account_type_id', 'account_id', 'partner_id'],
                    };

                    return (fieldMap[this.reportType] || []).includes(field);
                },
                modalHint() {
                    return this.selectedDefinition()?.description || 'Adjust the filters for the selected report before previewing or exporting.';
                },
                dateStartLabel() {
                    if (this.reportType === 'ACTUAL_COLLECTION' || this.reportType === 'ACCOUNTING_REPORT') {
                        return 'Collection Start Date';
                    }
                    if (this.reportType === 'DELINQUENT_AGE_ANALYSIS') {
                        return 'Due Date From';
                    }
                    return 'Start Date';
                },
                dateEndLabel() {
                    if (this.reportType === 'ACTUAL_COLLECTION' || this.reportType === 'ACCOUNTING_REPORT') {
                        return 'Collection End Date';
                    }
                    if (this.reportType === 'DELINQUENT_AGE_ANALYSIS') {
                        return 'Due Date To';
                    }
                    return 'End Date';
                },
                clearModalFields() {
                    const modalElement = this.$refs.reportFilterModal;
                    if (!modalElement) {
                        return;
                    }

                    modalElement.querySelectorAll('select, input[type="date"], input[type="text"]').forEach((element) => {
                        if (element.name === 'report_type') {
                            return;
                        }

                        element.value = '';
                    });

                    if (this.$refs.partnerId && typeof $ !== 'undefined' && $.fn.select2) {
                        $(this.$refs.partnerId).val('').trigger('change');
                    }

                    if (this.$refs.accountId) {
                        this.$refs.accountId.innerHTML = '<option value="">-- Bank/MNO name --</option>';
                    }
                },
                async loadAccounts(event) {
                    const accountTypeId = event.target.value;
                    if (!this.$refs.accountId) {
                        return;
                    }

                    if (!accountTypeId) {
                        this.$refs.accountId.innerHTML = '<option value="">-- Bank/MNO name --</option>';
                        return;
                    }

                    const response = await fetch('/account-types/' + accountTypeId);
                    this.$refs.accountId.innerHTML = await response.text();
                }
            };
        }
    </script>
@endsection
