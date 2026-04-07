@extends('layouts.master')

@section('title')
    Create Collection Batch
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/collections-create.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="cc-shell">
        @component('components.breadcrumb')
            @slot('li_1')
                Collection Batches
            @endslot
            @slot('title')
                Create
            @endslot
        @endcomponent

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

                            <div class="table-responsive mb-3">
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
