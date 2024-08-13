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
                <div class="card-body">
                    <!--./form wizard -->
                    <div class="stepwizard mb-4">
                        <div class="stepwizard-row setup-panel">

                            <div class="stepwizard-step">
                                <a href="#step-1" type="button" class="btn btn-success btn-circle text-white">1</a>
                                <span class="text-success">Company Information</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-success btn-circle text-white"
                                    disabled="disabled">2</a>
                                <span class="text-success">Company Documents</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-success btn-circle text-white"
                                    disabled="disabled">3</a>
                                <span class="text-success">Collection Accounts</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-success btn-circle text-white"
                                    disabled="disabled">4</a>
                                <span class="text-success">Authorized Representative</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-5" type="button" class="btn btn-secondary btn-circle"
                                    disabled="disabled">5</a>
                                <span class="text-primary">Company Preview</span>
                            </div>
                        </div>
                    </div>

                    <!--form message => success or danger -->
                    <div class="form-message"></div>

                    <form action="#" id="companyForm" enctype="multipart/form-data" autocomplete="off">
                        <input type="hidden" name="partner_id" value="{{ $company->id }}">
                        <input type="hidden" name="partner_uuid" value="{{ $company->uuid }}">

                        <!-- Company Documents -->
                        <section data-step="5">
                            <div class="row">
                                <div class="col-lg-12">
                                    <h5 class="text-capitalize"> Login Credentials</h5>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="email">Business Name <span class="text-danger">*</span></label>
                                        {{ Form::text('name', old('name', $company->business_name), ['class="form-control"', 'placeholder="Write business name..."']) }}
                                        <span class="formError"></span>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="email">TIN Number <span class="text-danger">*</span></label>
                                        {{ Form::text('tin_number', old('tin_number', $company->tin_number), ['class="form-control"', 'placeholder="Write tin number..."']) }}
                                        <span class="formError"></span>
                                    </div>
                                </div>

                                 <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="email">Phone <span class="text-danger">*</span></label>
                                        {{ Form::text('phone', old('phone', $company->phone), ['class="form-control"', 'placeholder="Write phone..."']) }}
                                        <span class="formError"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="email">Email/Username <span class="text-danger">*</span></label>
                                        {{ Form::email('email', old('email', $company->email), ['class="form-control"', 'placeholder="Write email..."']) }}
                                        <span class="formError"></span>
                                    </div>
                                </div>
                            
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="phone_number">Password <span class="text-danger">*</span></label>
                                        {{ Form::password('password', ['placeholder="Write a password..."', 'class="form-control"', 'id="password"']) }}
                                        <span class="formError"></span>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="category">Repeat Password <span class="text-danger">*</span></label>
                                        {{ Form::password('confirm_password', ['placeholder="Confirm password..."', 'class="form-control"']) }}
                                        <span class="formError"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3 text-sm-end d-none d-sm-block">
                                        <a href="{{ route('companies.register-staff', $company->uuid) }}"
                                            class="btn btn-info text-medium">Previous</a>
                                        <button type="submit" id="submitButton" class="btn btn-primary">Next</button>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </form>

                </div>
            </div>
        </div>
    </div>
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
                    'name': 'required',
                    'tin_number': 'required',
                    'email': 'required',
                    'phone': 'required',
                    'password': 'required',
                    'confirm_password': 'required',
                    'confirm_password': {
                        required: true,
                        equalTo: "#password"
                    }
                },
                messages: {
                    "name": "Business name required",
                    "tin_number": "TIN number required",
                    "email": "Email required",
                    "phone": "Phone required",
                    "password": "Password required",
                    "confirm_password": {
                        required: "Confirm password required",
                        equalTo: "Password should match",
                    }
                },

                //submit form
                submitHandler: function(form, e) {
                    e.preventDefault();

                    //change submit button text
                    $('#submitButton').text('Submitting...');

                    //form data
                    const formData = new FormData(form);
                    formData.append("_token", "{{ csrf_token() }}");

                    //post data 
                    $.ajax({
                        type: "POST",
                        url: "/companies/store-authorized-person",
                        data: formData,
                        processData: false,
                        contentType: false,
                        cache: false,
                        timeout: 800000,
                        success: function(response) {
                            //change submit button text
                            $('#submitButton').text('Next');

                            //display message
                            $('.form-message').html(
                                "<div class='alert alert-success py-2.5 px-2'>Service Provider credential(s) saved</div>"
                            );

                            //set timer
                            setTimeout(function() {
                                window.location.href = "/companies/preview/" + response.uuid;
                            }, 300);
                        },
                        error: function(e) {
                            $('#submitButton').text('Next');
                        }
                    });

                    return false;
                }

            });

        });
    </script>
@endsection
