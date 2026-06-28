@extends('layouts.master')

@section('title')
    New Collection Account
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/companies-create.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">{{ $company->business_name }}</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('companies.lists') }}">Service Providers</a></li>
                        <li class="breadcrumb-item active">New Collection Account</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

    <div class="account-shell">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="form-message sp-form-message"></div>

                        <form action="#" id="companyForm" enctype="multipart/form-data" autocomplete="off">
                            <input type="hidden" name="partner_id" value="{{ $company->id }}">
                            <input type="hidden" name="partner_uuid" value="{{ $company->uuid }}">

                            <section>
                                <div class="sp-section">
                                    <div class="sp-section__title">Settlement Account Information</div>

                                    <div class="account-grid field-input">
                                        <div class="account-grid__col-3">
                                            <div class="mb-3">
                                                <label for="basicpill-namecard-input">Channel Type <span
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

                                                {{ Form::select('account_type_id', $options, old('account_type_id'), ['class' => 'form-control account_type_id', 'id' => 'account_type_id']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="account-grid__col-3">
                                            <div class="mb-3">
                                                <label class="bank_mno_name">Payment Channel <span
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

                                                {{ Form::select('account_id', $options, old('account_id'), ['class' => 'form-control account_id', 'id' => 'account_id']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="account-grid__col-6">
                                            <div class="mb-3">
                                                <label for="basicpill-namecard-input">Account Name<span
                                                        class="text-danger">*</span></label>
                                                {{ Form::text('account_name', old('account_name'), ['class="form-control"', 'placeholder="Write account name..."']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="account-grid__col-3">
                                            <div class="mb-3">
                                                <label for="basicpill-namecard-input">Account Number <span
                                                        class="text-danger">*</span></label>
                                                {{ Form::text('account_number', old('account_number'), ['class="form-control"', 'placeholder="Account number..."']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="account-grid__col-3">
                                            <div class="mb-3">
                                                <label>Customer Number <span class="text-danger">*</span></label>
                                                {{ Form::text('customer_code', old('corporate_limit'), ['class="form-control"', 'placeholder="Customer number..."']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="account-grid__col-4">
                                            <div class="mb-3">
                                                <label>Corporate Limit (<small>Withdraw amount in a day</small>) <span
                                                        class="text-danger">*</span></label>
                                                {{ Form::text('corporate_limit', old('corporate_limit'), ['class="form-control number-separator"', 'placeholder="Write corporate limit..."']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="account-grid__col-2">
                                            <div class="mb-3">
                                                <label class="currency_id">Currency <span
                                                        class="text-danger">*</span></label>
                                                @php $options = []; @endphp
                                                @foreach ($currencies as $val)
                                                    @php
                                                        $options[$val->id] = $val->name;
                                                    @endphp
                                                @endforeach
                                                @php
                                                    $currency_options = $options;
                                                    $options = ['' => 'Select'] + $options;
                                                @endphp

                                                {{ Form::select('currency_id', $options, old('currency_id'), ['class' => 'form-control currency_id', 'id' => 'currency_id']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="sp-actionbar">
                                        <div class="sp-actionbar__meta"></div>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('companies.show', $company->uuid ?? '') }}"
                                                class="btn btn-outline-danger btn-xs text-medium">Cancel</a>
                                            <button type="submit" id="submitButton" class="btn btn-primary">Save
                                                Account</button>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </form>
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
            // On change of account type
            $(document).on('change', '.account_type_id', function(e) {
                e.preventDefault();
                var accountTypeId = $(this).val();
                var fieldInputContainer = $(this).closest('.field-input');

                // Change bank or MNO name
                if (accountTypeId == 1) {
                    fieldInputContainer.find('.bank_mno_name').html(
                        'Payment Channel <span class="text-danger">*</span>');
                } else if (accountTypeId == 2) {
                    fieldInputContainer.find('.bank_mno_name').html(
                        'Payment Channel <span class="text-danger">*</span>');
                }

                // Query for data
                $.ajax({
                    url: '/account-types/' + accountTypeId,
                    type: "get",
                    success: function(data) {
                        fieldInputContainer.find('.account_id').html(data);
                    }
                });
            });

            // Validate form
            $("#companyForm").validate({
                errorClass: "formError",
                errorElement: "span",
                rules: {
                    "account_type_id": "required",
                    "account_id": "required",
                    "account_name": "required",
                    "account_number": "required",
                    "customer_code": "required",
                    "corporate_limit": "required",
                    "currency_id": "required",
                },
                messages: {
                    "account_type_id": "Channel type required",
                    "account_id": "Payment channel required",
                    "account_name": "Account name required",
                    "account_number": "Account number required",
                    "customer_code": "Customer number required",
                    "corporate_limit": "Corporate limit required",
                    "currency_id": "Currency required",
                },
                // Submit form
                submitHandler: function(form, e) {
                    e.preventDefault();
                    // Change submit button text
                    $('#submitButton').text('Saving...');
                    // Form data
                    const formData = new FormData(form);
                    // Append token
                    formData.append("_token", "{{ csrf_token() }}");
                    // Post data
                    $.ajax({
                        type: "POST",
                        url: "/companies/store-collection-account",
                        data: formData,
                        processData: false,
                        contentType: false,
                        cache: false,
                        timeout: 800000,
                        success: function(response) {
                            if (response['success'] == false) {
                                // Display failed message
                                $('.form-message').html(
                                    "<div class='alert alert-danger py-2.5 px-2'>" +
                                    response['message'] + "</div>"
                                );
                            } else if (response['success'] == true) {
                                // Display success message
                                $('.form-message').html(
                                    "<div class='alert alert-success py-2.5 px-2'>Service provider collection account submitted successfully.</div>"
                                );

                                // Set timer
                                setTimeout(function() {
                                    window.location.href = "/companies/show/" + response.uuid;
                                }, 300);
                            }
                        },
                        error: function(error) {
                            //display errors
                            if (error.responseJSON && jQuery.type(error.responseJSON
                                .errors) == "object") {
                                $('.form-message').html('');
                                $.each(error.responseJSON.errors, function(key, value) {
                                    $('.form-message').append(
                                        '<div class="alert alert-danger">' +
                                        value + '</div>');
                                });
                            } else {
                                $('.form-message').html('<div class="alert alert-danger">' +
                                    ((error.responseJSON && error.responseJSON
                                        .errors) || 'Failed to save collection account.'
                                        ) + '</div>');
                            }

                            //change submit button
                            $('#submitButton').text('Save Account');
                        }
                    });
                    return false;
                }
            });
        });
    </script>
@endsection
