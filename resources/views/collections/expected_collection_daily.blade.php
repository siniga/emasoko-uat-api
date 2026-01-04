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
                            {!! Form::open([
                                'url' => route('collections.expected-daily'),
                                'method' => 'POST',
                                'class' => 'd-flex align-items-center',
                            ]) !!}
                            {{ Form::token() }}

                            <select name="channel" id="channel" class="form-select form-select-md me-2">
                                <option value="">-- All Channels --</option>
                                @foreach ($payment_channels as $channel)
                                    <option value="{{ $channel->slug }}"
                                        {{ request('channel') === $channel->slug ? 'selected' : '' }}>{{ $channel->slug }}
                                    </option>
                                @endforeach
                            </select>

                            {{ Form::date('payment_at', old('payment_at', $post_data['payment_at']), ['placeholder="Payment Date..."', 'class="form-control me-2"']) }}

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

                            <tbody>
                                @if ($arr_data)
                                    @php $serial = 1; @endphp
                                    @foreach ($arr_data as $values)
                                        <tr>
                                            <td>{{ $serial }}</td>
                                            <td>
                                                <a href="{{ route('collections.expected-daily-detail', $values['uuid']) }}?payment_at={{ $post_data['payment_at'] }}&channel={{ $post_data['channel'] }}"
                                                    class="font-weight-600">
                                                    {{ strtoupper($values['partner_name']) }}
                                                </a>
                                            </td>
                                            <td class="text-sm-end">{{ number_format($values['number_of_collection']) }}
                                            </td>
                                            <td class="text-sm-end">{{ number_format($values['total_amount']) }}</td>
                                            <td class="text-sm-end">{{ number_format($values['paid_amount']) }}</td>
                                            <td class="text-sm-end">{{ number_format($values['outstanding_amount']) }}</td>
                                            <td class="text-sm-end">{{ $values['percent'] }}</td>
                                        </tr>
                                        @php $serial++; @endphp
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="7" class="text-center">No data found</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->
@endsection
