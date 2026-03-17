@extends('layouts.master')

@section('title')
    Summary of Collection Batch
@endsection

<style>
    .cs-shell {
        --cs-primary: #153567;
        --cs-muted: #6b7c93;
        --cs-border: #dce6f5;
    }

    .cs-hero {
        border: 1px solid rgba(21, 53, 103, 0.14);
        border-radius: 20px;
        padding: 1.35rem;
        background:
            radial-gradient(circle at top left, rgba(255, 255, 255, 0.28), transparent 45%),
            linear-gradient(135deg, #153567 0%, #1f4a88 100%);
        color: #fff;
        box-shadow: 0 22px 44px rgba(15, 40, 79, 0.18);
        overflow: visible;
    }

    .cs-hero__eyebrow {
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        opacity: 0.8;
        margin-bottom: 0.45rem;
    }

    .cs-hero__title {
        font-size: 1.55rem;
        font-weight: 700;
        margin-bottom: 0.35rem;
    }

    .cs-hero__text {
        margin-bottom: 0;
        color: rgba(255, 255, 255, 0.84);
        max-width: 820px;
    }

    .cs-actions {
        position: relative;
        z-index: 5;
    }

    .cs-card {
        border: 1px solid #e3ebf6;
        border-radius: 18px;
        box-shadow: 0 18px 42px rgba(29, 53, 87, 0.08);
    }

    .cs-card__title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--cs-primary);
        margin-bottom: 0.2rem;
    }

    .cs-card__hint {
        color: var(--cs-muted);
        font-size: 0.86rem;
        margin-bottom: 1rem;
    }

    .cs-stat {
        border: 1px solid var(--cs-border);
        border-radius: 14px;
        background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        padding: 1rem;
        height: 100%;
    }

    .cs-stat__label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--cs-muted);
        margin-bottom: 0.35rem;
    }

    .cs-stat__value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--cs-primary);
    }

    .cs-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.9rem 1rem;
    }

    .cs-kv {
        border-bottom: 1px dashed #e5ebf5;
        padding-bottom: 0.65rem;
    }

    .cs-kv__label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--cs-muted);
        margin-bottom: 0.25rem;
    }

    .cs-kv__value {
        color: #243b5a;
        font-weight: 600;
        word-break: break-word;
    }

    .cs-section__title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--cs-primary);
        margin-bottom: 0.2rem;
    }

    .cs-section__hint {
        color: var(--cs-muted);
        font-size: 0.88rem;
        margin-bottom: 1rem;
    }

    .cs-table {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #dfe8f4;
    }

    .cs-table thead th {
        background: #edf4ff;
        color: var(--cs-primary);
        font-weight: 700;
        border-color: #dce7fa;
    }

    @media (max-width: 991px) {
        .cs-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575px) {
        .cs-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@section('content')
    <div class="cs-shell" x-data="initData()">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Summary of Collection Batch</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('collections.batches') }}">Collection Batches</a></li>
                            <li class="breadcrumb-item active">Summary</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="cs-hero mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <div class="cs-hero__eyebrow">Collection Batch</div>
                    <div class="cs-hero__title">{{ $batch->collectionReference }}</div>
                    <p class="cs-hero__text">Review batch status, upload and processing actions, and collection line performance from one summary workspace.</p>
                </div>

                <div class="text-sm-end d-none d-sm-block cs-actions">
                    <template x-if="deleted==0">
                        <template x-if="status==100 || status ==200 || status ==230 || status ==235 || status ==239 || status==249 || status==403 || status==405 || status==500 || status==501">
                            <a href="{{ route('collections.archive', $batch->id) }}" class="btn btn-warning btn-sm archive">
                                <i class="bx bx-archive-in font-size-12"></i> Archive Batch
                            </a>
                        </template>
                    </template>

                    @if (Auth::user()->hasRole(['admin']))
                        <template x-if="pushed==0">
                            <button x-html="btnPush" class="btn btn-outline-light btn-sm" x-on:click="pushCollectionBatch();"></button>
                        </template>
                    @endif

                    @if (Auth::user()->hasRole(['admin']))
                        <template x-if="pushed==1">
                            <button x-html="btnStatus" class="btn btn-light btn-sm" x-on:click="fetchCollectionStatus();"></button>
                        </template>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div id="message_wrp"></div>

                @if (session()->get('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @elseif(session()->get('warning'))
                    <div class="alert alert-warning">{{ session()->get('warning') }}</div>
                @elseif(session()->get('danger'))
                    <div class="alert alert-danger">{{ session()->get('danger') }}</div>
                @endif

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="cs-stat">
                            <div class="cs-stat__label">Collections</div>
                            <div class="cs-stat__value">{{ number_format($batch->number_of_collections) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="cs-stat">
                            <div class="cs-stat__label">Uploaded</div>
                            <div class="cs-stat__value {{ $batch->pushed ? 'text-success' : 'text-danger' }}">{{ $batch->pushed ? 'Yes' : 'No' }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="cs-stat">
                            <div class="cs-stat__label">Processed</div>
                            <div class="cs-stat__value {{ $batch->processed ? 'text-success' : 'text-warning' }}">{{ $batch->processed ? 'Yes' : 'Pending' }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="cs-stat">
                            <div class="cs-stat__label">Outcome</div>
                            <div class="cs-stat__value">
                                @if ($batch->pushed == 0 && $batch->failed == 0)
                                    <span class="badge badge-soft-warning">In Progress</span>
                                @elseif($batch->processed == 1 && $batch->failed == 0)
                                    <span class="badge badge-soft-success">Pass</span>
                                @elseif($batch->processed == 1 && $batch->failed == 1)
                                    <span class="badge badge-soft-danger">Failed</span>
                                @else
                                    <span class="badge badge-soft-secondary">Pending</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card cs-card mb-4">
                    <div class="card-body">
                        <div class="cs-card__title">Batch Details</div>
                        <div class="cs-card__hint">Batch identity, processing state, operational ownership, and timing metadata.</div>

                        <div class="cs-grid">
                            <div class="cs-kv">
                                <div class="cs-kv__label">Batch Reference</div>
                                <div class="cs-kv__value">{{ $batch->collectionReference }}</div>
                            </div>
                            <div class="cs-kv">
                                <div class="cs-kv__label">Service Provider</div>
                                <div class="cs-kv__value">{{ $batch->partner->business_name }}</div>
                            </div>
                            <div class="cs-kv">
                                <div class="cs-kv__label">Corporate Reference</div>
                                <div class="cs-kv__value">{{ $batch->corporateReference }}</div>
                            </div>
                            <div class="cs-kv">
                                <div class="cs-kv__label">Created Date</div>
                                <div class="cs-kv__value">{{ date('M d, Y H:i', strtotime($batch->created_at)) }}</div>
                            </div>
                            <div class="cs-kv">
                                <div class="cs-kv__label">Collection Date</div>
                                <div class="cs-kv__value">{{ date('d-m-Y', strtotime($batch->collectionDate)) }}</div>
                            </div>
                            <div class="cs-kv">
                                <div class="cs-kv__label">Created By</div>
                                <div class="cs-kv__value">{{ $batch->createdUser->name ?? 'NIL' }}</div>
                            </div>
                            <div class="cs-kv">
                                <div class="cs-kv__label">Verified By</div>
                                <div class="cs-kv__value">{{ $batch->verifiedUser->name ?? 'NIL' }}</div>
                            </div>
                            <div class="cs-kv">
                                <div class="cs-kv__label">Upload State</div>
                                <div class="cs-kv__value">{{ $batch->pushed ? 'Uploaded' : 'Pending Upload' }}</div>
                            </div>
                            <div class="cs-kv">
                                <div class="cs-kv__label">Processing State</div>
                                <div class="cs-kv__value">{{ $batch->processed ? 'Processed' : 'Pending Processing' }}</div>
                            </div>
                            <div class="cs-kv">
                                <div class="cs-kv__label">Failed Flag</div>
                                <div class="cs-kv__value">{{ $batch->failed ? 'Failed' : 'No Failure' }}</div>
                            </div>
                            <div class="cs-kv">
                                <div class="cs-kv__label">Recreated Batch</div>
                                <div class="cs-kv__value">{{ $batch->recreated ? 'Yes' : 'No' }}</div>
                            </div>
                            <div class="cs-kv">
                                <div class="cs-kv__label">Remarks</div>
                                <div class="cs-kv__value">{{ $batch->remarks ?: 'No remarks' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                @if (($batch->created == 1 && $batch->pushed == 0) || ($batch->pushed == 1 && $batch->processed == 0))
                    <div class="card cs-card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="cs-section__title">Created Collection Debit Mandates</div>
                                    <div class="cs-section__hint">Mandates prepared for this batch before upload or while processing is still pending.</div>
                                </div>
                                <a title="Export" class="btn btn-outline-success btn-sm" href="{{ route('collections.export-created', $batch->id) }}">
                                    <i class="bx bx-export"></i> Export XLS
                                </a>
                            </div>

                            <div class="table-responsive cs-table">
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

                @if ($batch->pushed == 1 && $batch->processed == 1)
                    <div class="card cs-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="cs-section__title">Collection Batch History</div>
                                    <div class="cs-section__hint">Processed collection performance for each mandate line in this batch.</div>
                                </div>
                                <a title="Export" class="btn btn-outline-success btn-sm" href="{{ route('collections.export-processed', $batch->id) }}">
                                    <i class="bx bx-export"></i> Export XLS
                                </a>
                            </div>

                            <div class="table-responsive cs-table">
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
                                                <td class="text-sm-end">{{ number_format($val['outstanding_amount']) }}</td>
                                                <td>
                                                    @if (strtoupper($val['status']) == 'CLOSED')
                                                        <span class="badge badge-soft-success">PAID</span>
                                                    @elseif(strtoupper($val['status']) == 'FAILED')
                                                        <span class="badge badge-soft-danger">FAILED</span>
                                                    @elseif(strtoupper($val['status']) == 'FUNDSCHECK')
                                                        <span class="badge badge-soft-danger">FUNDSCHECK</span>
                                                    @else
                                                        <span class="badge badge-soft-warning">{{ strtoupper($val['status']) }}</span>
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
        </div>
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
                btnStatus: '<i class="fa fa-check"></i> Query Status',

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
                            $('#message_wrp').html("<div class='alert alert-success rounded-sm'>Collection batch uploaded, wait for 2-5 minutes and refresh the page.</div>");
                            this.pushed = 1
                        }).catch(() => {
                            $('#message_wrp').html("<div class='alert alert-danger rounded-sm'>Something went wrong.</div>");
                        }).finally(() => {
                            this.formLoading = false;
                            this.btnPush = '<i class="fa fa-file-import"></i> Upload Collection';
                        });
                },

                fetchCollectionStatus() {
                    this.formLoading = true;
                    this.btnStatus = '<i class="fas fa-spinner fa-spin"></i> Fetch collection status....';

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
                            $('#message_wrp').html("<div class='alert alert-success rounded-sm'>Collection status processed, wait for 2-5 minutes and refresh the page.</div>");
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        }).catch(() => {
                            $('#message_wrp').html("<div class='alert alert-danger rounded-sm'>Something went wrong.</div>");
                        }).finally(() => {
                            this.formLoading = false;
                            this.btnStatus = '<i class="fa fa-check"></i> Query Status';
                        });
                },
            }
        }
    </script>
@endsection
