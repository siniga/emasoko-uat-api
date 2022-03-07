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
                                            <a href="#step-2" type="button" class="btn btn-secondary btn-circle"
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

                                <form action="#" id="registrationForm" enctype="multipart/form-data" autocomplete="off">
                                    <input type="hidden" name="partner_id" value="{{ $company->id }}">
                                    <input type="hidden" name="partner_uuid" value="{{ $company->uuid }}">

                                    <!-- Company Documents -->
                                    <section data-step="3">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <h5 class="text-capitalize">Company Collection Accounts</h5>
                                                <hr />
                                            </div>
                                            <!--./col -->
                                        </div>
                                        <!--./row -->

                                        @if ($company->partnerAccounts->isNotEmpty())
                                            @foreach ($company->partnerAccounts as $data)
                                                <div class="row non-field-wrapper">
                                                    <div class="col-lg-4">
                                                        <div class="mb-3">
                                                            <label>Account Category <span
                                                                    class="text-danger">*</span></label>
                                                            @php
                                                                $account_type_options = [];
                                                                $options = [];
                                                            @endphp
                                                            @foreach ($account_types as $val)
                                                                @php
                                                                    $options[$val->id] = $val->name;
                                                                @endphp
                                                            @endforeach
                                                            @php
                                                                $account_type_options = $options;
                                                                $options = ['' => 'Select'] + $options;
                                                            @endphp

                                                            {{ Form::select('account_type_id[]', $options, old('account_type_id[]', $data->account_type_id), ['class' => 'form-control account_type_id','id' => 'account_type_id','required']) }}
                                                            <span class="text-danger"></span>
                                                        </div>
                                                    </div>
                                                    <!--./col -->

                                                    <div class="col-lg-4">
                                                        <div class="mb-3">
                                                            <label>Bank/Institution <span
                                                                    class="text-danger">*</span></label>
                                                            @php $options = []; @endphp
                                                            @foreach ($accounts as $val)
                                                                @php
                                                                    $options[$val->id] = $val->name;
                                                                @endphp
                                                            @endforeach
                                                            @php
                                                                $account_options = $options;
                                                                $options = ['' => 'Select'] + $options;
                                                                
                                                            @endphp

                                                            {{ Form::select('account_id[]', $options, old('account_id[]', $data->account_id), ['class' => 'form-control account_id','id' => 'account_id','required']) }}
                                                            <span class="text-danger"></span>
                                                        </div>
                                                    </div>
                                                    <!--./col -->

                                                    <div class="col-lg-4">
                                                        <div class="mb-3">
                                                            <label>Account Name <span
                                                                    class="text-danger">*</span></label>
                                                            {{ Form::text('account_name[]', old('account_name[]', $data->account_name), ['class="form-control"','placeholder="Write account name..."','required']) }}
                                                            <span class="text-danger"></span>
                                                        </div>
                                                    </div>
                                                    <!--./col -->

                                                    <div class="col-lg-4">
                                                        <div class="mb-3">
                                                            <label>Account Number <span
                                                                    class="text-danger">*</span></label>
                                                            {{ Form::text('account_number[]', old('account_number[]', $data->account_number), ['class="form-control"','placeholder="Write account number..."','required']) }}
                                                            <span class="text-danger"></span>
                                                        </div>
                                                    </div>
                                                    <!--./col -->

                                                    <div class="col-lg-8">
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
                                                        <label for="basicpill-namecard-input">Account Category <span
                                                                class="text-danger">*</span></label>
                                                        @php
                                                            $account_type_options = [];
                                                            $options = [];
                                                        @endphp
                                                        @foreach ($account_types as $val)
                                                            @php
                                                                $options[$val->id] = $val->name;
                                                            @endphp
                                                        @endforeach
                                                        @php
                                                            $account_type_options = $options;
                                                            $options = ['' => 'Select'] + $options;
                                                        @endphp

                                                        {{ Form::select('account_type_id[]', $options, old('account_type_id[]'), ['class' => 'form-control account_type_id','id' => 'account_type_id','required']) }}
                                                        <span class="text-danger"></span>
                                                    </div>
                                                </div>
                                                <!--./col -->

                                                <div class="col-lg-4">
                                                    <div class="mb-3">
                                                        <label for="basicpill-namecard-input">Bank/Institution <span
                                                                class="text-danger">*</span></label>
                                                        @php $options = []; @endphp
                                                        @foreach ($accounts as $val)
                                                            @php
                                                                $options[$val->id] = $val->name;
                                                            @endphp
                                                        @endforeach
                                                        @php
                                                            $account_options = $options;
                                                            $options = ['' => 'Select'] + $options;
                                                            
                                                        @endphp

                                                        {{ Form::select('account_id[]', $options, old('account_id[]'), ['class' => 'form-control account_id','id' => 'account_id','required']) }}
                                                        <span class="text-danger"></span>
                                                    </div>
                                                </div>
                                                <!--./col -->

                                                <div class="col-lg-4">
                                                    <div class="mb-3">
                                                        <label for="basicpill-namecard-input">Account Name <span
                                                                class="text-danger">*</span></label>
                                                        {{ Form::text('account_name[]', old('account_name[]'), ['class="form-control"','placeholder="Write account name..."','required']) }}
                                                        <span class="text-danger"></span>
                                                    </div>
                                                </div>
                                                <!--./col -->

                                                <div class="col-lg-4">
                                                    <div class="mb-3">
                                                        <label for="basicpill-namecard-input">Account Number <span
                                                                class="text-danger">*</span></label>
                                                        {{ Form::text('account_number[]', old('account_number[]'), ['class="form-control"','placeholder="Write account number..."','required']) }}
                                                        <span class="text-danger"></span>
                                                    </div>
                                                </div>
                                                <!--./col -->

                                                <div class="col-lg-8">
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
                                        @endif

                                        <div class="field_wrapper"></div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3 text-sm-end d-none d-sm-block">
                                                    <a href="{{ url('company-documents/' . $company->uuid) }}"
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
                var account_types = {!! json_encode($account_type_options) !!}
                var accounts = {!! json_encode($account_options) !!}

                //add more accounts
                $(document).ready(function() {
                    let maxField = 5; //Input fields increment limitation
                    let wrapper = $('.field_wrapper'); //Input field wrapper
                    let fieldHTML =
                        '<div class="row row-div non-field-wrapper">' +
                        '<div class="col-lg-4">' +
                        '<div class="mb-3">' +
                        '<label>Account Category <span class="text-danger">*</span></label>';
                    fieldHTML += '<select name="account_type_id[]" class="form-control account_type_id">';
                    fieldHTML += "<option value=''>Select</option>";
                    $.each(account_types, function(key, value) {
                        fieldHTML += "<option value='" + value + "'>" + value + "</option>";
                    });
                    fieldHTML += '</select>';
                    fieldHTML += '</div></div>';

                    fieldHTML += '<div class="col-lg-4 col-md-4">' +
                        '<div class="mb-3">' +
                        '<label>Bank/Institution <span class="text-danger">*</span></label>';
                    fieldHTML += '<select name="account_id[]" class="form-control account_id">';
                    fieldHTML += "<option value=''>Select</option>";
                    $.each(accounts, function(key, value) {
                        fieldHTML += "<option value='" + value + "'>" + value + "</option>";
                    });
                    fieldHTML += '</select>';
                    fieldHTML += '</div></div>';

                    fieldHTML += '<div class="col-lg-4 col-md-4">' +
                        '<div class="mb-3">' +
                        '<label>Account Name <span class="text-danger">*</span></label>' +
                        '<input type="text" id="account_name" name="account_name[]" placeholder="Write account name..." class="form-control">' +
                        '</div></div>';

                    fieldHTML += '<div class="col-lg-4 col-md-4">' +
                        '<div class="mb-3">' +
                        '<label>Account Number <span class="text-danger">*</span></label>' +
                        '<input type="text" id="account_number" name="account_number[]" placeholder="Write account number..." class="form-control">' +
                        '</div></div>';

                    fieldHTML += '<div class="col-lg-8 col-md-8">' +
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
                        "account_type_id[]": "required",
                        "account_id[]": "required",
                        "account_name[]": "required",
                        "account_number[]": "required",
                    },
                    messages: {
                        "account_type_id[]": "Account category required",
                        "account_id[]": "Account type required",
                        "account_name[]": "Account name required",
                        "account_number[]": "Account number required",
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
                            url: "{{ url('store-company-accounts') }}",
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
                                window.location.href = "/company-staff/" + data.uuid
                            },
                            error: function(e) {
                                $("#btnSubmit").prop("disabled", false);
                            }
                        });
                        return false;
                    },
                });


                //on select account category
                $('.account_type_id').on('change', function(e) {
                    e.preventDefault();
                    accountTypeId = $(this).val();

                    //query for data
                    $.ajax({
                        url: '/account-types/' + accountTypeId,
                        type: "get",
                        success: function(data) {
                            $('.account_id').html(data);
                        }
                    });
                });
            });
        </script>
    @endsection
