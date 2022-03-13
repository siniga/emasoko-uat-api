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
                            <a href="{{ route('customers.register') }}"
                                class="btn btn-secondary btn-sm waves-effect btn-label waves-light">
                                <i class="bx bx-plus label-icon"></i> Register
                            </a>&nbsp;&nbsp;&nbsp;

                            {{-- <a href="{{ route('media.billboards.import') }}" class="btn btn-outline-info"><i
                                    class="fa fa-upload"></i> Import
                            </a>&nbsp;&nbsp;&nbsp; --}}
                        </div>
                        <!--./col-md-9 -->

                        <div class="col-md-3 pull-right">
                            <input type="text" id="myCustomSearchBox" class="form-control" placeholder="Search details...">
                        </div>
                        <!--./col-md-4 -->
                    </div>
                    <!--./row -->

                    <div class="table-responsive">
                        @if (isset($customers) && $customers)
                            <table id="dt" class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="18%">Full Name</th>
                                        <th width="16%">NIDA Number</th>
                                        <th width="8%">Payment Channel</th>
                                        <th width="10%">Bank/Institution</th>
                                        <th width="16%">Account</th>
                                        <th width="10%">Phone</th>
                                        <th width="8%">Status</th>
                                        <th style="width: 80px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $serial = 1; @endphp
                                    @foreach ($customers as $customer)
                                        <tr>
                                            <td>{{ $serial }}</td>
                                            <td>{{ $customer->full_name }}</td>
                                            <td>{{ $customer->id_number ?? 'NIL' }}</td>
                                            <td>{{ $customer->customerAccount->accountType->name ?? '' }}</td>
                                            <td>{{ $customer->customerAccount->account->name ?? '' }}</td>
                                            <td>
                                                {{ $customer->customerAccount->account_name ?? '' }}<br />
                                                {{ $customer->customerAccount->account_number ?? '' }}
                                            </td>
                                            <td>{{ $customer->customerAccount->phone ?? '' }}</td>
                                            <td>
                                                @if ($customer->status == 'active')
                                                    <span class="badge bg-success">Active</span>
                                                @elseif($customer->status == 'inactive')
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                
                                                <a href="{{ route('customers.details', $customer->uuid) }}" title="View"
                                                    class="btn btn-outline-primary btn-xss">
                                                    <i class="bx bx-folder-open text-center"></i>
                                                </a>

                                                <a href="{{ route('customers.edit', $customer->uuid) }}" title="Edit" class="btn btn-outline-secondary btn-xss">
                                                    <i class="bx bx-pencil text-center"></i>
                                                </a>

                                                <a href="#" title="Delete" class="btn btn-outline-danger btn-xss delete">
                                                    <i class="bx bx-trash"></i>
                                                </a>
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
