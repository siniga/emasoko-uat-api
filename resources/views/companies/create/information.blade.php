@extends('layouts.master')

@section('title')
   Register Service Provider
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Register Service Provider</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('companies.lists') }}">Service Providers</a></li>
                        <li class="breadcrumb-item active">Register New</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body" x-data="initData()">
                    <!--./form wizard -->
                    <div class="stepwizard mb-4">
                        <div class="stepwizard-row setup-panel">

                            <div class="stepwizard-step">
                                <a href="#step-1" type="button" class="btn btn-secondary btn-circle">1</a>
                                <span class="text-primary">Company Information</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-default btn-circle"
                                    disabled="disabled">2</a>
                                <span>Company Documents</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-default btn-circle"
                                    disabled="disabled">3</a>
                                <span>Collection Accounts</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-default btn-circle"
                                    disabled="disabled">4</a>
                                <span>Authorized Representative</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-5" type="button" class="btn btn-default btn-circle"
                                    disabled="disabled">5</a>
                                <span>Credentials</span>
                            </div>
                        </div>
                    </div>


                    @if (session()->get('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div><br />
                    @elseif(session()->get('danger'))
                        <div class="alert alert-danger">
                            {{ session()->get('danger') }}
                        </div><br />
                    @endif

                    {{ Form::open(['url' => route('companies.store-detail'), 'class' => 'form-horizontal', 'enctype' => 'multipart/form-data']) }}
                    @method('POST')
                    {{ Form::token() }}

                    <input type="hidden" name="partner_id" value="{{ $company->id ?? null }}">
                    <input type="hidden" name="partner_uuid" value="{{ $company->uuid ?? null }}">

                    <!-- Company Details -->
                    <section data-step="0">
                        <div class="row">
                            <div class="col-lg-12">
                                <h5 class="text-capitalize">Company Information</h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label>Business Name <span class="text-danger">*</span>
                                    </label>
                                    {{ Form::text('business_name', old('business_name', $company->business_name ?? ''), ['class="form-control"', 'placeholder="Write business name..."']) }}
                                    <div class="text-danger">{{ $errors->first('business_name') }}</div>
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>Category <span class="text-danger">*</span></label>
                                    @php $options = []; @endphp
                                    @foreach ($categories as $category)
                                        @php
                                            $options[$category->id] = $category->name;
                                        @endphp
                                    @endforeach
                                    @php
                                        $options = ['' => 'Select'] + $options;
                                    @endphp

                                    {{ Form::select('category_id', $options, old('category_id', $company->partner_category_id ?? ''), ['class' => 'form-control select2', 'id' => 'category_id']) }}
                                    <div class="text-danger">{{ $errors->first('category_id') }}</div>
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>TIN Number <span class="text-danger">*</span></label>
                                    {{ Form::text('tin_number', old('tin_number', $company->tin_number ?? ''), ['class="form-control input-mask"', 'data-inputmask' => "'mask': '999-999-999'", 'placeholder="Write tin number..."']) }}
                                    <div class="text-danger">{{ $errors->first('tin_number') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>Year of Incorporation <span class="text-danger">*</span>
                                    </label>
                                    @php
                                        $year_options = [];
                                        $current_year = date('Y');
                                    @endphp

                                    @foreach (range($current_year, $current_year - 20) as $v)
                                        @php $year_options[$v] = $v; @endphp
                                    @endforeach

                                    @php $year_options = ['' => 'Select'] + $year_options; @endphp
                                    {{ Form::select('year_of_incorporation', $year_options, old('year_of_incorporation', $company->year_of_incorporation ?? ''), ['class' => 'form-control select2']) }}
                                    <div class="text-danger">{{ $errors->first('year_of_incorporation') }}</div>
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>Number of Staff <span class="text-danger">*</span>
                                    </label>
                                    {{ Form::number('number_of_staff', old('number_of_staff', $company->number_of_staff ?? ''), ['class="form-control"', 'placeholder="Write number of staff..."', 'min' => 1]) }}
                                    <div class="text-danger">{{ $errors->first('number_of_staff') }}</div>
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>Company Email <span class="text-danger">*</span>
                                    </label>
                                    {{ Form::email('company_email', old('company_email', $company->email ?? ''), ['class="form-control"', 'placeholder="Write company email..."']) }}
                                    <div class="text-danger">{{ $errors->first('company_email') }}</div>
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>Website</label>
                                    {{ Form::text('company_website', old('company_website', $company->website ?? ''), ['class="form-control"', 'placeholder="Write company website..."']) }}
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <label>Company Phone <span class="text-danger">*</span></label>
                                <div class="row">
                                    <div class="col-md-4">
                                        @php $options = []; @endphp
                                        @foreach ($arr_countries as $key => $country)
                                            @php
                                                $options[$key] = $country;
                                            @endphp
                                        @endforeach
                                        {{ Form::select('country_code', $options, old('country_code', substr($company->phone ?? '', 0, 3)), ['class' => 'form-control', 'id' => 'country_id']) }}
                                        <div class="text-danger">{{ $errors->first('country_code') }}</div>
                                    </div>

                                    <div class="col-md-8">
                                        {{ Form::number('company_phone', old('company_phone', substr($company->phone ?? '', 4)), ['class="form-control"', 'placeholder="Write phone..."']) }}
                                        <div class="text-danger">{{ $errors->first('company_phone') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-lg-12">
                                <h5 class="text-capitalize">Company Location</h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>Region <span class="text-danger">*</span></label>
                                    @php $options = []; @endphp
                                    @foreach ($regions as $region)
                                        @php
                                            $options[$region->id] = $region->name;
                                        @endphp
                                    @endforeach
                                    @php
                                        $options = ['' => 'Select'] + $options;
                                    @endphp

                                    {{ Form::select('region_id', $options, old('region_id', $company->region_id ?? ''), ['class' => 'form-control select2', 'id' => 'region_id']) }}
                                    <div class="text-danger">{{ $errors->first('region_id') }}</div>
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>Postal Address </label>
                                    {{ Form::text('postal_address', old('postal_address', $company->postal_address ?? ''), ['class="form-control"', 'placeholder="Company postal address..."']) }}
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label>Physical Address <span class="text-danger">*</span></label>
                                    {{ Form::textarea('physical_address', old('company_address', $company->physical_address ?? ''), ['class="form-control"', 'placeholder="Write company physical address..."', 'rows' => 1]) }}
                                    <div class="text-danger">{{ $errors->first('physical_address') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3 text-sm-end d-none d-sm-block">
                                    <a href="{{ route('companies.lists') }}" class="btn btn-danger btn-xs text-medium">
                                        Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">Next</button>
                                </div>
                            </div>
                        </div>
                    </section>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->
@endsection

@section('script')
    <script type="text/javascript"></script>
@endsection
