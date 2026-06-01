@extends('layouts.master')

@section('title')
    Register Service Provider
@endsection

@include('companies.create.partials.styles')

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
        'currentStep' => 4,
        'heading' => 'Capture the authorized representative',
        'subtitle' => 'Add the representative details that will be used for approvals, operational follow-up, and formal communication.',
        'company' => $company,
    ])

    <div class="row">
        <div class="col-lg-12">
            <div class="card sp-card">
                <div class="card-body sp-card__body">
                    <div class="form-message sp-form-message"></div>

                    <form action="#" id="companyForm" enctype="multipart/form-data" autocomplete="off">
                        <input type="hidden" name="partner_id" value="{{ $company->id }}">
                        <input type="hidden" name="partner_uuid" value="{{ $company->uuid }}">

                        <section data-step="4">
                            <div class="sp-section">
                                <div class="sp-section__title">Authorized Representative</div>

                            @if ($company->partnerStaff->isNotEmpty())
                                @foreach ($company->partnerStaff as $data)
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label>Staff Name <span class="text-danger">*</span></label>
                                                {{ Form::text('staff_name[]', old('staff_name[]', $data->name), ['class="form-control"', 'placeholder="Staff name..."']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label>NIDA Number <span class="text-danger">*</span></label>
                                                {{ Form::text('nida_number[]', old('nida_number[]', $data->NIDA_number), ['class="form-control input-mask"', 'placeholder="NIDA number..."', 'data-inputmask' => "'mask': '99999999-99999-99999-99'"]) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label>Staff Email <span class="text-danger">*</span></label>
                                                {{ Form::email('staff_email[]', old('staff_email[]', $data->email), ['class="form-control invinputs"', 'placeholder="Staff email..."']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label>Staff Phone <span class="text-danger">*</span></label>
                                                {{ Form::number('staff_phone[]', old('staff_phone[]', $data->phone), ['class="form-control"', 'placeholder="Staff phone..."']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="basicpill-namecard-input">Staff Title <span
                                                        class="text-danger">*</span></label>
                                                {{ Form::text('staff_title[]', old('staff_title[]', $data->title), ['class= "form-control"']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="mt-4 pt-2 text-sm-end d-none d-sm-block">
                                                <button type="button" name="add" id="add_button"
                                                    class="btn btn-info btn-sm text-medium">
                                                    <i class="fa fa-plus"></i> Add another staff
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label>Staff Name <span class="text-danger">*</span></label>
                                            {{ Form::text('staff_name[]', old('staff_name[]'), ['class="form-control"', 'placeholder="Staff name..."']) }}
                                            <span class="formError"></span>

                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label>NIDA Number <span class="text-danger">*</span></label>
                                            {{ Form::text('nida_number[]', old('nida_number[]'), ['class="form-control input-mask"', 'placeholder="NIDA number..."', 'data-inputmask' => "'mask': '99999999-99999-99999-99'"]) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label>Staff Email <span class="text-danger">*</span></label>
                                            {{ Form::email('staff_email[]', old('staff_email[]'), ['class="form-control invinputs"', 'placeholder="Staff email..."']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label>Staff Phone <span class="text-danger">*</span></label>
                                            {{ Form::number('staff_phone[]', old('staff_phone[]'), ['class="form-control invinputs"', 'placeholder="Write phone 0753..."']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="basicpill-namecard-input">Staff Title <span
                                                    class="text-danger">*</span></label>
                                            {{ Form::text('staff_title[]', old('staff_title[]'), ['class= "form-control"', 'placeholder="Write title..."']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mt-4 pt-2 text-sm-end d-none d-sm-block">
                                            <button type="button" name="add" id="add_button"
                                                class="btn btn-info btn-sm text-medium">
                                                <i class="fa fa-plus"></i> Add another staff
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="field_wrapper"></div>

                            <div class="sp-actionbar">
                                <div class="sp-actionbar__meta">Step 4 of 5. Save the representative details before reviewing the full onboarding package.</div>
                                <div class="d-flex gap-2">
                                        <a href="{{ route('companies.register-accounts', $company->uuid) }}"
                                            class="btn btn-outline-secondary text-medium">Previous</a>
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
            let maxField = 5; //Input fields increment limitation
            let wrapper = $('.field_wrapper'); //Input field wrapper
            let fieldHTML =
                '<div class="row row-div border-top border-primary py-4">' +
                '<div class="col-lg-4">' +
                '<div class="mb-3">' +
                '<label>Staff Name <span class="text-danger">*</span></label>' +
                '<input type="text" name="staff_name[]" class="form-control" placeholder="Write full name..." />';
            fieldHTML += '<span class="formError"></span>';
            fieldHTML += '</div></div>';

            fieldHTML += '<div class="col-lg-4 col-md-4">' +
                '<div class="mb-3">' +
                '<label>NIDA Number <span class="text-danger">*</span></label>' +
                '<input type="text" name="nida_number[]" class="form-control input-mask" placeholder="Write NIDA number..." data-inputmask="" />';
            fieldHTML += '<span class="formError"></span>';
            fieldHTML += '</div></div>';

            fieldHTML += '<div class="col-lg-4 col-md-4">' +
                '<div class="mb-3">' +
                '<label>Staff Email <span class="text-danger">*</span></label>' +
                '<input type="email" name="staff_email[]" class="form-control invinputs" placeholder="Write staff email..." />';
            fieldHTML += '<span class="formError"></span>';
            fieldHTML += '</div></div>';

            fieldHTML += '<div class="col-lg-4 col-md-4">' +
                '<div class="mb-3">' +
                '<label>Staff Phone <span class="text-danger">*</span></label>' +
                '<input type="text" name="staff_phone[]" class="form-control" placeholder="Write phone 0753..." />';
            fieldHTML += '<span class="formError"></span>';
            fieldHTML += '</div></div>';

            fieldHTML += '<div class="col-lg-4 col-md-4">' +
                '<div class="mb-3">' +
                '<label>Staff Title <span class="text-danger">*</span></label>' +
                '<input type="text" name="staff_title[]" class="form-control" placeholder="Write title..." />';
            fieldHTML += '<span class="formError"></span>';
            fieldHTML += '</div></div>';

            fieldHTML += '<div class="col-lg-4 col-md-4">' +
                '<div class="mt-4 pt-2 text-sm-end d-none d-sm-block">' +
                'Do you want to delete this staff information? <input type="checkbox" id="remove_button" />' +
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

            //declare form message 
            $('.form-message').html("");

            //validate form
            $("#companyForm").validate({
                errorClass: "formError",
                errorElement: "span",
                rules: {
                    "staff_name[]": "required",
                    "nida_number[]": "required",
                    "staff_email[]": "required",
                    "staff_phone[]": "required",
                    "staff_title[]": "required",
                },
                messages: {
                    "staff_name[]": "Staff name required",
                    "nida_number[]": "NIDA number required",
                    "staff_email[]": "Email address required",
                    "staff_phone[]": "Phone required",
                    "staff_title[]": "Title required",
                },

                //submit form
                submitHandler: function(form, e) {
                    e.preventDefault();

                    //change submit button text
                    $('#submitButton').text('Submitting...');

                    //form data
                    const formData = new FormData(form);

                    //append token
                    formData.append("_token", "{{ csrf_token() }}");

                    //post data 
                    $.ajax({
                        type: "POST",
                        url: "/companies/store-staff",
                        data: formData,
                        processData: false,
                        contentType: false,
                        cache: false,
                        timeout: 800000,
                        success: function(response) {
                            if(response.success == true){
                                //change submit button text
                                $('#submitButton').text('Next');

                                //display message
                                $('.form-message').html(
                                    "<div class='alert alert-success py-2.5 px-2'>Service Provider credential saved</div>"
                                );

                                //set timer
                                setTimeout(function() {
                                    window.location.href = "/companies/preview/" + response.uuid;
                                }, 300);
                            }   else if(response.success == false){
                                     $('.form-message').html(
                                        '<div class="alert alert-danger py-2.5 px-2">' + response.message + '</div>'
                                    );
                            }
                        },
                        error: function(error) {
                            //display errors
                            if (jQuery.type(error.response.errors) == "object") {
                                $('.form-message').html('');
                                $.each(error.response.errors, function(key, value) {
                                    $('.form-message').append('<div class="alert alert-danger">' + value + '</div>');
                                });
                            } else {
                                $('.form-message').html('<div class="alert alert-danger">' + error.response.errors + '</div>');
                            }

                            //change submit button
                            $('#submitButton').text('Next');
                        }
                    });

                    return false;
                }
            });

        });
    </script>
@endsection
