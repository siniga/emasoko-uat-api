@extends('layouts.master')

@section('title')
    Create Collection Batch
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Collection Batches
        @endslot
        @slot('title')
            Create Collection Batch
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8 col-md-8 col-12">
            <div class="card">
                <div class="card-body">
                    {{ Form::open(['url' => route('collections.create'), 'method' => 'POST', 'class' => 'form-horizontal needs-validation', 'novalidate']) }}
                    {{ Form::token() }}
                    <section>
                        <div class="row">
                            <div class="col-lg-5 col-md-5 col-12">
                                <div class="mb-0">
                                    @php
                                        $options = [];
                                    @endphp
                                    @foreach ($providers as $val)
                                        @php
                                            $options[$val->id] = $val->business_name;
                                        @endphp
                                    @endforeach
                                    @php
                                        $options = ['' => 'Service Provider'] + $options;
                                    @endphp

                                    {{ Form::select('partner_id', $options, old('partner_id', $provider->partner->id ?? ''), ['class' => 'form-control select2', 'id' => 'partner_id', 'required' => '']) }}
                                    <span class="text-danger">{{ $errors->first('partner_id') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-5 col-md-5 col-12">
                                <div class="mb-0">
                                    {{ Form::date('debit_date', old('debit_date', date('Y-m-d')), ['class="form-control"', 'placeholder="Enter collection date..."', 'max' => date('Y-m-d'), 'required' => '']) }}
                                </div>
                            </div>

                            <div class="col-lg-2 col-md-2 col-12">
                                <div class="mb-0 text-sm-end d-none d-sm-block">
                                    <button type="submit" name="filter" class="btn btn-secondary px-4">
                                        <i class="bx bx-search"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 col-md-12 col-12">
            <div class="card">
                <div class="card-body">

                    @if (session()->get('success'))
                        <div class="alert alert-success mb-2">
                            {{ session()->get('success') }}
                        </div>
                    @elseif(session()->get('danger'))
                        <div class="alert alert-danger mb-2">
                            {{ session()->get('danger') }}
                        </div>
                    @endif

                    @if (isset($arr_installments) && $arr_installments)
                        <div class="row mb-2">
                            <div class="col-lg-8 col-md-8 col-12">
                                <span class="font-weight-500">No. of collection accounts: {{ sizeof($arr_installments) }}</span>
                            </div>

                            <div class="col-lg-4 col-md-4 col-12">
                                <div class="text-sm-end d-none d-sm-block">
                                    <input type="text" id="myCustomSearchBox" class="form-control" placeholder="Search here...">
                                </div>
                            </div>
                        </div>

                        {{ Form::open(['url' => route('collections.store'), 'class' => 'form-horizontal']) }}
                        @method('POST')
                        {{ Form::token() }}
                        <input type="hidden" name="selected_partner_id" value="{{ $partner_id }}">

                        <div class="table-responsive table-container mb-3">
                            <table class="table table-bordered dt-responsive  nowrap w-100" id="my-table">
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
                                    @php $serial = 1; @endphp
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
                                        @php $serial++; @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-12">
                                <div class="mb-2 text-sm-end d-sm-block">
                                    <a href="{{ route('collections.batches') }}"
                                        class="btn btn-outline-danger text-medium">Cancel</a>

                                    <button type="submit" class="btn btn-primary px-3">Submit</button>
                                </div>
                            </div>
                        </div>
                        {{ Form::close() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript"></script>

    <script language="javascript">
        $('#select-all').click(function(event) {   
            if(this.checked) {
                // Iterate each checkbox
                $(':checkbox').each(function() {
                    this.checked = true;                        
                });
            } else {
                $(':checkbox').each(function() {
                    this.checked = false;                       
                });
            }
        });

        dTable = $('#my-table').DataTable({
            "paging": false,
            "ordering": false,
            "info": false,
            "bFilter": true,
            "pageLength": 100,
            "dom": "lrtip"
        });

        $('#myCustomSearchBox').keyup(function() {
            dTable.search($(this).val()).draw(); // this  is for customized searchbox with datatable search feature.
        });
    </script>
@endsection
