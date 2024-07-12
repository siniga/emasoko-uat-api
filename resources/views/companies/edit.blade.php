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
                            <!--form message => success or danger -->
                            <div class="form-message"></div>

                            <form action="#" id="companyForm" enctype="multipart/form-data" autocomplete="off">
                                <input type="hidden" name="partner_id" value="{{ $company->id ?? null }}">
                                <input type="hidden" name="partner_uuid" value="{{ $company->uuid ?? null }}">

                                <div class="row">
                                    <div class="col-lg-12">
                                        <h5 class="text-capitalize">Company Information</h5>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label>Business Name <span class="text-danger">*</span></label>
                                            {{ Form::text('business_name', old('business_name', $company->business_name ?? ''), ['class="form-control"', 'placeholder="Write business name..."', 'required']) }}
                                            <span class="formError"></span>
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

                                            {{ Form::select('category_id', $options, old('category_id', $company->partner_category_id ?? ''), ['class' => 'form-control select2', 'id' => 'category_id', 'required']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <label>TIN Number <span class="text-danger">*</span></label>
                                            {{ Form::text('tin_number', old('tin_number', $company->tin_number ?? ''), ['class="form-control input-mask"', 'data-inputmask' => "'mask': '999-999-999'", 'placeholder="Write tin number..."', 'required']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
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
                                            {{ Form::select('year_of_incorporation', $year_options, old('year_of_incorporation', $company->year_of_incorporation ?? ''), ['class' => 'form-control select2', 'required' => '']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <label>Number of Staff <span class="text-danger">*</span></label>
                                            {{ Form::number('number_of_staff', old('number_of_staff', $company->number_of_staff ?? ''), ['class="form-control"', 'placeholder="Write number of staff..."', 'min' => 1, 'required']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <label>Company Email <span class="text-danger">*</span></label>
                                            {{ Form::email('company_email', old('company_email', $company->email ?? ''), ['class="form-control"', 'placeholder="Write company email..."', 'required']) }}
                                            <span class="formError"></span>
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
                                            </div>

                                            <div class="col-md-8">
                                                {{ Form::number('company_phone', old('company_phone', substr($company->phone ?? '', 4)), ['class="form-control"', 'placeholder="Write phone..."', 'required']) }}
                                                <span class="formError"></span>
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

                                            {{ Form::select('region_id', $options, old('region_id', $company->region_id ?? ''), ['class' => 'form-control select2', 'id' => 'region_id', 'required']) }}
                                            <span class="formError"></span>
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
                                            {{ Form::textarea('physical_address', old('company_address', $company->physical_address ?? ''), ['class="form-control"', 'placeholder="Write company physical address..."', 'rows' => 1, 'required']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3 text-sm-end d-none d-sm-block">
                                            <a href="{{ url('companies') }}"
                                                class="btn btn-danger btn-xs text-medium">Cancel</a>
                                            <button type="submit" id="submitButton"
                                                class="btn btn-primary btn-xs text-medium">Update</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- end row -->
@endsection



@section('script')
    <!--jquery validation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            //declare form message 
            $('.form-message').html("");

            //validate form
            $("#companyForm").validate({
                errorClass: "formError",
                errorElement: "span",
                rules: {
                    business_name: "required",
                    category_id: "required",
                    tin_number: "required",
                    year_of_incorporation: "required",
                    number_of_staff: "required",
                    company_email: {
                        required: true,
                        email: true,
                    },
                    country_code: "required",
                    company_phone: "required",
                    region_id: "required",
                    physical_address: "required",
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
                    country_code: {
                        required: "Required"
                    },
                    company_phone: {
                        required: "Phone required"
                    },
                    region_id: {
                        required: "Region required"
                    },
                    physical_address: {
                        required: "Physical address required"
                    }
                },

                //submit form
                submitHandler: function(form, e) {
                    e.preventDefault();

                    //change submit button text
                    $('#submitButton').text('Submitting...');

                    //form data
                    const formData = new FormData(form);
                    const jsonData = Object.fromEntries(formData);

                    //post data => URL
                    fetch("/companies/update", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]')
                                    .content
                            },
                            body: JSON.stringify(jsonData),
                        }).then(response => response.json())
                        .then(responseJson => {
                            //change submit button text
                            $('#submitButton').text('Update');

                            //display message
                            $('.form-message').html(
                                "<div class='alert alert-success py-2.5 px-2'>Service provider information updated</div>"
                            );

                            //set timer
                            setTimeout(function() {
                                window.location.href = "/companies/details/" + responseJson.uuid;
                            }, 2000);

                        }).catch(() => {
                            //change submit button text
                            $('#submitButton').text('Update');

                            //write error message
                            $('.form-message').html(
                                "<div class='alert alert-danger py-2.5 px-2'>Failed to update service provider information</div>"
                            );
                        }).finally(() => {
                            //console
                            console.log("updating data...");
                        });
                    return false;
                }
            });
        });
    </script>
@endsection
