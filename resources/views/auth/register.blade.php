@extends('layouts.master-without-nav')

@section('title')
    @lang('translation.Register')
@endsection

@section('content')
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-12 col-lg-12 col-xl-12">
                    <div class="card overflow-hidden">
                        <div class="bg-primary bg-soft">
                            <div class="row">
                                <div class="col-12">
                                    <div class="text-center p-4">
                                        <h3>Company Registration</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--./soft -->

                        <div class="card-body pt-4">
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
                                        <a href="#step-2" type="button" class="btn btn-default btn-circle"
                                            disabled="disabled">5</a>
                                        <span>Credentials</span>
                                    </div>
                                </div>
                            </div>
                            <!--./form wizard -->

                            @if (session()->get('success'))
                                <div class="alert alert-success">
                                    {{ session()->get('success') }}
                                </div><br />
                            @elseif(session()->get('danger'))
                                <div class="alert alert-danger">
                                    {{ session()->get('danger') }}
                                </div><br />
                            @endif

                            {{ Form::open(['url' => route('company.store-company'), 'class' => 'form-horizontal needs-validation', 'enctype' => 'multipart/form-data', 'novalidate']) }}
                            @method('POST')
                            {{ Form::token() }}

                            <input type="hidden" name="partner_id" value="{{ $company->id ?? null }}">
                            <input type="hidden" name="partner_uuid" value="{{ $company->uuid ?? null }}">

                            <!-- Company Details -->
                            <section data-step="0">
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
                                            <label for="address">Physical Address <span
                                                    class="text-danger">*</span></label>
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
                                    <div class="col-md-12">
                                        <div class="mb-3 text-sm-end d-none d-sm-block">
                                            <button type="reset" class="btn btn-danger text-medium ">Reset</button>
                                            <button type="submit" id="btnSubmit" class="btn btn-primary">
                                                Next <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <!--./col -->
                                </div>
                                <!--./row -->
                            </section>
                            {{ Form::close() }}
                        </div>
                        <!--./card-body -->
                    </div>
                    <!--./card -->

                    <div class="mt-2 text-center">
                        <div>
                            <p>
                                Already have an account ?
                                <a href="{{ url('login') }}" class="fw-medium text-primary">
                                    Login
                                </a>
                            </p>
                            <p>
                                <script>
                                    document.write(new Date().getFullYear())
                                </script> eMakato. All rights reserved.
                            </p>
                        </div>
                    </div>
                    <!--./text-center -->
                </div>
                <!--./col-->
            </div>
            <!--./row -->
        </div>
        <!--./container -->
    </div>
    <!--./account-pages -->
@endsection
@section('script')
    <script type="text/javascript"></script>
@endsection
