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
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <div></div>
                        <div class="text-sm-end d-none d-sm-block">
                            {!! Form::open(['url' => route('collections.actual-monthly'), 'method' => 'POST', 'class' => 'd-flex align-items-center']) !!}
                                {{ Form::token() }}

                                <select name="month" id="month" class="form-select form-select-md me-2">
                                    <option value="{{ $post_data['month'] }}">{{ date('F', mktime(0, 0, 0, $post_data['month'], 1)) }}</option>
                                    <option value="">-- Month --</option>
                                    @foreach (range(1, 12) as $v)
                                        @php
                                            $month_name = date('F', mktime(0, 0, 0, $v, 10));
                                        @endphp
                                        <option value="{{ $v }}">{{ $month_name }}</option>
                                    @endforeach
                                </select>

                                <select name="year" id="year" class="form-select form-select-md me-2">
                                    <option value="{{ $post_data['year'] }}">{{ $post_data['year'] }}</option>
                                    <option value="">-- Year --</option>
                                    @foreach (range(2024, date('Y')) as $v)
                                        <option value="{{ $v }}">{{ $v }}</option>
                                    @endforeach
                                </select>

                                <button type="submit" name="filter" class="btn btn-secondary">
                                    Filter
                                </button>
                            {{ Form::close() }}
                        </div>
                    </div>

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

                            @php
                                $sum_expected_amount = 0;
                                $sum_paid_amount = 0;
                                $sum_current_balance = 0;
                                $sum_outstanding_amount = 0;
                                $success_rate = 0;
                            @endphp

                            <tbody>
                                @php $serial = 1; @endphp
                                @foreach ($arr_data as $values)
                                    <tr>
                                        <td>{{ $serial }}</td>
                                        <td>
                                            <a href="{{ route('collections.actual-monthly-detail', $values['uuid']) }}?month={{ $post_data['month'] }}&year={{ $post_data['year'] }}"
                                                class="font-weight-600">
                                                {{ strtoupper($values['partner_name']) }}
                                            </a>
                                        </td>
                                        <td class="text-sm-end">{{ number_format($values['number_of_collection']) }}</td>
                                        <td class="text-sm-end">{{ number_format($values['total_amount']) }}</td>
                                        <td class="text-sm-end">{{ number_format($values['paid_amount']) }}</td>
                                        <td class="text-sm-end">{{ number_format($values['outstanding_amount']) }}</td>
                                        <td class="text-sm-end">{{ $values['percent'] }}</td>
                                    </tr>
                                    @php $serial++; @endphp

                                    @php
                                        $sum_expected_amount += $values['total_amount'];
                                        $sum_paid_amount += $values['paid_amount'];
                                        $sum_outstanding_amount += $values['outstanding_amount'];
                                        $success_rate = ($sum_paid_amount / $sum_expected_amount) * 100;
                                    @endphp
                                @endforeach
                                <tr>
                                    <td colspan="3" class="font-weight-600">Total</td>
                                    <td class="text-sm-end font-weight-600">{{ number_format($sum_expected_amount) }}</td>
                                    <td class="text-sm-end font-weight-600">{{ number_format($sum_paid_amount) }}</td>
                                    <td class="text-sm-end font-weight-600">{{ number_format($sum_outstanding_amount) }}</td>
                                    <td class="text-sm-end font-weight-600">{{ round($success_rate, 2) }}</td> 
                                </tr>
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
    <script type="text/javascript">
        $(document).ready(function() {
            //on select account category
            $('.account_type_id').on('change', function(e) {
                e.preventDefault();
                accountTypeId = $(this).val();

                //query for data
                $.ajax({
                    url: '/account-types/' + accountTypeId,
                    type: "get",
                    success: function(data) {
                        $('.account_id').html(data);
                    }
                });
            });

        });
    </script>
@endsection
