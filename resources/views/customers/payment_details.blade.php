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
                                <a href="#step-1" type="button" class="btn btn-success btn-circle">1</a>
                                <span class="text-primary">Customer Information</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-secondary btn-circle" disabled="disabled">2</a>
                                <span>Deduction Channel</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-default btn-circle" disabled="disabled">3</a>
                                <span>Customer Deduction Details</span>
                            </div>


                        </div>
                    </div>
                    <!--./form wizard -->

                    <form action="#" id="registrationForm" enctype="multipart/form-data" autocomplete="off">
                        <input type="hidden" name="customer_id" value="{{ $customer->id ?? null }}">
                        <input type="hidden" name="customer_uuid" value="{{ $customer->uuid ?? null }}">

                        <!-- Customer Information -->
                        <section>
                            <div class="row mb-4">
                                <div class="col-lg-12">
                                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                                        <b>Note: </b> 
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            
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

                                        {{ Form::select('account_type_id', $options, old('account_type_id', $customer->customerAccount->account_type_id ?? ''), ['class' => 'form-control','id' => 'account_type_id']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Bank/Institution <span
                                                class="text-danger">*</span></label>
                                        @php $options = []; @endphp
                                        {{-- @foreach ($accounts as $val)
                                            @php
                                                $options[$val->id] = $val->name;
                                            @endphp
                                        @endforeach --}}
                                        @php
                                            $options = ['' => 'Select'] + $options;       
                                        @endphp

                                        {{ Form::select('account_id', $options, old('account_id', $customer->customerAccount->account_id ?? ''), ['class' => 'form-control', 'id' => 'account_id']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label>Account Name <span
                                                class="text-danger">*</span></label>
                                        {{ Form::text('account_name', old('account_name', $customer->customerAccount->account_name ?? ''), ['class="form-control"','placeholder="Write account name..."','required']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Account Number <span
                                                class="text-danger">*</span></label>
                                        {{ Form::text('account_number', old('account_number', $customer->customerAccount->account_number ?? ''), ['class="form-control"','placeholder="Write account number..."']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4" id="account_phone" style="display: block;">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Phone <small>(Linked to Bank Account)</small><span
                                            class="text-danger">*</span></label>
                                        {{ Form::text('phone', old('phone', $customer->customerAccount->phone ?? ''), ['class="form-control input-mask"','placeholder="Write phone..."','data-inputmask' => "'mask': '9999999999'"]) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3 text-sm-end d-none d-sm-block">
                                        <a href="{{ url('customers/register/' . $customer->uuid) }}"
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
                        required: "Bank/Institution required"
                    },
                    account_name: {
                        required: "Account name required"
                    },
                    account_number: {
                        required: "Account number required"
                    },
                    phone: {
                        required: "Phone required"
                    },
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
                        url: "{{ route('customers.store-payment-details') }}",
                        data: data,
                        processData: false,
                        contentType: false,
                        cache: false,
                        timeout: 800000,
                        success: function(data) {
                            $("#btnSubmit").prop("disabled", false);
                            //$(this)[0].reset();

                            // Deactivate Loading Spinner
                            $('.loading-spinner').toggleClass('active');

                            //redirect to show
                            window.location.href = "/customers/deduction-details/" + data.uuid
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

            //filter
            $('#account_type_id').on('change', function(e) {
                accountTypeId = $(this).val();

                if (accountTypeId == 1)
                    $('#account_phone').css('display', '');
                else
                    $('#account_phone').css('display', 'none');
            });

            //format amount 
            document.getElementById('number_comma').addEventListener('input', event =>
                event.target.value = (parseInt(event.target.value.replace(/[^\d]+/gi, '')) || 0).toLocaleString(
                    'en-US')
            );

            //format amount 
            document.getElementById('number_comma2').addEventListener('input', event =>
                event.target.value = (parseInt(event.target.value.replace(/[^\d]+/gi, '')) || 0).toLocaleString(
                    'en-US')
            );

        });
    </script>
@endsection
