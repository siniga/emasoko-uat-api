@extends(($selfRegistration ?? false) ? 'layouts.self-registration' : 'layouts.master')

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

                @unless($selfRegistration ?? false)
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('companies.lists') }}">Service Providers</a></li>
                        <li class="breadcrumb-item active">Register New</li>
                    </ol>
                </div>
                @endunless

            </div>
        </div>
    </div>

    @include('companies.create.partials.hero', [
        'currentStep' => 3,
        'heading' => 'Configure collection accounts',
        'subtitle' => 'Set up the bank or MNO accounts used to receive collections, including customer code, corporate limit, and currency.',
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

                        <!-- Company Documents -->
                        <section data-step="3">
                            <div class="sp-section">
                                <div class="sp-section__title">Collection Account Setup</div>

                            @if ($company->partnerAccounts->isNotEmpty())
                                @foreach ($company->partnerAccounts as $data)
                                    <div class="row field-input">
                                        <div class="col-lg-2">
                                            <div class="mb-3">
                                                <label>Channel Type <span class="text-danger">*</span></label>
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
                                                    $options = ['' => '-- Select --'] + $options;
                                                @endphp

                                                {{ Form::select('account_type_id[]', $options, old('account_type_id[]', $data->account_type_id), ['class' => 'form-control account_type_id', 'id' => 'account_type_id']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="col-lg-2">
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
                                                    $options = ['' => '-- Select --'] + $options;

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

                                        <div class="col-lg-2">
                                            <div class="mb-3">
                                                <label>Account Number <span class="text-danger">*</span></label>
                                                {{ Form::text('account_number[]', old('account_number[]', $data->account_number), ['class="form-control"', 'placeholder="Account number..."']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="col-lg-2">
                                            <div class="mb-3">
                                                <label>Customer Number <span class="text-danger">*</span></label>
                                                {{ Form::text('customer_code[]', old('customer_code[]', $data->customer_code), ['class="form-control"', 'placeholder="Customer number..."']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label>Corporate Limit (<small>Withdraw amount in a day</small>) <span class="text-danger">*</span></label>
                                                {{ Form::text('corporate_limit[]', old('corporate_limit[]', $data->corporate_limit), ['class="form-control number-separator"', 'placeholder="Write corporate limit..."']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="col-lg-2">
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
                                                    $options = ['' => '-- Select --'] + $options;
                                                @endphp

                                                {{ Form::select('currency_id[]', $options, old('currency_id[]', $data->currency_id), ['class' => 'form-control currency_id', 'id' => 'currency_id']) }}
                                                <span class="formError"></span>
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
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
                                <div class="row field-input">
                                    <div class="col-lg-2">
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
                                                $options = ['' => '-- Select --'] + $options;
                                            @endphp

                                            {{ Form::select('account_type_id[]', $options, old('account_type_id[]'), ['class' => 'form-control account_type_id', 'id' => 'account_type_id']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-2">
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
                                                $options = ['' => '-- Select --'] + $options;
                                            @endphp

                                            {{ Form::select('account_id[]', $options, old('account_id[]'), ['class' => 'form-control account_id', 'id' => 'account_id']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="basicpill-namecard-input">Account Name<span
                                                    class="text-danger">*</span></label>
                                            {{ Form::text('account_name[]', old('account_name[]'), ['class="form-control"', 'placeholder="Write account name..."']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-2">
                                        <div class="mb-3">
                                            <label for="basicpill-namecard-input">Account Number <span
                                                    class="text-danger">*</span></label>
                                            {{ Form::text('account_number[]', old('account_number[]'), ['class="form-control"', 'placeholder="Account number..."']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-2">
                                        <div class="mb-3">
                                            <label>Customer Number <span class="text-danger">*</span></label>
                                            {{ Form::text('customer_code[]', old('corporate_limit[]'), ['class="form-control"', 'placeholder="Customer number..."']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label>Corporate Limit (<small>Withdraw amount in a day</small>) <span class="text-danger">*</span></label>
                                            {{ Form::text('corporate_limit[]', old('corporate_limit[]'), ['class="form-control number-separator"', 'placeholder="Write corporate limit..."']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-2">
                                        <div class="mb-3">
                                            <label class="currency_id">Currency <span class="text-danger">*</span></label>
                                            @php $options = []; @endphp
                                            @foreach ($currencies as $val)
                                                @php
                                                    $options[$val->id] = $val->name;
                                                @endphp
                                            @endforeach
                                            @php
                                                $currency_options = $options;
                                                $options = ['' => '-- Select --'] + $options;
                                            @endphp

                                            {{ Form::select('currency_id[]', $options, old('currency_id[]'), ['class' => 'form-control currency_id', 'id' => 'currency_id']) }}
                                            <span class="formError"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
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

                            <div class="sp-actionbar">
                                <div class="sp-actionbar__meta"></div>
                                <div class="d-flex gap-2">
                                        <a href="{{ route('companies.register-documents', $company->uuid) }}"
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

            // Data initialization
            var account_types = {!! json_encode($account_type_options) !!};
            var accounts = {!! json_encode($account_options) !!};
            var currencies = {!! json_encode($currency_options) !!};

            // Add more accounts
            let maxField = 5; // Input fields increment limitation
            let wrapper = $('.field_wrapper'); // Input field wrapper
            let fieldHTML = `
            <div class="row field-input row-div border-top border-primary py-4">
                <div class="col-lg-2">
                    <div class="mb-3">
                        <label>Channel Type <span class="text-danger">*</span></label>
                        <select name="account_type_id[]" class="form-control account_type_id">
                            <option value=''>-- Select --</option>
                            ${Object.keys(account_types).map(key => `<option value='${key}'>${account_types[key]}</option>`).join('')}
                        </select>
                        <span class="formError"></span>
                    </div>
                </div>

                <div class="col-lg-2">
                    <div class="mb-3">
                        <label class="bank_mno_name">Payment Channel <span class="text-danger">*</span></label>
                        <select name="account_id[]" class="form-control account_id">
                            <option value=''>-- Select --</option>
                            ${Object.keys(accounts).map(key => `<option value='${key}'>${accounts[key]}</option>`).join('')}
                        </select>
                        <span class="formError"></span>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label>Account Name <span class="text-danger">*</span></label>
                        <input type="text" name="account_name[]" placeholder="Write account name..." class="form-control">
                        <span class="formError"></span>
                    </div>
                </div>

                <div class="col-lg-2">
                    <div class="mb-3">
                        <label>Account Number <span class="text-danger">*</span></label>
                        <input type="text" name="account_number[]" placeholder="Account number..." class="form-control">
                        <span class="formError"></span>
                    </div>
                </div>

                <div class="col-lg-2">
                    <div class="mb-3">
                        <label>Customer Number <span class="text-danger">*</span></label>
                        <input type="text" name="customer_code[]" placeholder="Customer number..." class="form-control">
                        <span class="formError"></span>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label>Corporate Limit (<small>Withdraw amount in a day</small>) <span class="text-danger">*</span></label>
                        <input type="text" name="corporate_limit[]" placeholder="Write corporate limit..." class="form-control number-separator">
                        <span class="formError"></span>
                    </div>
                </div>

                <div class="col-lg-2">
                    <div class="mb-3">
                        <label class="currency_id">Currency <span class="text-danger">*</span></label>
                        <select name="currency_id[]" class="form-control currency_id">
                            <option value=''>-- Select --</option>
                            ${Object.keys(currencies).map(key => `<option value='${key}'>${currencies[key]}</option>`).join('')}
                        </select>
                        <span class="formError"></span>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="mt-4 pt-2 text-sm-end d-none d-sm-block">
                        Do you want to delete this account? <input type="checkbox" class="remove_button" />
                    </div>
                </div>
            </div>`;

            let x = 1; // Initial field counter is 1
            // Once add button is clicked
            $('#add_button').click(function() {
                // Check maximum number of input fields
                if (x < maxField) {
                    x++; // Increment field counter
                    $(wrapper).append(fieldHTML); // Add field html
                }
            });

            // Once remove button is clicked
            $(wrapper).on('click', '.remove_button', function(e) {
                e.preventDefault();
                $(this).closest('.row-div').remove();
                x--; // Decrement field counter
            });

            // Validate form
            $("#companyForm").validate({
                errorClass: "formError",
                errorElement: "span",
                rules: {
                    "account_type_id[]": "required",
                    "account_id[]": "required",
                    "account_name[]": "required",
                    "account_number[]": "required",
                    "customer_code[]": "required",
                    "corporate_limit[]": "required",
                    "currency_id[]": "required",
                },
                messages: {
                    "account_type_id[]": "Channel type required",
                    "account_id[]": "Payment channel required",
                    "account_name[]": "Account name required",
                    "account_number[]": "Account number required",
                    "customer_code[]": "Customer number required",
                    "corporate_limit[]": "Corporate limit required",
                    "currency_id[]": "Currency required",
                },
                // Submit form
                submitHandler: function(form, e) {
                    e.preventDefault();
                    // Change submit button text
                    $('#submitButton').text('Submitting...');
                    // Form data
                    const formData = new FormData(form);
                    // Append token
                    formData.append("_token", "{{ csrf_token() }}");
                    // Post data
                    $.ajax({
                        type: "POST",
                        url: @json(route('companies.store-accounts')),
                        data: formData,
                        processData: false,
                        contentType: false,
                        cache: false,
                        timeout: 800000,
                        success: function(response) {
                            // Change submit button text
                            $('#submitButton').text('Next');

                            // Display message
                            $('.form-message').html(
                                "<div class='alert alert-success py-2.5 px-2'>Service provider collection account(s) saved</div>"
                            );

                            // Set timer
                            setTimeout(function() {
                                window.location.href = @json(url('/companies/staff')) + "/" + response.uuid;
                            }, 300);
                        },
                        error: function(error) {
                            //display errors
                            if (jQuery.type(error.responseJson.errors) == "object") {
                                $('.form-message').html('');
                                $.each(error.responseJson.errors, function(key, value) {
                                    $('.form-message').append('<div class="alert alert-danger">' + value + '</div>');
                                });
                            } else {
                                $('.form-message').html('<div class="alert alert-danger">' + error.responseJson.errors + '</div>');
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
