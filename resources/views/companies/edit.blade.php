@extends('layouts.master')

@section('title')
    Edit Service Provider
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">Edit Service Provider</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('companies.lists') }}">Service Providers</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>

        </div>
    </div>
</div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">

                    <!--./row -->
                    <div class="row">
                        <div class="col-lg-12">
                            @if (session()->get('success'))
                                <div class="alert alert-success">
                                    {{ session()->get('success') }}
                                </div><br />
                            @elseif(session()->get('danger'))
                                <div class="alert alert-danger">
                                    {{ session()->get('danger') }}
                                </div><br />
                            @endif

                            {{ Form::open(['url' => route('companies.update', $company->id), 'class' => 'form-horizontal needs-validation', 'enctype' => 'multipart/form-data', 'novalidate']) }}
                            @method('PUT')
                            {{ Form::token() }}

                            <div class="row">
                                <div class="col-lg-12">
                                    <h5 class="text-capitalize">Company Information</h5>
                                    <hr />
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label for="businessname">Business Name <span class="text-danger">*</span>
                                        </label>
                                        {{ Form::text('business_name', old('business_name', $company->business_name ?? ''), ['class="form-control"', 'placeholder="Write business name..."', 'required']) }}
                                        <div class="invalid-feedback">
                                            Business name required
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label for="email">Category <span class="text-danger">*</span></label>
                                        @php $options = []; @endphp
                                        @foreach ($categories as $category)
                                            @php
                                                $options[$category->id] = $category->name;
                                            @endphp
                                        @endforeach
                                        @php
                                            $options = ['' => 'Select'] + $options;
                                        @endphp

                                        {{ Form::select('category_id', $options, old('category_id', $company->partner_category_id ?? ''), ['class' => 'form-control select2', 'id' => 'category_id', 'required']) }}
                                        <div class="invalid-feedback">
                                            Business category required
                                        </div>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label for="tinno">TIN Number <span class="text-danger">*</span></label>
                                        {{ Form::text('tin_number', old('tin_number', $company->tin_number ?? ''), ['class="form-control input-mask"', 'data-inputmask' => "'mask': '999-999-999'", 'placeholder="Write tin number..."', 'required']) }}
                                        <div class="invalid-feedback">
                                            TIN number required
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label for="email">Year of Incorporation <span class="text-danger">*</span>
                                        </label>
                                        @php
                                            $year_options = [];
                                            $current_year = date('Y');
                                        @endphp

                                        @foreach (range($current_year, $current_year - 20) as $v)
                                            @php $year_options[$v] = $v; @endphp
                                        @endforeach

                                        @php $year_options = ['' => 'Select'] + $year_options; @endphp
                                        {{ Form::select('year_of_incorporation', $year_options, old('year_of_incorporation', $company->year_of_incorporation ?? ''), ['class' => 'form-control select2', 'required' => '']) }}
                                        <div class="invalid-feedback">
                                            Year of incorporation required
                                        </div>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label for="email">Number of Staff <span class="text-danger">*</span>
                                        </label>
                                        {{ Form::number('number_of_staff', old('number_of_staff', $company->number_of_staff ?? ''), ['class="form-control"', 'placeholder="Write number of staff..."', 'min' => 1, 'required']) }}
                                        <div class="invalid-feedback">
                                            Number of staff required
                                        </div>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label for="email">Company Email <span class="text-danger">*</span>
                                        </label>
                                        {{ Form::email('company_email', old('company_email', $company->email ?? ''), ['class="form-control"', 'placeholder="Write company email..."', 'required']) }}
                                        <div class="invalid-feedback">
                                            Company email required
                                        </div>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label for="address">Website</label>
                                        {{ Form::text('company_website', old('company_website', $company->website ?? ''), ['class="form-control"', 'placeholder="Write company website..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-6">
                                    <label for="email">Company Phone <span class="text-danger">*</span></label>
                                    <div class="row">
                                        <div class="col-md-4">
                                            @php $options = []; @endphp
                                            @foreach ($arr_countries as $key => $country)
                                                @php
                                                    $options[$key] = $country;
                                                @endphp
                                            @endforeach
                                            {{ Form::select('country_code', $options, old('country_code', substr($company->phone ?? '', 0, 3)), ['class' => 'form-control', 'id' => 'country_id']) }}
                                        </div>

                                        <div class="col-md-8">
                                            {{ Form::number('company_phone', old('company_phone', substr($company->phone ?? '', 4)), ['class="form-control"', 'placeholder="Write phone..."', 'required']) }}
                                            <div class="invalid-feedback">
                                                Company phone required
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row mt-4">
                                <div class="col-lg-12">
                                    <h5 class="text-capitalize">Company Location</h5>
                                    <hr />
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label for="email">Region <span class="text-danger">*</span></label>
                                        @php $options = []; @endphp
                                        @foreach ($regions as $region)
                                            @php
                                                $options[$region->id] = $region->name;
                                            @endphp
                                        @endforeach
                                        @php
                                            $options = ['' => 'Select'] + $options;
                                        @endphp

                                        {{ Form::select('region_id', $options, old('region_id', $company->region_id ?? ''), ['class' => 'form-control select2', 'id' => 'region_id', 'required']) }}
                                        <div class="invalid-feedback">
                                            Region required
                                        </div>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label for="location">Postal Address </label>
                                        {{ Form::text('postal_address', old('postal_address', $company->postal_address ?? ''), ['class="form-control"', 'placeholder="Company postal address..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label for="address">Physical Address <span class="text-danger">*</span></label>
                                        {{ Form::textarea('physical_address', old('company_address', $company->physical_address ?? ''), ['class="form-control"', 'placeholder="Write company physical address..."', 'rows' => 1, 'required']) }}
                                        <div class="invalid-feedback">
                                            Physical address required
                                        </div>
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="mb-3 text-sm-end d-none d-sm-block">
                                        <a href="{{ url('companies') }}"
                                            class="btn btn-danger btn-xs text-medium">Cancel</a>
                                        <button type="submit" class="btn btn-primary btn-xs text-medium">Update</button>
                                    </div>
                                </div>
                                <!--./col-lg-12 -->
                            </div>
                            <!--./row -->

                            {{ Form::close() }}
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- end row -->
@endsection
@section('script')
@endsection
