@extends('layouts.master')

@section('title')
    Companies
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Companies
        @endslot
        @slot('title')
            List of Companies
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    

                    <div class="table-responsive">
                        @if (isset($companies) && $companies)
                            <table id="dt" class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="22%">Bussiness Name</th>
                                        <th width="16%">Category</th>
                                        <th width="12%">TIN Number</th>
                                        <th width="12%">Year of Incorporation</th>
                                        <th width="14%">Region</th>
                                        <th width="8%">Status</th>
                                        <th style="width: 60px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $serial = 1; @endphp
                                    @foreach ($companies as $company)
                                        <tr>
                                            <td>{{ $serial }}</td>
                                            <td>{{ $company->business_name }}</td>
                                            <td>{{ $company->partnerCategory->name ?? '' }}</td>
                                            <td>{{ $company->tin_number }}</td>
                                            <td>{{ $company->year_of_incorporation }}</td>
                                            <td>{{ $company->region->name ?? '' }}</td>
                                            <td>
                                                @if ($company->status == 0)
                                                    <span class="badge bg-warning">Registration Process</span>
                                                @elseif ($company->status == 1)
                                                    <span class="badge bg-info">Completed Registration</span>
                                                @elseif($company->status == 2)
                                                    <span class="badge bg-info">Assessment Process</span>
                                                @elseif($company->status == 3)
                                                    <span class="badge bg-success">Approved</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('companies.show', $company->id) }}"
                                                    title="View" class="btn btn-xss">
                                                    <i class="bx bx-folder-open"></i>
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
                            <div class="alert alert-danger"> No any user registered</div>
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
