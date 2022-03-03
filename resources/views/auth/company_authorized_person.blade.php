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
                                    <input type="hidden" name="partner_id" value="{{ $company->id }}">
                                    <input type="hidden" name="partner_uuid" value="{{ $company->uuid }}">

                                    <!-- Company Documents -->
                                    <section data-step="5">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <h5 class="text-capitalize">Company Authorized Person</h5>
                                                <hr />
                                            </div>
                                            <!--./col -->
                                        </div>
                                        <!--./row -->

                                        <div class="row">
                                            <div class="col-lg-3">
                                                <div class="mb-3">
                                                    <label for="tinno">Authorized Person Name <span
                                                            class="text-danger">*</span></label>
                                                    {{ Form::text('person_name', old('person_name'), ['class="form-control"','placeholder="Write a fullname..."','required']) }}
                                                </div>
                                            </div>
                                            <!--./col -->

                                            <div class="col-lg-3">
                                                <div class="mb-3">
                                                    <label for="email">NIDA Number <span
                                                            class="text-danger">*</span></label>
                                                    {{ Form::text('nida_number', old('nida_number'), ['class="form-control input-mask"','placeholder="Write number..."','data-inputmask' => "'mask': '99999999-99999-99999-99'",'required']) }}
                                                </div>
                                            </div>
                                            <!--./col -->

                                            <div class="col-lg-3">
                                                <div class="mb-3">
                                                    <label for="email">Email <span class="text-danger">*</span></label>
                                                    {{ Form::email('email', old('email'), ['class="form-control"', 'placeholder="Write email..."', 'required']) }}
                                                </div>
                                            </div>
                                            <!--./col -->

                                            <div class="col-lg-3">
                                                <div class="mb-3">
                                                    <label for="location">Phone <span
                                                            class="text-danger">*</span></label>
                                                    {{ Form::text('phone', old('phone'), ['class="form-control"', 'placeholder="Write a phone..."', 'required']) }}
                                                </div>
                                            </div>
                                            <!--./col -->
                                        </div>
                                        <!--./row -->

                                        <div class="row mt-3">
                                            <div class="col-lg-12">
                                                <h5 class="text-capitalize font-weight-600">Authorized Person Login</h5>
                                                <hr />
                                            </div>
                                            <!--./col -->
                                        </div>
                                        <!--./row -->

                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label for="phone_number">Password <span
                                                            class="text-danger">*</span></label>
                                                    {{ Form::password('password', ['placeholder="Write a password..."','class="form-control"','id="password"','required']) }}
                                                    <span class="text-danger"></span>
                                                </div>
                                            </div>
                                            <!--./col -->

                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label for="category">Repeat Password <span
                                                            class="text-danger">*</span></label>
                                                    {{ Form::password('confirm_password', ['placeholder="Confirm password..."','class="form-control"','required']) }}
                                                    <span class="text-danger"></span>
                                                </div>
                                            </div>
                                            <!--./col -->
                                        </div>
                                        <!--./row -->

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3 text-sm-end d-none d-sm-block">
                                                    <a href="{{ url('company-staff/' . $company->uuid) }}"
                                                        class="btn btn-info text-medium">Previous</a>

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
                                <script>
                                    document.write(new Date().getFullYear())
                                </script> eMakato. Developed by LainaFinance

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
                        "person_name": "required",
                        "nida_number": "required",
                        "email": "required",
                        "phone": "required",
                        "password": "required",
                        "confirm_password": "required",
                        confirm_password: {
                            equalTo: "#password"
                        }
                    },
                    messages: {
                        "person_name": "Person name required",
                        "nida_number": "NIDA number required",
                        "email": "Email required",
                        "phone": "Phone required",
                        "password": "Password required",
                        "confirm_password": "Password required",
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
                            url: "{{ url('store-company-authorized-person') }}",
                            data: data,
                            processData: false,
                            contentType: false,
                            cache: false,
                            timeout: 800000,
                            success: function(data) {
                                $("#btnSubmit").prop("disabled", false);

                                // Deactivate Loading Spinner
                                $('.loading-spinner').toggleClass('active');

                                //uuid
                                var uuid = data.uuid

                                //redirect
                                window.location.href = "/company-info-overview/" + uuid
                            },
                            error: function(e) {
                                $("#btnSubmit").prop("disabled", false);
                            }
                        });
                        return false;
                    },
                });


                //on select account category
                $('#account_type_id').on('change', function(e) {
                    e.preventDefault();
                    accountTypeId = $(this).val();

                    $.ajax({
                        url: '/account-types/' + accountTypeId,
                        type: "get",
                        success: function(data) {
                            $('#account_id').html(data);
                        }
                    });
                });

            });
        </script>
    @endsection
