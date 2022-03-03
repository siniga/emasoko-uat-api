@extends('layouts.master-without-nav')

@section('title')
    @lang('translation.Register')
@endsection
@section('css')
    <link href="{{ URL::asset('/assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.css') }}" rel="stylesheet"
        type="text/css">
@endsection
@section('body')

    <body>
    @endsection

    @section('content')
        <div class="account-pages my-5 pt-sm-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-12 col-lg-12 col-xl-12">
                        <div class="card overflow-hidden">
                            <div class="card-body pt-4">
                                <form action="#" id="registrationForm" enctype="multipart/form-data" autocomplete="off">
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
                                                    <label for="businessname">Business Name <span
                                                            class="text-danger">*</span>
                                                    </label>
                                                    {{ Form::text('business_name', old('business_name', $company->business_name ?? ''), ['class="form-control"','placeholder="Write business name..."','required']) }}
                                                    <span class="text-danger"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-3">
                                                <div class="mb-3">
                                                    <label for="email">Category <span
                                                            class="text-danger">*</span></label>
                                                    @php $options = []; @endphp
                                                    @foreach ($categories as $category)
                                                        @php
                                                            $options[$category->id] = $category->name;
                                                        @endphp
                                                    @endforeach
                                                    @php
                                                        $options = ['' => 'Select'] + $options;
                                                    @endphp

                                                    {{ Form::select('category_id', $options, old('category_id', $company->partner_category_id ?? ''), ['class' => 'form-control','id' => 'category_id','required']) }}
                                                    <span class="text-danger"></span>
                                                </div>
                                            </div>
                                            <!--./col -->

                                            <div class="col-lg-3">
                                                <div class="mb-3">
                                                    <label for="tinno">TIN Number <span
                                                            class="text-danger">*</span></label>
                                                    {{ Form::text('tin_number', old('tin_number', $company->tin_number ?? ''), ['class="form-control input-mask"','data-inputmask' => "'mask': '999-999-999'",'placeholder="Write tin number..."','required']) }}
                                                    <span class="text-danger"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <!--./row -->

                                        <div class="row">
                                            <div class="col-lg-3">
                                                <div class="mb-3">
                                                    <label for="email">Year of Incorporation <span
                                                            class="text-danger">*</span> </label>
                                                    @php
                                                        $year_options = [];
                                                        $current_year = date('Y');
                                                    @endphp

                                                    @foreach (range($current_year, $current_year - 20) as $v)
                                                        @php $year_options[$v] = $v; @endphp
                                                    @endforeach

                                                    @php $year_options = ['' => 'Select'] + $year_options; @endphp
                                                    {{ Form::select('year_of_incorporation',$year_options,old('year_of_incorporation', $company->year_of_incorporation ?? ''),['class' => 'form-control', 'required' => '']) }}
                                                </div>
                                            </div>
                                            <!--./col -->

                                            <div class="col-lg-3">
                                                <div class="mb-3">
                                                    <label for="email">Number of Staff <span class="text-danger">*</span>
                                                    </label>
                                                    {{ Form::number('number_of_staff', old('number_of_staff', $company->number_of_staff ?? ''), ['class="form-control"','placeholder="Write number of staff..."','min' => 1,'required']) }}
                                                    <span class="text-danger"></span>
                                                </div>
                                            </div>
                                            <!--./col -->

                                            <div class="col-lg-3">
                                                <div class="mb-3">
                                                    <label for="email">Company Email <span class="text-danger">*</span>
                                                    </label>
                                                    {{ Form::email('company_email', old('company_email', $company->email ?? ''), ['class="form-control"','placeholder="Write company email..."','required']) }}
                                                    <span class="text-danger"></span>
                                                </div>
                                            </div>
                                            <!--./col -->

                                            <div class="col-lg-3">
                                                <div class="mb-3">
                                                    <label for="location">Company Phone <span
                                                            class="text-danger">*</span></label>
                                                    {{ Form::text('company_phone', old('company_phone', $company->phone ?? ''), ['class="form-control"','placeholder="Write company phone..."','required']) }}
                                                    <span class="text-danger"></span>
                                                </div>
                                            </div>
                                            <!--./col -->

                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label for="address">Website</label>
                                                    {{ Form::text('company_website', old('company_website', $company->website ?? ''), ['class="form-control"','placeholder="Write company website..."']) }}
                                                </div>
                                            </div>
                                            <!--./col -->
                                        </div>
                                        <!--./row -->

                                        <div class="row mt-2">
                                            <div class="col-lg-12">
                                                <h5 class="text-capitalize">Company Location</h5>
                                                <hr />
                                            </div>
                                            <!--./col -->
                                        </div>
                                        <!--./row -->

                                        <div class="row">
                                            <div class="col-lg-6">
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

                                                    {{ Form::select('region_id', $options, old('region_id', $company->region_id ?? ''), ['class' => 'form-control','id' => 'region_id','required']) }}
                                                    <span class="text-danger"></span>
                                                </div>
                                            </div>
                                            <!--./col -->

                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label for="address">Address <span
                                                            class="text-danger">*</span></label>
                                                    {{ Form::textarea('company_address', old('company_address', $company->address ?? ''), ['class="form-control"','placeholder="Write company address..."','rows' => 2,'required']) }}
                                                    <span class="text-danger"></span>
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
                                </form>
                            </div>
                            <!--./card-body -->
                        </div>
                        <!--./card -->

                        <div class="mt-2 text-center">
                            <div>
                                <p>Already have an account ? <a href="{{ url('login') }}" class="fw-medium text-primary">
                                        Login</a>
                                </p>
                                <script>
                                    document.write(new Date().getFullYear())
                                </script> eMakato. Developed <i class="mdi mdi-heart text-danger"></i> by Laina
                                Finance
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
        <script src="{{ URL::asset('/assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>

        <!--jquery validation -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js"></script>

        <script type="text/javascript">
            $(document).ready(function() {
                //validate form and submit
                $("#registrationForm").validate({
                    rules: {
                        business_name: {
                            required: true
                        },
                        category_id: {
                            required: true
                        },
                        tin_number: {
                            required: true
                        },
                        year_of_incorporation: {
                            required: true
                        },
                        number_of_staff: {
                            required: true
                        },
                        company_email: {
                            required: true,
                            email: true,
                        },
                        company_phone: {
                            required: true
                        },
                        region_id: {
                            required: true
                        },
                        company_address: {
                            required: true
                        }
                    },
                    messages: {
                        business_name: {
                            required: "Business name required"
                        },
                        category_id: {
                            required: "Business category required"
                        },
                        tin_number: {
                            required: "TIN Number required"
                        },
                        year_of_incorporation: {
                            required: "Year of incorporation required"
                        },
                        number_of_staff: {
                            required: "Number of staff required"
                        },
                        company_email: {
                            required: "Company email required",
                            email: "Invalid email",
                        },
                        company_phone: {
                            required: "Company phone required"
                        },
                        region_id: {
                            required: "Region required"
                        },
                        company_address: {
                            required: "Company address required"
                        }
                    },
                    submitHandler: function(form, e) {
                        e.preventDefault();

                        // Activate the loading spinner
                        $('.loading-spinner').toggleClass('active');

                        // Get form
                        var form = $(form)[0];

                        // FormData object 
                        var data = new FormData(form);

                        //append token
                        data.append("_token", "{{ csrf_token() }}");

                        // disabled the submit button
                        $("#btnSubmit").prop("disabled", true);

                        //post data 
                        $.ajax({
                            type: "POST",
                            url: "{{ url('store-company-details') }}",
                            data: data,
                            processData: false,
                            contentType: false,
                            cache: false,
                            timeout: 800000,
                            success: function(data) {
                                $("#btnSubmit").prop("disabled", false);

                                // Deactivate Loading Spinner
                                $('.loading-spinner').toggleClass('active');

                                //redirect
                                window.location.href = "/company-documents/" + data.uuid
                            },
                            error: function(e) {
                                $("#btnSubmit").prop("disabled", false);
                            }
                        });
                        return false;
                    },
                });
            });
        </script>
    @endsection
