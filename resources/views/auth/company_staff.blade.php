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

                                    <section data-step="4">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <h5 class="text-capitalize">Company Staff Approvers</h5>
                                                <hr />
                                            </div>
                                            <!--./col -->
                                        </div>
                                        <!--./row -->

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
                                        </div>
                                        <!--./row -->

                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="mb-3">
                                                    <label for="basicpill-namecard-input">Staff Phone<span
                                                            class="text-danger">*</span></label>
                                                    {{ Form::text('staff_phone[]', old('staff_phone[]'), ['class="form-control invinputs"','placeholder="Staff phone..."','required=""']) }}

                                                </div>
                                            </div>
                                            <!--./col -->

                                            <div class="col-lg-4">
                                                <div class="mb-3">
                                                    <label for="basicpill-namecard-input">Staff Title<span
                                                            class="text-danger">*</span></label>
                                                    @php
                                                        $options = [
                                                            'CEO' => 'Chief Executive Officer (CEO)',
                                                            'CFO' => 'Chief Finance Officer (CFO)',
                                                            'Company_Secretary' => 'Company Secretary',
                                                        ];
                                                        
                                                        $staff_title_options = $options;
                                                        $options = ['' => 'Select'] + $options;
                                                    @endphp
                                                    {{ Form::select('staff_title[]', $options, old('staff_title[]'), ['class= "form-control"', 'required=""']) }}
                                                </div>
                                            </div>
                                            <!--./col -->

                                            <div class="col-lg-4">
                                                <div class="mt-4 pt-2 text-sm-end d-none d-sm-block">
                                                    <button type="button" name="add" id="add_button"
                                                        class="btn btn-info btn-sm text-medium">
                                                        <i class="fa fa-plus"></i>
                                                    </button>
                                                </div>
                                                <!--./pull-right -->
                                            </div>
                                            <!--./col-lg-12 -->
                                        </div>
                                        <!--./row -->

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
                                <p>
                                <script>
                                    document.write(new Date().getFullYear())
                                </script> eMakato. Developed by Laina Finance
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
                var title_options = {!! json_encode($staff_title_options) !!}

                //add more accounts
                $(document).ready(function() {
                    let maxField = 5; //Input fields increment limitation
                    let wrapper = $('.field_wrapper'); //Input field wrapper
                    let fieldHTML =
                        '<div class="row row-div" style="background: #FAFAFA; border: 1px solid #E0E0E0; border-radius: 5px; margin: 8px 0; padding: 10px;">' +
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
                        '<input type="text" name="staff_phone[]" class="form-control" placeholder="Write staff phone..." required="" />' +
                        '</div></div>';

                    fieldHTML += '<div class="col-lg-4 col-md-4">' +
                        '<div class="mb-3">' +
                        '<label>Staff Title <span class="text-danger">*</span></label>';
                    fieldHTML += '<select name="staff_title[]" class="form-control" required="">';
                    fieldHTML += "<option value=''>Select</option>";
                    $.each(title_options, function(key, value) {
                        fieldHTML += "<option value='" + value + "'>" + value + "</option>";
                    });
                    fieldHTML += '</select>';
                    fieldHTML += '</div></div>';

                    fieldHTML += '<div class="col-lg-4 col-md-4">' +
                        '<div class="mt-4 pt-2 text-sm-end d-none d-sm-block">' +
                        '<button type="button" name="add" id="remove_button" class="btn btn-danger btn-sm text-medium">' +
                        '<i class="fa fa-times"></i>' +
                        '</button>' +
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
