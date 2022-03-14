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
        <div class="account-pages my-4">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-12 col-lg-12 col-xl-12">
                        <div class="card overflow-hidden">
                            <div class="bg-primary bg-soft">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="text-center p-4">
                                            <h2 class="text-primary">eMakato</h2>
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
                                            <a href="#step-1" type="button" class="btn btn-success btn-circle">1</a>
                                            <span class="text-success">Company Information</span>
                                        </div>

                                        <div class="stepwizard-step">
                                            <a href="#step-2" type="button" class="btn btn-success btn-circle"
                                                disabled="disabled">2</a>
                                            <span>Company Documents</span>
                                        </div>

                                        <div class="stepwizard-step">
                                            <a href="#step-2" type="button" class="btn btn-success btn-circle"
                                                disabled="disabled">3</a>
                                            <span>Collection Accounts</span>
                                        </div>

                                        <div class="stepwizard-step">
                                            <a href="#step-2" type="button" class="btn btn-secondary btn-circle"
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

                                <form action="#" id="registrationForm" enctype="multipart/form-data" autocomplete="off">
                                    <input type="hidden" name="partner_id" value="{{ $company->id }}">
                                    <input type="hidden" name="partner_uuid" value="{{ $company->uuid }}">

                                    <section data-step="4">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <h5 class="text-capitalize">Company Authorized Representative</h5>
                                                <hr />
                                            </div>
                                            <!--./col -->
                                        </div>
                                        <!--./row -->

                                        @if ($company->partnerStaff->isNotEmpty())
                                            @foreach ($company->partnerStaff as $data)
                                                <div class="row">
                                                    <div class="col-lg-4">
                                                        <div class="mb-3">
                                                            <label for="basicpill-namecard-input">Staff Name <span
                                                                    class="text-danger">*</span></label>
                                                            {{ Form::text('staff_name[]', old('staff_name[]', $data->name), ['class="form-control"','placeholder="Staff name..."','required=""']) }}

                                                        </div>
                                                    </div>
                                                    <!--./col -->

                                                    <div class="col-lg-4">
                                                        <div class="mb-3">
                                                            <label for="basicpill-namecard-input">NIDA Number <span
                                                                    class="text-danger">*</span></label>
                                                            {{ Form::text('nida_number[]', old('nida_number[]', $data->NIDA_number), ['class="form-control input-mask"','placeholder="NIDA number..."','data-inputmask' => "'mask': '99999999-99999-99999-99'",'required=""']) }}

                                                        </div>
                                                    </div>
                                                    <!--./col -->

                                                    <div class="col-lg-4">
                                                        <div class="mb-3">
                                                            <label for="basicpill-namecard-input">Staff Email<span
                                                                    class="text-danger">*</span></label>
                                                            {{ Form::email('staff_email[]', old('staff_email[]', $data->email), ['class="form-control invinputs"','placeholder="Staff email..."','required=""']) }}

                                                        </div>
                                                    </div>
                                                    <!--./col -->
                                                
                                                    <div class="col-lg-4">
                                                        <div class="mb-3">
                                                            <label for="basicpill-namecard-input">Staff Phone<span
                                                                    class="text-danger">*</span></label>
                                                            {{ Form::number('staff_phone[]', old('staff_phone[]', $data->phone), ['class="form-control"','placeholder="Staff phone..."','required=""']) }}
                                                        </div>
                                                    </div>
                                                    <!--./col -->

                                                    <div class="col-lg-4">
                                                        <div class="mb-3">
                                                            <label for="basicpill-namecard-input">Staff Title<span
                                                                    class="text-danger">*</span></label>
                                                            {{ Form::text('staff_title[]', old('staff_title[]', $data->title), ['class= "form-control"', 'required=""']) }}
                                                        </div>
                                                    </div>
                                                    <!--./col -->

                                                    <div class="col-lg-4">
                                                        <div class="mt-4 pt-2 text-sm-end d-none d-sm-block">
                                                            <button type="button" name="add" id="remove_button"
                                                                class="btn btn-danger btn-sm text-medium">
                                                                <i class="fa fa-times"></i> 
                                                            </button>
                                                        </div>
                                                        <!--./pull-right -->
                                                    </div>
                                                    <!--./col-lg-12 -->
                                                </div>
                                                <!--./row -->
                                            @endforeach
                                        @else
                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="mb-3">
                                                        <label for="basicpill-namecard-input">Staff Name <span
                                                                class="text-danger">*</span></label>
                                                        {{ Form::text('staff_name[]', old('staff_name[]'), ['class="form-control"','placeholder="Staff name..."','required=""']) }}

                                                    </div>
                                                </div>
                                                <!--./col -->

                                                <div class="col-lg-4">
                                                    <div class="mb-3">
                                                        <label for="basicpill-namecard-input">NIDA Number <span
                                                                class="text-danger">*</span></label>
                                                        {{ Form::text('nida_number[]', old('nida_number[]'), ['class="form-control input-mask"','placeholder="NIDA number..."','data-inputmask' => "'mask': '99999999-99999-99999-99'",'required=""']) }}

                                                    </div>
                                                </div>
                                                <!--./col -->

                                                <div class="col-lg-4">
                                                    <div class="mb-3">
                                                        <label for="basicpill-namecard-input">Staff Email<span
                                                                class="text-danger">*</span></label>
                                                        {{ Form::email('staff_email[]', old('staff_email[]'), ['class="form-control invinputs"','placeholder="Staff email..."','required=""']) }}

                                                    </div>
                                                </div>
                                                <!--./col -->
                                            
                                                <div class="col-lg-4">
                                                    <div class="mb-3">
                                                        <label for="basicpill-namecard-input">Staff Phone<span
                                                                class="text-danger">*</span></label>
                                                        {{ Form::number('staff_phone[]', old('staff_phone[]'), ['class="form-control invinputs"','placeholder="255xxxxxxxxx..."','required=""']) }}
                                                    </div>
                                                </div>
                                                <!--./col -->

                                                <div class="col-lg-4">
                                                    <div class="mb-3">
                                                        <label for="basicpill-namecard-input">Staff Title<span
                                                                class="text-danger">*</span></label>
                                                        {{ Form::text('staff_title[]', old('staff_title[]'), ['class= "form-control"', 'placeholder="Write title..."', 'required=""']) }}
                                                    </div>
                                                </div>
                                                <!--./col -->

                                                <div class="col-lg-4">
                                                    <div class="mt-4 pt-2 text-sm-end d-none d-sm-block">
                                                        <button type="button" name="add" id="add_button"
                                                            class="btn btn-info btn-sm text-medium">
                                                            <i class="fa fa-plus"></i> Add another staff
                                                        </button>
                                                    </div>
                                                    <!--./pull-right -->
                                                </div>
                                                <!--./col-lg-12 -->
                                            </div>
                                            <!--./row -->
                                        @endif

                                        <div class="field_wrapper"></div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3 text-sm-end d-none d-sm-block">
                                                    <a href="{{ url('company-accounts/' . $company->uuid) }}"
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
                                <p>Already have an account ? <a href="{{ url('login') }}" class="fw-medium text-primary">
                                        Login</a>
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
        <script src="{{ URL::asset('/assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>

        <!--jquery validation -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js"></script>

        <script type="text/javascript">
            $(document).ready(function() {
                //data
                //add more accounts
                $(document).ready(function() {
                    let maxField = 5; //Input fields increment limitation
                    let wrapper = $('.field_wrapper'); //Input field wrapper
                    let fieldHTML =
                        '<div class="row row-div">' +
                        '<div class="col-lg-4">' +
                        '<div class="mb-3">' +
                        '<label>Staff Name <span class="text-danger">*</span></label>' +
                        '<input type="text" name="staff_name[]" class="form-control" placeholder="Write full name..." required />' +
                        '</div></div>';

                    fieldHTML += '<div class="col-lg-4 col-md-4">' +
                        '<div class="mb-3">' +
                        '<label>NIDA Number <span class="text-danger">*</span></label>' +
                        '<input type="text" name="nida_number[]" class="form-control input-mask" placeholder="Write NIDA number..." data-inputmask="" required="" />' +
                        '</div></div>';

                    fieldHTML += '<div class="col-lg-4 col-md-4">' +
                        '<div class="mb-3">' +
                        '<label>Staff Email <span class="text-danger">*</span></label>' +
                        '<input type="email" name="staff_email[]" class="form-control invinputs" placeholder="Write staff email..." required="" />' +
                        '</div></div>';

                    fieldHTML += '<div class="col-lg-4 col-md-4">' +
                        '<div class="mb-3">' +
                        '<label>Staff Phone <span class="text-danger">*</span></label>' +
                        '<input type="text" name="staff_phone[]" class="form-control" placeholder="255xxxxxxxxx..." required="" />' +
                        '</div></div>';

                    fieldHTML += '<div class="col-lg-4 col-md-4">' +
                        '<div class="mb-3">' +
                        '<label>Staff Title <span class="text-danger">*</span></label>' +
                        '<input type="text" name="staff_title[]" class="form-control" placeholder="Write title..." required />' +
                        '</div></div>';

                    fieldHTML += '<div class="col-lg-4 col-md-4">' +
                        '<div class="mt-4 pt-2 text-sm-end d-none d-sm-block">' +
                        'Do you want to delete this staff information? <input type="checkbox" id="remove_button" />' +
                        // '<button type="button" name="add" id="remove_button" class="btn btn-danger btn-sm text-medium">' +
                        // '<i class="fa fa-times"></i>' +
                        // '</button>' +
                        '</div></div>';

                    fieldHTML += '</div>';

                    let x = 1; //Initial field counter is 1
                    //Once add button is clicked
                    $('#add_button').click(function() {
                        //Check maximum number of input fields
                        if (x < maxField) {
                            x++; //Increment field counter
                            $(wrapper).append(fieldHTML); //Add field html
                        }
                    });

                    //Once remove button is clicked
                    $(wrapper).on('click', '#remove_button', function(e) {
                        e.preventDefault();
                        $(this).closest('.row-div').remove();
                        x--; //Decrement field counter
                    });
                });

                //validate form and submit
                $("#registrationForm").validate({
                    rules: {
                        "staff_name[]": "required",
                        "staff_email[]": "required",
                        "staff_phone[]": "required",
                        "staff_title[]": "required",
                        "nida_number[]": "required",
                    },
                    messages: {
                        "staff_name[]": "Staff name required",
                        "staff_email[]": "Email required",
                        "staff_phone[]": "Phone required",
                        "staff_title[]": "Staff title required",
                        "nida_number[]": "NIDA number required",
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
                            url: "{{ url('store-company-staff') }}",
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
                                window.location.href = "/company-authorized-person/" + data.uuid
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
