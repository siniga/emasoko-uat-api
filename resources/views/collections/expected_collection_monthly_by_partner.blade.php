@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">{{ $title }}</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Expected Monthly Collection</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <!-- -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <div></div>
                        <div class="text-sm-end d-none d-sm-block">
                            <a title="Export" class="btn btn-outline-success btn-sm"
                                href="{{ route('collections.export-expected-monthly', $partner->uuid) }}?month={{ $post_data['month'] }}&year={{ $post_data['year'] }}">
                                <i class="bx bx-export"></i> Export XLS
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive  nowrap w-100" id="dynamicTable">
                            <thead>
                                <tr>
                                    <th width="3%">#</th>
                                    <th width="8%">Mandate Ref</th>
                                    <th width="20%">Account Name</th>
                                    <th width="10%">Account No.</th>
                                    <th width="10%">Expected Amt</th>
                                    <th width="10%">Paid Amt</th>
                                    <th width="10%">Outstanding</th>
                                    <th width="10%">Status</th>
                                </tr>
                            </thead>
                            @php
                                $sum_expected_amount = 0;
                                $sum_paid_amount = 0;
                                $sum_current_balance = 0;
                                $sum_outstanding_amount = 0;
                            @endphp

                            @foreach ($arr_installments as $val)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $val['reference'] }}</td>
                                    <td>{{ $val['account_name'] }}</td>
                                    <td>{{ $val['account_number'] }}</td>
                                    <td class="text-sm-end">{{ number_format($val['amount']) }}</td>

                                    <td class="text-sm-end">
                                        {{ number_format($val['last_paid_amount']) }}</td>
                                    <td class="text-sm-end">
                                        {{ number_format($val['outstanding_amount']) }}</td>
                                    <td>
                                        @if (strtoupper($val['status']) == 'OPEN')
                                            <span class="badge bg-primary">OPEN</span>
                                        @elseif (strtoupper($val['status']) == 'CLOSED')
                                            <span class="badge bg-success-1">PAID</span>
                                        @elseif(strtoupper($val['status']) == 'FAILED')
                                            <span class="badge bg-danger">FAILED</span>
                                        @elseif(strtoupper($val['status']) == 'FUNDSCHECK')
                                            <span class="badge bg-danger">FUNDSCHECK</span>
                                        @else
                                            <span class="badge bg-warning">{{ strtoupper($val['status']) }}</span>
                                        @endif
                                    </td>
                                </tr>

                                @php
                                    $sum_expected_amount += $val['amount'];
                                    $sum_paid_amount += $val['last_paid_amount'];
                                    $sum_current_balance += $val['current_balance'];
                                    $sum_outstanding_amount += $val['outstanding_amount'];
                                @endphp
                            @endforeach
                            <tr>
                                <td colspan="4" class="font-weight-600">Total</td>
                                <td class="text-sm-end">{{ number_format($sum_expected_amount) }}</td>
                                <td class="text-sm-end">{{ number_format($sum_paid_amount) }}</td>
                                <td class="text-sm-end">{{ number_format($sum_outstanding_amount) }}
                                </td>
                                <td colspan="2"></td>
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
    <script type="text/javascript"></script>
@endsection
