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
            <!-- -->
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
                                        <td>
                                            <a href="{{ route('collections.actual-monthly-detail', $values['uuid']) }}" class="font-weight-600">
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
