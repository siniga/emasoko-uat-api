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
                                <a href="#step-2" type="button" class="btn btn-secondary btn-circle"
                                    disabled="disabled">3</a>
                                <span class="text-primary">Collection Accounts</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-default btn-circle"
                                    disabled="disabled">4</a>
                                <span>Authorized Representative</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-5" type="button" class="btn btn-default btn-circle"
                                    disabled="disabled">5</a>
                                <span>Credentials</span>
                            </div>
                        </div>
                    </div>

                    <!--form message => success or danger -->
                    <div class="form-message"></div>

                    <form action="#" id="companyForm" enctype="multipart/form-data" autocomplete="off">
                        <input type="hidden" name="partner_id" value="{{ $company->id }}">
                        <input type="hidden" name="partner_uuid" value="{{ $company->uuid }}">

                        <!-- Company Documents -->
                        <section data-step="3">
                            <div class="row">
                                <div class="col-lg-12">
                                    <h5 class="text-capitalize">Company Collection Accounts</h5>
                                </div>
                            </div>

                            @if ($company->partnerAccounts->isNotEmpty())
                                @foreach ($company->partnerAccounts as $data)
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label>Account Type <span class="text-danger">*</span></label>
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

                                                {{ Form::select('account_type_id[]', $options, old('account_type_id[]', $data->account_type_id), ['class' => 'form-control account_type_id', 'id' => 'account_type_id']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label class="bank_mno_name">Bank Name <span
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

                                                {{ Form::select('account_id[]', $options, old('account_id[]', $data->account_id), ['class' => 'form-control account_id', 'id' => 'account_id']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label>Account Name <span class="text-danger">*</span></label>
                                                {{ Form::text('account_name[]', old('account_name[]', $data->account_name), ['class="form-control"', 'placeholder="Write account name..."']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label>Account Number <span class="text-danger">*</span></label>
                                                {{ Form::text('account_number[]', old('account_number[]', $data->account_number), ['class="form-control"', 'placeholder="Write account number..."']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="col-lg-8">
                                            <div class="mt-4 pt-2 text-sm-end d-none d-sm-block">
                                                <button type="button" name="add" id="add_button"
                                                    class="btn btn-info btn-sm text-medium">
                                                    <i class="fa fa-plus"></i> Add another account
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="basicpill-namecard-input">Account Type <span
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

                                            {{ Form::select('account_type_id[]', $options, old('account_type_id[]'), ['class' => 'form-control account_type_id', 'id' => 'account_type_id']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="bank_mno_name">Bank Name <span
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

                                            {{ Form::select('account_id[]', $options, old('account_id[]'), ['class' => 'form-control account_id', 'id' => 'account_id']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="basicpill-namecard-input">Account Name <span
                                                    class="text-danger">*</span></label>
                                            {{ Form::text('account_name[]', old('account_name[]'), ['class="form-control"', 'placeholder="Write account name..."']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="basicpill-namecard-input">Account Number <span
                                                    class="text-danger">*</span></label>
                                            {{ Form::text('account_number[]', old('account_number[]'), ['class="form-control"', 'placeholder="Write account number..."']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-8">
                                        <div class="mt-4 pt-2 text-sm-end d-none d-sm-block">
                                            <button type="button" name="add" id="add_button"
                                                class="btn btn-info btn-sm text-medium">
                                                <i class="fa fa-plus"></i> Add another account
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="field_wrapper"></div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3 text-sm-end d-none d-sm-block">
                                        <a href="{{ route('companies.register-documents', $company->uuid) }}"
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
    <!-- end row -->
@endsection

@section('script')
    <!--jquery validation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            //on select account category
            $('.account_type_id').on('change', function(e) {
                e.preventDefault();
                accountTypeId = $(this).val();

                //change bank or mno name
                if (accountTypeId == 1)
                    $('.bank_mno_name').html('Bank Name <span class="text-danger">*</span>');
                else if (accountTypeId == 2)
                    $('.bank_mno_name').html('MNO Name <span class="text-danger">*</span>');

                //query for data
                $.ajax({
                    url: '/account-types/' + accountTypeId,
                    type: "get",
                    success: function(data) {
                        $('.account_id').html(data);
                    }
                });
            });

            //data
            var account_types = {!! json_encode($account_type_options) !!}
            var accounts = {!! json_encode($account_options) !!}

            //add more accounts
            // $(document).ready(function() {
            let maxField = 5; //Input fields increment limitation
            let wrapper = $('.field_wrapper'); //Input field wrapper
            let fieldHTML =
                '<div class="row row-div">' +
                '<div class="col-lg-4">' +
                '<div class="mb-3">' +
                '<label>Account Type <span class="text-danger">*</span></label>';
            fieldHTML += '<select name="account_type_id[]" class="form-control account_type_id">';
            fieldHTML += "<option value=''>Select</option>";
            $.each(account_types, function(key, value) {
                fieldHTML += "<option value='" + key + "'>" + value + "</option>";
            });
            fieldHTML += '</select>';
            fieldHTML += '<span class="formError"></span>';
            fieldHTML += '</div></div>';

            fieldHTML += '<div class="col-lg-4 col-md-4">' +
                '<div class="mb-3">' +
                '<label class="bank_mno_name">Bank Name <span class="text-danger">*</span></label>';
            fieldHTML += '<select name="account_id[]" class="form-control account_id">';
            fieldHTML += "<option value=''>Select</option>";
            $.each(accounts, function(key, value) {
                fieldHTML += "<option value='" + key + "'>" + value + "</option>";
            });
            fieldHTML += '</select>';
            fieldHTML += '<span class="formError"></span>';
            fieldHTML += '</div></div>';

            fieldHTML += '<div class="col-lg-4 col-md-4">' +
                '<div class="mb-3">' +
                '<label>Account Name <span class="text-danger">*</span></label>' +
                '<input type="text" id="account_name" name="account_name[]" placeholder="Write account name..." class="form-control">';
            fieldHTML += '<span class="formError"></span>';
            fieldHTML += '</div></div>';

            fieldHTML += '<div class="col-lg-4 col-md-4">' +
                '<div class="mb-3">' +
                '<label>Account Number <span class="text-danger">*</span></label>' +
                '<input type="text" id="account_number" name="account_number[]" placeholder="Write account number..." class="form-control">';
            fieldHTML += '<span class="formError"></span>';
            fieldHTML += '</div></div>';

            fieldHTML += '<div class="col-lg-8 col-md-8">' +
                '<div class="mt-4 pt-2 text-sm-end d-none d-sm-block">' +
                'Do you want to delete this account? <input type="checkbox" id="remove_button" />' +
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
            // });

            //wrapper on select account type
            $(wrapper).on('change', '.account_type_id', function(e) {
                e.preventDefault();
                accountTypeId = $(this).val();

                //change bank or mno name
                if (accountTypeId == 1)
                    $('.field_wrapper .bank_mno_name').html('Bank Name <span class="text-danger">*</span>');
                else if (accountTypeId == 2)
                    $('.field_wrapper .bank_mno_name').html('MNO Name <span class="text-danger">*</span>');

                //query for data
                $.ajax({
                    url: '/account-types/' + accountTypeId,
                    type: "get",
                    success: function(data) {
                        $('.field_wrapper .account_id').html(data);
                    }
                });
            });

            //declare form message 
            $('.form-message').html("");

            //validate form
            $("#companyForm").validate({
                errorClass: "formError",
                errorElement: "span",
                rules: {
                    "account_type_id[]": "required",
                    "account_id[]": "required",
                    "account_name[]": "required",
                    "account_number[]": "required",
                },
                messages: {
                    "account_type_id[]": "Account type required",
                    "account_id[]": "Bank/MNO name required",
                    "account_name[]": "Account name required",
                    "account_number[]": "Account number required",
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
                        url: "/companies/store-accounts",
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
                                "<div class='alert alert-success py-2.5 px-2'>Service Provider collection account(s) saved</div>"
                            );

                            //set timer
                            setTimeout(function() {
                                window.location.href = "/companies/staff/" + response.uuid;
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
