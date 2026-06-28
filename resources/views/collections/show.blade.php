@extends('layouts.master')

@section('title')
    Summary of Collection Batch
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/payment-mandate-show.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="pm-show" x-data="initData()">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Summary of Collection Batch</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('collections.batches') }}">Collection Batches</a>
                            </li>
                            <li class="breadcrumb-item active">Summary</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div id="message_wrp" class="mb-3"></div>

        @if (session()->get('success'))
            <div class="alert alert-success">{{ session()->get('success') }}</div>
        @elseif(session()->get('warning'))
            <div class="alert alert-warning">{{ session()->get('warning') }}</div>
        @elseif(session()->get('danger'))
            <div class="alert alert-danger">{{ session()->get('danger') }}</div>
        @endif

        <div class="card pm-card mb-4">
            <div class="card-body">
                <div class="pm-card__head">
                    <div>
                        <div class="pm-card__title">Batch Details</div>
                    </div>

                    <div class="pm-actions">
                        @if ($can_repair_failed_batch)
                            <form id="repair-failed-batch-form" method="POST"
                                action="{{ route('collections.repair-failed', $batch->id) }}">
                                @csrf
                                <button type="button" class="btn btn-success btn-sm"
                                    x-on:click="confirmFailedBatchRepair();">
                                    <i class="bx bx-repeat"></i> Recreate Failed Batch
                                </button>
                            </form>
                        @else
                            <button type="button" class="btn btn-success btn-sm" disabled
                                title="{{ $repair_failed_batch_eligibility['reason'] }}">
                                <i class="bx bx-repeat"></i> Recreate Failed Batch
                            </button>
                        @endif

                        <template x-if="deleted==0">
                            <template
                                x-if="status==100 || status ==200 || status ==230 || status ==235 || status ==239 || status==249 || status==403 || status==405 || status==500 || status==501">
                                <a href="{{ route('collections.archive', $batch->id) }}" class="btn btn-warning btn-sm archive">
                                    <i class="bx bx-archive-in font-size-12"></i> Archive Batch
                                </a>
                            </template>
                        </template>

                        @if (Auth::user()->hasRole(['admin']) && $batch->canBePushed())
                            <button x-html="btnPush" class="btn btn-primary btn-sm"
                                x-on:click="pushCollectionBatch();"></button>
                        @endif

                        @if (Auth::user()->hasRole(['admin']) && $batch->canFetchStatus())
                            <button x-html="btnStatus" class="btn btn-success btn-sm"
                                x-on:click="fetchCollectionStatus();"></button>
                        @endif
                    </div>
                </div>

                <div class="pm-grid">
                    <div class="pm-grid__item">
                        <div class="pm-grid__label">Batch Reference</div>
                        <div class="pm-grid__value">{{ $batch->collectionReference }}</div>
                    </div>
                    <div class="pm-grid__item">
                        <div class="pm-grid__label">Service Provider</div>
                        <div class="pm-grid__value">{{ $batch->partner->business_name }}</div>
                    </div>
                    <div class="pm-grid__item">
                        <div class="pm-grid__label">Corporate Reference</div>
                        <div class="pm-grid__value">{{ $batch->corporateReference }}</div>
                    </div>
                    <div class="pm-grid__item">
                        <div class="pm-grid__label">Created Date</div>
                        <div class="pm-grid__value">{{ date('M d, Y H:i', strtotime($batch->created_at)) }}</div>
                    </div>
                    <div class="pm-grid__item">
                        <div class="pm-grid__label">Collection Date</div>
                        <div class="pm-grid__value">{{ date('d-m-Y', strtotime($batch->collectionDate)) }}</div>
                    </div>
                    <div class="pm-grid__item">
                        <div class="pm-grid__label">Created By</div>
                        <div class="pm-grid__value">{{ $batch->createdUser->name ?? 'NIL' }}</div>
                    </div>
                    <div class="pm-grid__item">
                        <div class="pm-grid__label">Verified By</div>
                        <div class="pm-grid__value">{{ $batch->verifiedUser->name ?? 'NIL' }}</div>
                    </div>
                    <div class="pm-grid__item">
                        <div class="pm-grid__label">Upload State</div>
                        <div class="pm-grid__value">{{ $batch->upload_state_label }}</div>
                    </div>
                    <div class="pm-grid__item">
                        <div class="pm-grid__label">Processing State</div>
                        <div class="pm-grid__value">{{ $batch->processing_state_label }}</div>
                    </div>
                    <div class="pm-grid__item">
                        <div class="pm-grid__label">Workflow Lifecycle</div>
                        <div class="pm-grid__value">{{ $batch->lifecycle_label }}</div>
                    </div>
                    <div class="pm-grid__item">
                        <div class="pm-grid__label">Recreated Batch</div>
                        <div class="pm-grid__value">{{ $batch->recreated ? 'Yes' : 'No' }}</div>
                    </div>
                    <div class="pm-grid__item">
                        <div class="pm-grid__label">Remarks</div>
                        <div class="pm-grid__value">{{ $batch->remarks ?: 'No remarks' }}</div>
                    </div>
                </div>
            </div>
        </div>

            @if (!$batch->isWorkflowComplete())
                <div class="card pm-card mb-4">
                    <div class="card-body">
                        <div class="pm-card__head">
                            <div>
                                <div class="pm-card__title">Created Collection Debit Mandates</div>
                                <div class="pm-card__hint">Mandates prepared for this batch before upload or while processing is still pending.</div>
                            </div>
                            <a title="Export" class="btn btn-outline-success btn-sm"
                                href="{{ route('collections.export-created', $batch->id) }}">
                                <i class="bx bx-export"></i> Export XLS
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="10%">Mandate Ref</th>
                                        <th width="10%">Periodicity</th>
                                        <th width="16%">Account Name</th>
                                        <th width="12%">Account No.</th>
                                        <th width="10%">Collection Amt</th>
                                        <th width="10%">Payment Date</th>
                                        <th width="10%">Debit End Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($arr_installments as $val)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $val['reference'] }}</td>
                                            <td>{{ $val['periodicity'] }}</td>
                                            <td>{{ $val['account_name'] }}</td>
                                            <td>{{ $val['account_number'] }}</td>
                                            <td class="text-sm-end">{{ number_format($val['amount']) }}</td>
                                            <td>{{ $val['payment_date'] }}</td>
                                            <td>{{ $val['end_date'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if ($batch->isWorkflowComplete())
                <div class="card pm-card">
                    <div class="card-body">
                        <div class="pm-card__head">
                            <div>
                                <div class="pm-card__title">Collection Batch History</div>
                                <div class="pm-card__hint">Processed collection performance for each mandate line in this batch.</div>
                            </div>
                            <a title="Export" class="btn btn-outline-success btn-sm"
                                href="{{ route('collections.export-processed', $batch->id) }}">
                                <i class="bx bx-export"></i> Export XLS
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="8%">Mandate Ref</th>
                                        <th width="12%">Account Name</th>
                                        <th width="10%">Account No.</th>
                                        <th width="8%">Expected Amt</th>
                                        <th width="8%">Paid Amt</th>
                                        <th width="8%">Outstanding Amt</th>
                                        <th width="8%">Status</th>
                                        <th width="24%">Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $sum_expected_amount = 0;
                                        $sum_paid_amount = 0;
                                        $sum_outstanding_amount = 0;
                                    @endphp

                                    @foreach ($arr_installments as $val)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $val['reference'] }}</td>
                                            <td>{{ $val['account_name'] }}</td>
                                            <td>{{ $val['account_number'] }}</td>
                                            <td class="text-sm-end">{{ number_format($val['amount']) }}</td>
                                            <td class="text-sm-end">{{ number_format($val['last_paid_amount']) }}</td>
                                            <td class="text-sm-end">{{ number_format($val['outstanding_amount']) }}
                                            </td>
                                            <td>
                                                @if (strtoupper($val['status']) == 'CLOSED')
                                                    <span class="badge badge-soft-success">PAID</span>
                                                @elseif(strtoupper($val['status']) == 'FAILED')
                                                    <span class="badge badge-soft-danger">FAILED</span>
                                                @elseif(strtoupper($val['status']) == 'FUNDSCHECK')
                                                    <span class="badge badge-soft-danger">FUNDSCHECK</span>
                                                @else
                                                    <span
                                                        class="badge badge-soft-warning">{{ strtoupper($val['status']) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $val['remarks'] }}</td>
                                        </tr>

                                        @php
                                            $sum_expected_amount += $val['amount'];
                                            $sum_paid_amount += $val['last_paid_amount'];
                                            $sum_outstanding_amount += $val['outstanding_amount'];
                                        @endphp
                                    @endforeach
                                    <tr>
                                        <td colspan="4" class="font-weight-600">Total</td>
                                        <td class="text-sm-end">{{ number_format($sum_expected_amount) }}</td>
                                        <td class="text-sm-end">{{ number_format($sum_paid_amount) }}</td>
                                        <td class="text-sm-end">{{ number_format($sum_outstanding_amount) }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        
    </div>
@endsection

@section('script')
<script type="text/javascript">
    function initData() {
        return {
            sidebarOpen: false,
            eModal: "Collection_Information",
            status: "{{ $batch->status }}",
            pushed: "{{ $batch->pushed }}",
            processed: "{{ $batch->processed }}",
            deleted: "{{ $batch->deleted }}",
            failed: "{{ $batch->failed }}",
            recreated: "{{ $batch->recreated }}",
            show_create_button: "{{ $show_create_button }}",
            number_of_failed_installments: "{{ $number_of_failed_installments }}",

            formData: {
                batch_id: "{{ $batch->id }}",
            },

            formLoading: false,
            formMessage: "",
            btnPush: '<i class="fa fa-file-import"></i> Upload Collection',
            btnStatus: '<i class="fa fa-check"></i> Fetch Status',

            confirmFailedBatchRepair() {
                Swal.fire({
                    title: 'Recreate failed batch?',
                    text: 'A new batch will be created from the eligible failed collections in this batch.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, recreate',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('repair-failed-batch-form').submit();
                    }
                });
            },

            pushCollectionBatch() {
                this.formLoading = true;
                this.btnPush = '<i class="fas fa-spinner fa-spin"></i> Uploading collection batch....';

                fetch("/collections/upload", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Accept: "application/json",
                            'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify(this.formData),
                    }).then(response => response.json())
                    .then(() => {
                        $('#message_wrp').html(
                            "<div class='alert alert-success rounded-sm'>Collection batch uploaded, wait for 2-5 minutes and refresh the page.</div>"
                            );
                        this.pushed = 1
                    }).catch(() => {
                        $('#message_wrp').html(
                            "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>");
                    }).finally(() => {
                        this.formLoading = false;
                        this.btnPush = '<i class="fa fa-file-import"></i> Upload Collection';
                    });
            },

            fetchCollectionStatus() {
                this.formLoading = true;
                this.btnStatus = '<i class="fas fa-spinner fa-spin"></i> Fetching collection status....';

                fetch("/collections/fetch-status", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Accept: "application/json",
                            'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify(this.formData),
                    }).then(response => response.json())
                    .then(() => {
                        $('#message_wrp').html(
                            "<div class='alert alert-success rounded-sm'>Collection status processed, wait for 2-5 minutes and refresh the page.</div>"
                            );
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }).catch(() => {
                        $('#message_wrp').html(
                            "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>");
                    }).finally(() => {
                        this.formLoading = false;
                        this.btnStatus = '<i class="fa fa-check"></i> Query Status';
                    });
            },
        }
    }
</script>
@endsection
