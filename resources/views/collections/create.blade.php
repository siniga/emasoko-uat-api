@extends('layouts.master')

@section('title')
    Create Collection Batch
@endsection

<style>
    .cc-shell {
        --cc-primary: #153567;
        --cc-muted: #6b7c93;
        --cc-border: #dce6f5;
    }

    .cc-hero {
        border: 1px solid rgba(21, 53, 103, 0.14);
        border-radius: 20px;
        padding: 1.35rem;
        background:
            radial-gradient(circle at top left, rgba(255, 255, 255, 0.28), transparent 45%),
            linear-gradient(135deg, #153567 0%, #1f4a88 100%);
        color: #fff;
        box-shadow: 0 22px 44px rgba(15, 40, 79, 0.18);
    }

    .cc-hero__eyebrow {
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        opacity: 0.8;
        margin-bottom: 0.45rem;
    }

    .cc-hero__title {
        font-size: 1.55rem;
        font-weight: 700;
        margin-bottom: 0.45rem;
    }

    .cc-hero__text {
        margin-bottom: 0;
        color: rgba(255, 255, 255, 0.84);
        max-width: 820px;
    }

    .cc-card {
        border: 1px solid #e3ebf6;
        border-radius: 18px;
        box-shadow: 0 18px 42px rgba(29, 53, 87, 0.08);
    }

    .cc-section__title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--cc-primary);
        margin-bottom: 0.25rem;
    }

    .cc-section__hint {
        color: var(--cc-muted);
        font-size: 0.88rem;
        margin-bottom: 1rem;
    }

    .cc-summary {
        border: 1px solid var(--cc-border);
        border-radius: 14px;
        background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        padding: 1rem;
        height: 100%;
    }

    .cc-summary__label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--cc-muted);
        margin-bottom: 0.35rem;
    }

    .cc-summary__value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--cc-primary);
    }

    .cc-table {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #dfe8f4;
    }

    .cc-table thead th {
        background: #edf4ff;
        color: var(--cc-primary);
        font-weight: 700;
        border-color: #dce7fa;
    }
</style>

@section('content')
    <div class="cc-shell">
        @component('components.breadcrumb')
            @slot('li_1')
                Collection Batches
            @endslot
            @slot('title')
                Create Collection Batch
            @endslot
        @endcomponent

        <div class="cc-hero mb-4">
            <div class="cc-hero__eyebrow">Collection Operations</div>
            <div class="cc-hero__title">Create Collection Batch</div>
            <p class="cc-hero__text">Select the provider and debit date, review all eligible installment collections, then create a batch for upload and downstream bank processing.</p>
        </div>

        <div class="row">
            <div class="col-lg-10">
                <div class="card cc-card mb-4">
                    <div class="card-body">
                        <div class="cc-section__title">Batch Filters</div>
                        <div class="cc-section__hint">Choose the provider and debit date you want to prepare for batching.</div>

                        {{ Form::open(['url' => route('collections.create'), 'method' => 'POST', 'class' => 'form-horizontal needs-validation', 'novalidate']) }}
                        {{ Form::token() }}

                        <div class="row align-items-end">
                            <div class="col-lg-5">
                                <div class="mb-3">
                                    @php $options = ['' => '-- Service Provider --']; @endphp
                                    @foreach ($providers as $val)
                                        @php $options[$val->id] = $val->business_name; @endphp
                                    @endforeach
                                    {{ Form::select('partner_id', $options, old('partner_id', $provider->partner->id ?? ''), ['class' => 'form-control select2', 'id' => 'partner_id', 'required' => '']) }}
                                    <span class="text-danger">{{ $errors->first('partner_id') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="mb-3">
                                    {{ Form::date('debit_date', old('debit_date', date('Y-m-d')), ['class' => 'form-control', 'required' => '']) }}
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="mb-3 text-sm-end d-none d-sm-block">
                                    <button type="submit" name="filter" class="btn btn-secondary px-4">
                                        <i class="bx bx-search"></i> Load Eligible Items
                                    </button>
                                </div>
                            </div>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card cc-card">
                    <div class="card-body">
                        @if (session()->get('success'))
                            <div class="alert alert-success mb-3">{{ session()->get('success') }}</div>
                        @elseif(session()->get('danger'))
                            <div class="alert alert-danger mb-3">{{ session()->get('danger') }}</div>
                        @endif

                        @if (isset($arr_installments) && $arr_installments)
                            @php
                                $totalExpected = collect($arr_installments)->sum('amount');
                                $totalOutstanding = collect($arr_installments)->sum('outstanding_amount');
                            @endphp

                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="cc-summary">
                                        <div class="cc-summary__label">Eligible Accounts</div>
                                        <div class="cc-summary__value">{{ number_format(sizeof($arr_installments)) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="cc-summary">
                                        <div class="cc-summary__label">Expected Amount</div>
                                        <div class="cc-summary__value text-primary">{{ number_format($totalExpected) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="cc-summary">
                                        <div class="cc-summary__label">Outstanding Amount</div>
                                        <div class="cc-summary__value text-warning">{{ number_format($totalOutstanding) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="cc-summary">
                                        <div class="cc-summary__label">Batch Scope</div>
                                        <div class="cc-summary__value text-info">{{ $partner_id ? 'Provider Selected' : 'Review Needed' }}</div>
                                    </div>
                                </div>
                            </div>

                            {{ Form::open(['url' => route('collections.store'), 'class' => 'form-horizontal']) }}
                            @method('POST')
                            {{ Form::token() }}
                            <input type="hidden" name="selected_partner_id" value="{{ $partner_id }}">

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <div class="cc-section__title mb-1">Eligible Collection Items</div>
                                    <div class="cc-section__hint mb-0">Review the items below, select the accounts you want to include, and create the batch.</div>
                                </div>
                                <div style="width: 280px;">
                                    <input type="text" id="myCustomSearchBox" class="form-control" placeholder="Search here...">
                                </div>
                            </div>

                            <div class="table-responsive cc-table mb-3">
                                <table class="table table-bordered dt-responsive nowrap w-100 mb-0" id="my-table">
                                    <thead>
                                        <tr>
                                            <th width="3%">
                                                <input class="form-check-input" type="checkbox" name="select-all" id="select-all" />
                                            </th>
                                            <th>Mandate Reference</th>
                                            <th>Account Name</th>
                                            <th>Account Number</th>
                                            <th>Payment Amount</th>
                                            <th>Current Balance</th>
                                            <th>Outstanding Amount</th>
                                            <th>Payment Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($arr_installments as $values)
                                            <tr>
                                                <td>
                                                    <input name="installment_ids[]" class="form-check-input" type="checkbox"
                                                        value="{{ $values['id'] }}" id="{{ $values['id'] }}">
                                                </td>
                                                <td>{{ $values['reference'] }}</td>
                                                <td>{{ $values['account_name'] }}</td>
                                                <td>{{ $values['account_number'] }}</td>
                                                <td class="text-sm-end">{{ number_format($values['amount']) }}</td>
                                                <td class="text-sm-end">{{ number_format($values['current_balance']) }}</td>
                                                <td class="text-sm-end">{{ number_format($values['outstanding_amount']) }}</td>
                                                <td>{{ date('d-m-Y', strtotime($values['payment_date'])) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('collections.batches') }}" class="btn btn-outline-danger text-medium">Cancel</a>
                                <button type="submit" class="btn btn-primary px-3">Create Batch</button>
                            </div>
                            {{ Form::close() }}
                        @else
                            <div class="alert alert-danger mb-0">No pending collection debit order.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script language="javascript">
        $('#select-all').click(function() {
            $(':checkbox').each(function() {
                this.checked = $('#select-all').is(':checked');
            });
        });

        if ($('#my-table').length) {
            dTable = $('#my-table').DataTable({
                "paging": false,
                "ordering": false,
                "info": false,
                "bFilter": true,
                "pageLength": 100,
                "dom": "lrtip"
            });

            $('#myCustomSearchBox').keyup(function() {
                dTable.search($(this).val()).draw();
            });
        }
    </script>
@endsection
