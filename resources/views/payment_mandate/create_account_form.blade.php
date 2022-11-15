@extends('layouts.master')

@section('title')
    Create Payment Mandate
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
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
                                <a href="#step-1" type="button" class="btn btn-secondary btn-circle" disabled="disabled">1</a>
                                <span>Deduction Channel</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-default btn-circle"
                                    disabled="disabled">2</a>
                                <span>Customer Deduction Details</span>
                            </div>
                        </div>
                    </div>
                    <!--./form wizard -->

                    <div class="col-md-12 mb-3">
                        <div id="error-messages" style="display: none;"></div>
                    </div>

                    <form action="#" id="Loan-Form" enctype="multipart/form-data" autocomplete="off">
                        <input type="hidden" name="id" value="{{ $customer_account->uuid ?? null }}">
                        <input type="hidden" name="do_id" value="{{ $debit_order->id ?? null }}">

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

                                        {{ Form::select('account_type_id', $options, old('account_type_id', $customer_account->account_type_id ?? ''), ['class' => 'form-control account_type_id', 'id' => 'account_type_id']) }}
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

                                        {{ Form::select('account_id', $options, old('account_id', $customer_account->account_id ?? ''), ['class' => 'form-control account_id', 'id' => 'account_id']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4" id="account_name" style="display: block;">
                                    <div class="mb-3">
                                        <label>Name of Account Holder <span class="text-danger">*</span></label>
                                        {{ Form::text('account_name', old('account_name', $customer_account->account_name ?? ''), ['class="form-control"', 'placeholder="Enter name of account holder..."', 'id="account_name"']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4" id="account_number" style="display: block;">
                                    <div class="mb-3">
                                        <label>Bank Account Number <span class="text-danger">*</span></label>
                                        {{ Form::text('account_number', old('account_number', $customer_account->account_number ?? ''), ['class="form-control"', 'placeholder="Enter account number..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4" id="account_number_phone" style="display: none;">
                                    <div class="mb-3">
                                        <label>MNO Phone <span class="text-danger">*</span></label>
                                        {{ Form::text('account_number_phone', old('account_number_phone', $customer_account ? reverse_casting_phone($customer_account->phone) : ''), ['class="form-control"', 'placeholder="Enter phone number..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4" id="account_phone" style="display: block;">
                                    <div class="mb-3">
                                        <label>Phone <span class="text-danger">*</span></label>
                                        {{ Form::text('phone', old('phone', $customer_account ? reverse_casting_phone($customer_account->phone) : ''), ['class="form-control"', 'placeholder="Enter phone connected to the bank account..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label>NIDA Number</label>
                                        {{ Form::text('nida_number', old('nida_number', $customer_account->customer->id_number ?? ''), ['class="form-control input-mask"', 'placeholder="Enter NIDA number..."', 'data-inputmask' => "'mask': '99999999-99999-99999-99'"]) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label>Email</label>
                                        {{ Form::email('email', old('email', $customer_account->customer->email ?? ''), ['class="form-control input-mask"', 'placeholder="Enter email..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label>Address</label>
                                        {{ Form::text('address', old('address', $customer_account->customer->address ?? ''), ['class="form-control input-mask"', 'placeholder="Enter address..."']) }}
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3 text-sm-end d-none d-sm-block">
                                        <a href="{{ route('payment-mandate.lists') }}"
                                            class="btn btn-danger text-medium">Cancel</a>

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
            //before on change
            var accountTypeId = $(".account_type_id").val();

            if (accountTypeId == 1 || accountTypeId == 2) {
                //change bank or mno name
                if (accountTypeId == 1) {
                    $('.bank_mno_name').html('Bank Name <span class="text-danger">*</span>');

                    //hide or show
                    $('#account_name').css('display', '')
                    $('#account_number').css('display', '')
                    $('#account_number_phone').css('display', 'none')
                    $('#account_phone').css('display', '')
                } else if (accountTypeId == 2) {
                    $('.bank_mno_name').html('MNO Name <span class="text-danger">*</span>');

                    //hide or show
                    $('#account_name').css('display', 'none')
                    $('#account_number').css('display', 'none')
                    $('#account_number_phone').css('display', '')
                    $('#account_phone').css('display', 'none')
                }
            }

            //on select account account type id
            $('.account_type_id').on('change', function(e) {
                e.preventDefault();
                accountTypeId = $(this).val();

                //change bank or mno name
                if (accountTypeId == 1) {
                    $('.bank_mno_name').html('Bank Name <span class="text-danger">*</span>');

                    //hide or show
                    $('#account_name').css('display', '')
                    $('#account_number').css('display', '')
                    $('#account_number_phone').css('display', 'none')
                    $('#account_phone').css('display', '')
                } else if (accountTypeId == 2) {
                    $('.bank_mno_name').html('MNO Name <span class="text-danger">*</span>');

                    //hide or show
                    $('#account_name').css('display', 'none')
                    $('#account_number').css('display', 'none')
                    $('#account_number_phone').css('display', '')
                    $('#account_phone').css('display', 'none')
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
            $("#Loan-Form").validate({
                errorLabelContainer: ".form-error",

                //rules
                rules: {
                    account_type_id: {
                        required: true
                    },
                    account_id: {
                        required: true
                    },
                    account_name: {
                        required: function() {
                            if ($("select[name=account_type_id]").val() == 1 || $(
                                    "select[name=account_type_id]").val() == "") {
                                return true;
                            } else {
                                return false;
                            }
                        }
                    },
                    account_number: {
                        required: function() {
                            if ($("select[name=account_type_id]").val() == 1 || $(
                                    "select[name=account_type_id]").val() == "") {
                                return true;
                            } else {
                                return false;
                            }
                        }
                    },
                    phone: {
                        required: function() {
                            if ($("select[name=account_type_id]").val() == 1 || $(
                                    "select[name=account_type_id]").val() == "") {
                                return true;
                            } else {
                                return false;
                            }
                        }
                    },

                    account_number_phone: {
                        required: function() {
                            if ($("select[name=account_type_id]").val() == 2) {
                                return true;
                            } else {
                                return false;
                            }
                        }
                    }
                },

                //message
                messages: {
                    account_type_id: {
                        required: "Payment channel required"
                    },
                    account_id: {
                        required: "Bank/MNO name required"
                    },
                    account_name: {
                        required: "Name of account holder required"
                    },
                    account_number: {
                        required: "Account number required"
                    },
                    phone: {
                        required: "Phone connected to bank account required"
                    },
                    account_number_phone: {
                        required: "MNO Phone number is required"
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
                        url: "{{ route('payment-mandate.store-account') }}",
                        data: data,
                        processData: false,
                        contentType: false,
                        cache: false,
                        timeout: 800000,
                        success: function(response) {
                            //activate submit button
                            $("#btnSubmit").prop("disabled", false);

                            // deactivate Loading Spinner
                            $('.loading-spinner').toggleClass('active');

                            //hide error msg
                            $("#error-messages").css('display', 'none');

                            $.notify(response.success_msg, 'success');

                            setTimeout(function() {
                                //customer account id
                                id = response.id

                                //debit order id
                                do_id = ''
                                if (response.do_id != null)
                                    do_id = response.do_id

                                //redirect to show
                                window.location.href =
                                    "/payment-mandate/create-deduction-info/" + id +
                                    "/" + do_id
                            }, 1000);
                        },
                        error: function(response) {
                            //activate submit button
                            $("#btnSubmit").prop("disabled", false);

                            // Deactivate Loading Spinner
                            $('.loading-spinner').toggleClass('active');

                            //show error msg
                            $("#error-messages").css('display', '');

                            $.each(response.responseJSON.errors, function(key, item) {
                                $("#error-messages").append(
                                    '<span>' + item +
                                    '</span><br />');
                            });
                        }
                    });
                    return false;
                },
            });
        });
    </script>
@endsection
