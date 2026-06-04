@extends('layouts.master')

@section('title')
    Register Service Provider
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/companies-create.css') }}" rel="stylesheet" type="text/css" />
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

    @include('companies.create.partials.hero', [
        'currentStep' => 1,
        'heading' => 'Create a new service provider',
        'subtitle' => 'Start with the legal profile and operating address. This draft will continue through documents, accounts, representative details, and final review.',
        'company' => $company ?? null,
    ])

    <div class="row">
        <div class="col-lg-12">
            <div class="card sp-card">
                <div class="card-body sp-card__body">
                    <div class="form-message sp-form-message"></div>

                    <form action="#" id="companyForm" enctype="multipart/form-data" autocomplete="off">
                        <input type="hidden" name="partner_id" value="{{ $company->id ?? null }}">
                        <input type="hidden" name="partner_uuid" value="{{ $company->uuid ?? null }}">

                        <!-- Company Details -->
                        <section data-step="0">
                            <div class="sp-section">
                                <div class="sp-section__title">Business Information</div>

                                <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label>Business Name <span class="text-danger">*</span></label>
                                        {{ Form::text('business_name', old('business_name', $company->business_name ?? ''), ['class="form-control"', 'placeholder="Write business name..."']) }}
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
                                            $options = ['' => '-- Select --'] + $options;
                                        @endphp

                                        {{ Form::select('category_id', $options, old('category_id', $company->partner_category_id ?? ''), ['class' => 'form-control', 'id' => 'category_id']) }}
                                        <span class="formError"></span>
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>TIN Number <span class="text-danger">*</span></label>
                                        {{ Form::text('tin_number', old('tin_number', $company->tin_number ?? ''), ['class="form-control input-mask"', 'data-inputmask' => "'mask': '999-999-999'", 'placeholder="Write tin number..."']) }}
                                        <span class="formError" id="tinNumberError"></span>
                                    </div>
                                </div>
                            </div>
                            </div>

                            <div class="sp-section">
                                <div class="sp-section__title">Address Details</div>

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

                                        @php $year_options = ['' => '-- Select --'] + $year_options; @endphp
                                        {{ Form::select('year_of_incorporation', $year_options, old('year_of_incorporation', $company->year_of_incorporation ?? ''), ['class' => 'form-control select2']) }}
                                        <span class="formError"></span>
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Number of Staff <span class="text-danger">*</span></label>
                                        {{ Form::number('number_of_staff', old('number_of_staff', $company->number_of_staff ?? 1), ['class="form-control"', 'placeholder="Write number of staff..."', 'min' => 1]) }}
                                        <span class="formError"></span>
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Company Email <span class="text-danger">*</span>
                                        </label>
                                        {{ Form::email('company_email', old('company_email', $company->email ?? ''), ['class="form-control"', 'placeholder="Write company email..."']) }}
                                        <span class="formError" id="companyEmailError"></span>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <label>Company Phone <span class="text-danger">*</span></label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            @php $options = []; @endphp
                                            @foreach ($arr_countries as $key => $country)
                                                @php
                                                    $options[$key] = $country;
                                                @endphp
                                            @endforeach
                                            {{ Form::select('country_code', $options, old('country_code', substr($company->phone ?? '', 0, 3)), ['class' => 'form-control', 'id' => 'country_id']) }}
                                            <span class="formError"></span>
                                        </div>

                                        <div class="col-md-6">
                                            {{ Form::number('company_phone', old('company_phone', $company->phone ?? ''), ['class="form-control"', 'placeholder="Write phone..."']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>
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
                                            $options = ['' => '-- Select --'] + $options;
                                        @endphp

                                        {{ Form::select('region_id', $options, old('region_id', $company->region_id ?? ''), ['class' => 'form-control select2', 'id' => 'region_id']) }}
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
                                        {{ Form::textarea('physical_address', old('company_address', $company->physical_address ?? ''), ['class="form-control"', 'placeholder="Write company physical address..."', 'rows' => 1]) }}
                                        <span class="formError"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="sp-actionbar">
                                <div class="sp-actionbar__meta"></div>
                                <div class="d-flex gap-2">
                                        <a href="{{ route('companies.lists') }}"
                                            class="btn btn-outline-danger btn-xs text-medium">
                                            Cancel
                                        </a>
                                        <button type="submit" id="submitButton" class="btn btn-primary">Save and Continue</button>
                                </div>
                            </div>
                            </div>
                        </section>
                    </form>
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
                    fetch("/companies/store-detail", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]')
                                    .content
                            },
                            body: JSON.stringify(jsonData),
                        }).then(async response => {
                            if (!response.ok) {
                                throw response;
                            }

                            return response.json();
                        })
                        .then(responseJson => {
                            if (responseJson.errors) {
                                //display error message
                                if (jQuery.type(responseJson.errors) == "object") {
                                    $('.form-message').html('');
                                    $.each(responseJson.errors, function(key, value) {
                                        $('.form-message').append('<div class="alert alert-danger">' + value + '</div>');
                                    });
                                } else {
                                    $('.form-message').html('<div class="alert alert-danger">' + responseJson.errors + '</div>');
                                }
                            } else {
                                if(responseJson.success == true){
                                    //display message
                                    $('.form-message').html(
                                        "<div class='alert alert-success py-2.5 px-2'>Service provider information saved</div>"
                                    );

                                    //set timer
                                    setTimeout(function() {
                                        window.location.href = "/companies/documents/" + responseJson.uuid;
                                    }, 300);
                                }else if(responseJson.success == false){
                                     $('.form-message').html(
                                        '<div class="alert alert-danger py-2.5 px-2">' + responseJson.message + '</div>'
                                    );
                                }
                            }
                        }).catch(async (error) => {
                            let message = 'Failed to save service provider information.';

                            if (error instanceof Response) {
                                try {
                                    const errorJson = await error.json();
                                    message = errorJson.message || errorJson.errors || message;
                                } catch (parseError) {
                                    message = `${message} Server responded with status ${error.status}.`;
                                }
                            } else if (error && error.message) {
                                message = error.message;
                            }

                            $('.form-message').html('<div class="alert alert-danger">' + message + '</div>');
                        }).finally(() => {
                            //change submit button text
                            $('#submitButton').text('Save and Continue');
                        });
                    return false;
                }
            });
        });
    </script>
@endsection
