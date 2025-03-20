@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            {{ $title }}
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="row mb-2">
                <div class="col-lg-12 col-md-12 col-12">
                    {!! Form::open(['url' => route('collections.expected-daily'), 'method' => 'POST', 'class' => 'form-horizontal']) !!}
                    {{ Form::token() }}

                    <div class="row">
                        <div class="col-lg-3 col-md-3 col-12">
                            <div class="input-group input-group-md">
                                {{ Form::date('payment_at', old('payment_at'), ['placeholder="Payment Date..."', 'class="form-control"']) }}

                                <button type="submit" name="filter" class="btn btn-secondary">
                                    <i class="bx bx-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                    {{ Form::close() }}
                </div>
            </div>


            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive  nowrap w-100" id="dynamicTable">
                            <thead>
                                <tr>
                                    <th width="3%">#</th>
                                    <th width="30%">Service Provider</th>
                                    <th width="12%">No. of Collection</th>
                                    <th width="14%">Expected Amount</th>
                                    <th width="14%">Collected Amount</th>
                                    <th width="14%">Outstanding Amount</th>
                                    <th width="12%">Success Rate(%)</th>
                                </tr>
                            </thead>

                            <tbody>
                                @php $serial = 1; @endphp
                                @foreach ($arr_data as $values)
                                    <tr>
                                        <td>{{ $serial }}</td>
                                        <td>{{ strtoupper($values['partner_name']) }}</td>
                                        <td class="text-sm-end">{{ number_format($values['number_of_collection']) }}</td>
                                        <td class="text-sm-end">{{ number_format($values['total_amount']) }}</td>
                                        <td class="text-sm-end">{{ number_format($values['paid_amount']) }}</td>
                                        <td class="text-sm-end">{{ number_format($values['outstanding_amount']) }}</td>
                                        <td class="text-sm-end">{{ $values['percent'] }}</td>
                                    </tr>
                                    @php $serial++; @endphp
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->
@endsection
@section('script')
    <script type="text/javascript"></script>
@endsection
