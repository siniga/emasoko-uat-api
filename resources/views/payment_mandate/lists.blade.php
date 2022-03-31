@extends('layouts.master')

@section('title')
    Payment Mandate
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            Payment Mandate
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-9">
                            {{-- <div class="row">
                    <div class="col-md-12">
                        <div class="float-right">
                            {!! Form::open(['url' => route('.index'), 'method' => 'POST', 'class' => 'form-inline']) !!}
                            {{ Form::token() }}

                            <div class="form-group">
                                {{ Form::date('start_at', old('start_at'), ['placeholder="Start Date..."', 'class="form-control"']) }}
                            </div>
                            <!--./form-group -->&nbsp;&nbsp;

                            <div class="form-group">
                                {{ Form::date('end_at', old('end_at'), ['placeholder="End Date..."', 'class="form-control"']) }}
                            </div>
                            <!--./form-group -->&nbsp;&nbsp;

                            <div class="form-group">
                                <button type="submit" name="filter" class="btn btn-secondary">Filter</button>
                            </div>
                            <!--./form-group -->
                            {{ Form::close() }}
                        </div>
                        <!--./float-right -->
                    </div>
                    <!--./col-md-12 -->
                </div>
                <!--./row --> --}}
                        </div>
                        <!--./col-md-9 -->
                    </div>
                    <!--./row -->

                    <div class="table-responsive">
                        @if (isset($debit_orders) && $debit_orders)
                            <table id="dt" class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="18%">Account Name</th>
                                        <th width="16%">NIDA Number</th>
                                        <th width="8%">Payment Channel</th>
                                        <th width="10%">Bank Name/MNO</th>
                                        <th width="12%">Account Number</th>
                                        <th width="12%">Phone<br /><small>(Connected to Bank)</small></th>
                                        <th width="8%">Consent Status</th>
                                        <th style="width: 80px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $serial = 1; @endphp
                                    @foreach ($debit_orders as $values)
                                        <tr>
                                            <td>{{ $serial }}</td>
                                            <td>{{ strtoupper($values->customerAccount->account_name ?? '') }}</td>
                                            <td><small>{{ $values->customerAccount->id_number ?? 'NIL' }}</small></td>
                                            <td>{{ $values->customerAccount->accountType->name ?? '' }}</td>
                                            <td>{{ $values->customerAccount->account->name ?? '' }}</td>
                                            <td>
                                                {{ $values->customerAccount->account_number ?? '' }}
                                            </td>
                                            <td>{{ $values->customerAccount->phone ?? '' }}</td>

                                            <td>
                                                @if ($values->approved == 0)
                                                    <span class="badge badge-pill badge-soft-danger">Pending</span>
                                                @elseif($values->approved == 1)
                                                    <span class="badge badge-pill badge-soft-success">Approved</span>
                                                @elseif($values->approved == 99)
                                                    <span class="badge badge-pill badge-soft-danger">Rejected</span>
                                                @endif
                                            </td>

                                            <td>
                                                <a href="{{ route('payment-mandate.details', $values->customer_account_uuid) }}" title="View"
                                                    class="btn btn-outline-primary btn-sm">
                                                    <i class="bx bx-folder-open"></i>
                                                </a>
                                                {{-- 

                                                <a href="{{ route('customers.edit', $values->uuid) }}" title="Edit"
                                                    class="btn btn-outline-secondary btn-xss">
                                                    <i class="bx bx-pencil text-center"></i>
                                                </a> --}}
                                                {{-- <a href="#" title="Delete" class="btn btn-outline-danger btn-xss delete">
                                                    <i class="bx bx-trash"></i>
                                                </a> --}}
                                            </td>
                                        </tr>
                                        @php $serial++; @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-danger"> No any customer registered</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->


@endsection
@section('script')
    <!-- apexcharts -->
    <script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <!-- dashboard init -->
    <script src="{{ URL::asset('/assets/js/pages/dashboard.init.js') }}"></script>
@endsection
