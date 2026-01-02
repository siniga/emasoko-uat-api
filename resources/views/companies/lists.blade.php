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
                                                0 => 'Pending Review',
                                                1 => 'Approved',
                                                99 => 'Failed',
                                            ];
                                            $options = ['' => '-- Select eMakato Status --'] + $options;
                                        @endphp

                                        {{ Form::select('approved', $options, old('approved', $post_data['approved']), ['class' => 'form-control']) }}
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
                                                1 => 'Verified',
                                                99 => 'With Rejected Accounts',
                                            ];
                                            $options = ['' => '-- Select Account Status --'] + $options;
                                        @endphp

                                        {{ Form::select('is_verified', $options, old('is_verified', $post_data['is_verified']), ['class' => 'form-control']) }}
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
                    <div class="table-responsive">
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
                                    @if (isset($companies) && $companies->isNotEmpty())
                                        <a class="btn btn-outline-success btn-sm"
                                            href="{{ route('companies.export-xls', ['start_at' => $post_data['start_at'], 'end_at' => $post_data['end_at'], 'category_id' => $post_data['category_id'], 'region_id' => $post_data['region_id'], 'approved' => $post_data['approved'], 'is_verified' => $post_data['is_verified'], 'keyword' => $post_data['keyword']]) }}">
                                            <i class="fas fa-file-excel"></i> Export XLS
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if (isset($companies) && $companies->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-bordered dt-responsive  nowrap w-100" id="dynamicTable">
                                    <thead>
                                        <tr>
                                            <th width="3%">#</th>
                                            <th width="24%">Bussiness Name</th>
                                            <th>Category</th>
                                            <th>TIN Number</th>
                                            <th>Year of Inc</th>
                                            <th>Phone</th>
                                            <th>Region</th>
                                            <th>Approved</th>
                                            <th>Actions</th>
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
                                                <td>{{ $company->region ? ucfirst(strtolower($company->region->name)) : '' }}
                                                </td>
                                                <td>
                                                    @if ($company->approved == 0)
                                                        <span class="badge bg-warning">Pending Review</span>
                                                    @elseif($company->approved == 1)
                                                        <span class="badge bg-success">Approved</span>
                                                    @elseif($company->approved == 99)
                                                        <span class="badge bg-danger">Failed</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($company->complete == 1)
                                                        <a href="{{ route('companies.details', $company->uuid) }}"
                                                            title="View" class="btn btn-xss">
                                                            <i class="bx bxs-folder-open"></i>
                                                        </a>
                                                    @elseif($company->complete == 0)
                                                        <a href="{{ route('companies.registration', $company->uuid) }}"
                                                            title="View" class="btn btn-xss">
                                                            <i class='bx bxs-plus-square bx-xs'></i>
                                                        </a>
                                                    @endif

                                                    <a href="{{ route('companies.edit', $company->uuid) }}" title="Edit"
                                                        class="btn btn-xss">
                                                        <i class="bx bx-edit text-secondary"></i>
                                                    </a>

                                                    @if ($company->approved == 0 || $company->approved == 99)
                                                        <a href="{{ route('companies.delete', $company->uuid) }}"
                                                            title="Delete" class="btn btn-xss delete">
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
                            </div>
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
