@extends('layouts.master')

@section('title')
    Create Payment Mandate
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Payment Mandate
        @endslot
        @slot('title')
            Create Payment Mandate
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">

                    <div class="stepwizard mb-4">
                        <div class="stepwizard-row setup-panel">
                            <div class="stepwizard-step">
                                <a href="#step-1" type="button" class="btn btn-secondary btn-circle"
                                    disabled="disabled">1</a>
                                <span>Deduction Channel</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-default btn-circle" disabled="disabled">2</a>
                                <span>Customer Deduction Details</span>
                            </div>


                        </div>
                    </div>
                    <!--./form wizard -->

                    <form action="#" id="registrationForm" enctype="multipart/form-data" autocomplete="off">
                        <input type="hidden" name="uuid" value="{{ $customer_account->uuid ?? null }}">

                        <!-- Customer Information -->
                        <section>
                            <div class="row ">
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Payment Channel <span
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

                                        {{ Form::select('account_type_id', $options, old('account_type_id', $customer_account->account_type_id ?? ''), ['class' => 'form-control account_type_id','id' => 'account_type_id']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="bank_mno_name">Bank Name/MNO <span
                                                class="text-danger">*</span></label>
                                        @php $options = []; @endphp
                                        @foreach ($accounts as $val)
                                            @php
                                                $options[$val->id] = $val->name;
                                            @endphp
                                        @endforeach
                                        @php
                                            $options = ['' => 'Select'] + $options;
                                        @endphp

                                        {{ Form::select('account_id', $options, old('account_id', $customer_account->account_id ?? ''), ['class' => 'form-control account_id','id' => 'account_id']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label>Name of Account Holder <span class="text-danger">*</span></label>
                                        {{ Form::text('account_name', old('account_name', $customer_account->account_name ?? ''), ['class="form-control"','placeholder="Enter name of account holder..."','required']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Account Number <span
                                                class="text-danger">*</span></label>
                                        {{ Form::text('account_number', old('account_number', $customer_account->account_number ?? ''), ['class="form-control"','placeholder="Enter account number..."']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4 account_phone" style="display: block;">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Phone <span
                                                class="text-danger">*</span></label>
                                        {{ Form::text('phone', old('phone', $customer_account->phone ?? ''), ['class="form-control input-mask"','placeholder="Enter phone connected to the bank account..."','data-inputmask' => "'mask': '9999999999'"]) }}
                                        <span class="text-danger" id="phoneError"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label>NIDA Number</label>
                                        {{ Form::text('nida_number', old('nida_number', $customer_account->id_number ?? ''), ['class="form-control input-mask"','placeholder="Enter NIDA number..."','data-inputmask' => "'mask': '99999999-99999-99999-99'"]) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label>Email</label>
                                        {{ Form::email('email', old('email', $customer_account->email ?? ''), ['class="form-control input-mask"','placeholder="Enter email..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label>Address</label>
                                        {{ Form::text('address', old('address', $customer_account->address ?? ''), ['class="form-control input-mask"','placeholder="Enter address..."']) }}
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3 text-sm-end d-none d-sm-block">
                                        <a href="{{ route('payment-mandate.lists') }}"
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
            </div>
        </div>
    </div>
    <!-- end row -->
@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            //on select account category
            $('.account_type_id').on('change', function(e) {
                e.preventDefault();
                accountTypeId = $(this).val();

                //change bank or mno name
                if (accountTypeId == 1) {
                    $('.bank_mno_name').html('Bank Name <span class="text-danger">*</span>');
                    $('.account_phone').css('display', '');
                } else if (accountTypeId == 2) {
                    $('.bank_mno_name').html('MNO Name <span class="text-danger">*</span>');
                    $('.account_phone').css('display', 'none');
                }

                //query for data
                $.ajax({
                    url: '/account-types/' + accountTypeId,
                    type: "get",
                    success: function(data) {
                        $('.account_id').html(data);
                    }
                });
            });

            //validate form and submit
            $("#registrationForm").validate({
                rules: {
                    account_type_id: {
                        required: true
                    },
                    account_id: {
                        required: true
                    },
                    account_name: {
                        required: true
                    },
                    account_number: {
                        required: true
                    },
                    phone: {
                        required: true
                    },
                },
                messages: {
                    account_type_id: {
                        required: "Payment channel required"
                    },
                    account_id: {
                        required: "Bank/MNO name required"
                    },
                    account_name: {
                        required: "Account name required"
                    },
                    account_number: {
                        required: "Account number required"
                    },
                    phone: {
                        required: "Phone number required"
                    }
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
                        url: "{{ route('payment-mandate.store-account-details') }}",
                        data: data,
                        processData: false,
                        contentType: false,
                        cache: false,
                        timeout: 800000,
                        success: function(response) {
                            //activate submit button
                            $("#btnSubmit").prop("disabled", false);

                            // Deactivate Loading Spinner
                            $('.loading-spinner').toggleClass('active');

                            //uuid
                            uuid = response.uuid

                            //redirect to show
                            window.location.href = "/payment-mandate/deduction-details/" +
                                uuid
                        },
                        error: function(response) {
                            //activate submit button
                            $("#btnSubmit").prop("disabled", false);

                            // Deactivate Loading Spinner
                            $('.loading-spinner').toggleClass('active');

                            //write error message
                            $('#phoneError').text(response.responseJSON.errors.phone);
                        }
                    });
                    return false;
                },
            });
        });
    </script>
@endsection
