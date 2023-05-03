@extends('layouts.master')

@section('title')
    Service Providers
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            Service Providers
        @endslot
    @endcomponent

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
                                            $options = ['' => 'Category'] + $options;
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
                                            $options = ['' => 'Region'] + $options;
                                        @endphp

                                        {{ Form::select('region_id', $options, old('region_id', $post_data['region_id']), ['class' => 'form-control select2', 'id' => 'region_id']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        @php
                                            $options = [
                                                0 => 'Registration Process',
                                                1 => 'Completed Registration',
                                                2 => 'Assessment Process',
                                                3 => 'Approved',
                                            ];
                                            $options = ['' => 'Status'] + $options;
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

                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <div class="text-sm-end d-none d-sm-block">
                                            <button type="submit" name="filter" class="btn btn-secondary">
                                                <i class="bx bx-search"></i> Filter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->
                            {{ Form::close() }}
                        </div>
                        <!--./col-lg-12 -->
                    </div>
                    <!--./row -->
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
                            <span class="font-weight-500">No. of companies: {{ number_format(count($companies)) }} </span>
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
                                        <th width="22%">Bussiness Name</th>
                                        <th width="16%">Category</th>
                                        <th width="12%">TIN Number</th>
                                        <th width="10%">Year of Incorporation</th>
                                        <th width="12%">Region</th>
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
                                                    <span class="badge bg-success">Assessment Approved</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('companies.show', $company->id) }}" title="View"
                                                    class="btn btn-xss">
                                                    <i class="bx bx-folder-open text-secondary font-size-14"></i>
                                                </a>

                                                <a href="{{ route('companies.edit', $company->id) }}" title="Edit"
                                                    class="btn btn-xss">
                                                    <i class="bx bx-edit  font-size-14"></i>
                                                </a>

                                                <a href="{{ route('companies.delete', $company->id) }}" title="Delete"
                                                    class="btn btn-xss delete">
                                                    <i class="bx bx-trash text-danger font-size-14"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @php $serial++; @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-danger"> No any company registered</div>
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
