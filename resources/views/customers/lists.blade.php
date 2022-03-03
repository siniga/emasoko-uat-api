@extends('layouts.master')

@section('title')
    Customers
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1')
            Lists
        @endslot
        @slot('title')
            Customers
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-9">
                            <a href="{{ route('customers.create') }}" class="btn btn-outline-primary btn-sm">
                                <i class="fa fa-plus"></i> Register New
                            </a>&nbsp;&nbsp;&nbsp;

                            {{-- <a href="{{ route('media.billboards.import') }}" class="btn btn-outline-info"><i
                                    class="fa fa-upload"></i> Import
                            </a>&nbsp;&nbsp;&nbsp; --}}
                        </div>
                        <!--./col-md-9 -->

                        <div class="col-md-3 pull-right">
                            <input type="text" id="myCustomSearchBox" class="form-control"
                                placeholder="Search details...">
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
                                        <th width="24%">Full Name</th>
                                        <th width="16%">NIDA Number</th>
                                        <th width="12%">Date of Birth</th>
                                        <th width="12%">Sector</th>
                                        <th width="14%">Occupation</th>
                                        <th width="8%">Status</th>
                                        <th style="width: 60px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $serial = 1; @endphp
                                    @foreach ($customers as $customer)
                                        <tr>
                                            <td>{{ $serial }}</td>
                                            <td>{{ $customer->first_name . ' ' . $customer->middle_name . ' ' . $customer->last_name }}</td>
                                            <td>{{ $customer->id_number }}</td>
                                            <td>{{ $customer->dob }}</td>
                                            <td>{{ $customer->sector->name }}</td>
                                            <td>{{ $customer->occupation->name ?? '' }}</td>
                                            <td>
                                                {{ $customer->status }}
                                            </td>
                                            <td>
                                                <a href="{{ route('customers.details', $customer->uuid) }}" title="View"
                                                    class="btn btn-outline-primary btn-xss">
                                                    <i class="fa fa-folder-open"></i>
                                                </a>

                                                {{-- <a href="{{ route('users.change-status', $values->id) }}"
                                                title="Change status" class="btn btn-outline-info btn-xs">
                                                <i class="fa fa-info-circle"></i>
                                            </a>

                                            <a href="{{ route('users.delete', $values->id) }}" title="Delete"
                                                class="btn btn-outline-danger btn-xs delete">
                                                <i class="fa fa-trash text-danger"></i>
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
