@extends('layouts.master')

@section('title')
    Service Providers
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Service Provider</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Service Providers</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            {!! Form::open(['url' => route('companies.lists'), 'method' => 'POST', 'class' => 'form-horizontal']) !!}
                            {{ Form::token() }}

                            <div class="row">
                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        {{ Form::date('start_at', old('start_at', $post_data['start_at']), ['placeholder="Start Date..."', 'class="form-control"']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        {{ Form::date('end_at', old('end_at', $post_data['end_at']), ['placeholder="End Date..."', 'class="form-control"']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        @php
                                            $options = [];
                                        @endphp
                                        @foreach ($categories as $val)
                                            @php
                                                $options[$val->id] = $val->name;
                                            @endphp
                                        @endforeach
                                        @php
                                            $options = ['' => '-- Select Category --'] + $options;
                                        @endphp

                                        {{ Form::select('category_id', $options, old('category_id', $post_data['category_id']), ['class' => 'form-control', 'id' => 'category_id']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        @php $options = []; @endphp
                                        @foreach ($regions as $val)
                                            @php
                                                $options[$val->id] = $val->name;
                                            @endphp
                                        @endforeach
                                        @php
                                            $options = ['' => '-- Select Region --'] + $options;
                                        @endphp

                                        {{ Form::select('region_id', $options, old('region_id', $post_data['region_id']), ['class' => 'form-control', 'id' => 'region_id']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        @php
                                            $options = [
                                                0 => 'Registration Process',
                                                1 => 'Completed Registration',
                                                100 => 'Approval Process',
                                                200 => 'Approved',
                                                403 => 'Rejected',
                                            ];
                                            $options = ['' => '-- Select Status --'] + $options;
                                        @endphp

                                        {{ Form::select('status', $options, old('status', $post_data['status']), ['class' => 'form-control']) }}
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        {{ Form::text('keyword', old('keyword', $post_data['keyword']), ['class="form-control"', 'placeholder="Search by business name  or TIN number..."']) }}
                                    </div>
                                </div>
                                <!--./col -->
                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        @php
                                            $options = [
                                                0 => 'All',
                                                // 1 => 'Approved',
                                                2 => 'With Rejected Accounts',
                                            ];
                                            $options = ['' => '-- Select Account Status --'] + $options;
                                        @endphp

                                        {{ Form::select('account_status', $options, old('account_status', request()->account_status), ['class' => 'form-control']) }}
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <div class="text-sm-end d-none d-sm-block">
                                            <button type="submit" name="filter" class="btn btn-secondary">
                                                <i class="bx bx-search"></i> Filter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{ Form::close() }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            @if (session()->get('success'))
                                <div class="alert alert-success">
                                    {{ session()->get('success') }}
                                </div>
                            @elseif(session()->get('danger'))
                                <div class="alert alert-danger">
                                    {{ session()->get('danger') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-6">
                            <span class="font-weight-500">No. of service providers:
                                {{ number_format($number_of_companies) }} </span>
                        </div>

                        <div class="col-md-6">
                            <div class="text-sm-end d-none d-sm-block">
                                <a class="btn btn-primary btn-sm" href="{{ route('companies.registration') }}">
                                    <i class="fas fa-plus"></i> Register
                                </a>

                                <a class="btn btn-outline-success btn-sm"
                                    href="{{ route('companies.export-xls', ['start_at' => $post_data['start_at'], 'end_at' => $post_data['end_at'], 'category_id' => $post_data['category_id'], 'region_id' => $post_data['region_id'], 'status' => $post_data['status'], 'keyword' => $post_data['keyword']]) }}">
                                    <i class="fas fa-file-excel"></i> Export XLS
                                </a>

                                <a class="btn btn-outline-danger btn-sm"
                                    href="{{ route('companies.export-pdf', ['start_at' => $post_data['start_at'], 'end_at' => $post_data['end_at'], 'category_id' => $post_data['category_id'], 'region_id' => $post_data['region_id'], 'status' => $post_data['status'], 'keyword' => $post_data['keyword']]) }}">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        @if (isset($companies) && $companies->isNotEmpty())
                            <table id="dt" class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="18%">Bussiness Name</th>
                                        <th width="16%">Category</th>
                                        <th width="10%">TIN Number</th>
                                        <th width="8%">Year of Incorporation</th>
                                        <th width="10%">Phone</th>
                                        <th width="10%">Region</th>
                                        <th width="10%">Status</th>
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
                                            <td>{{ $company->phone }}</td>
                                            <td>{{ $company->region ? ucfirst(strtolower($company->region->name)) : '' }}</td>
                                            <td>
                                                @if ($company->status == 0)
                                                    <span class="badge bg-warning">Registration Process</span>
                                                @elseif($company->status == 100)
                                                    <span class="badge bg-info">Pending Review</span>
                                                @elseif($company->status == 200)
                                                    <span class="badge bg-info">Pending Approval</span>
                                                @elseif($company->status == 201)
                                                    <span class="badge bg-info">Pending Verification</span>
                                                @elseif($company->status == 202)
                                                    <span class="badge bg-success-1">Verified</span>
                                                @elseif($company->status == 403) 
                                                    <span class="badge bg-danger">Failed</span>   
                                                @elseif($company->status == 500)
                                                    <span class="badge bg-danger">Rejected</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($company->complete == 1)
                                                    <a href="{{ route('companies.details', $company->uuid) }}"
                                                        title="View" class="btn btn-xss">
                                                        <i class="bx bxs-folder-open"></i>
                                                    </a>
                                                @elseif($company->complete == 0 || $company->status == 500)
                                                    <a href="{{ route('companies.registration', $company->uuid) }}"
                                                        title="View" class="btn btn-xss">
                                                        <i class='bx bxs-plus-square bx-xs'></i>
                                                    </a>
                                                @endif

                                                @if ($company->status == 100 || $company->status ==200 || $company->status == 201)
                                                    <a href="{{ route('companies.edit', $company->uuid) }}" title="Edit"
                                                        class="btn btn-xss">
                                                        <i class="bx bx-edit text-secondary"></i>
                                                    </a>

                                                    <a href="{{ route('companies.delete', $company->uuid) }}" title="Delete"
                                                        class="btn btn-xss delete">
                                                        <i class="bx bx-trash text-danger"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                        @php $serial++; @endphp
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="d-flex justify-content-end">
                                @if (method_exists($companies, 'links'))
                                    {{ $companies->links() }}
                                @endif
                            </div>
                            <!--./d-flex -->
                        @else
                            <div class="alert alert-danger"> No any service provider registered</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->
@endsection

@section('script')
@endsection
