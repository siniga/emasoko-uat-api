@extends('layouts.master')

@section('title')
    Update Deduction Channel
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Payment Mandate
        @endslot
        @slot('title')
            Update Payment Mandate
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="#" id="registrationForm" enctype="multipart/form-data" autocomplete="off">
                        <input type="hidden" name="uuid" value="{{ $debit_order->customerAccount->uuid ?? null }}">

                        <!-- Customer Information -->
                        <section>
                            <div class="row">
                                <div class="col-lg-12">
                                    <h5 class="text-capitalize">Deduction Channel</h5>
                                    <hr />
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

                                        {{ Form::select('account_type_id',$options,old('account_type_id', $debit_order->customerAccount->account_type_id ?? ''),['class' => 'form-control account_type_id', 'id' => 'account_type_id']) }}
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

                                        {{ Form::select('account_id', $options, old('account_id', $debit_order->customerAccount->account_id ?? ''), ['class' => 'form-control account_id','id' => 'account_id']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label>Name of Account Holder <span class="text-danger">*</span></label>
                                        {{ Form::text('account_name', old('account_name', $debit_order->customerAccount->account_name ?? ''), ['class="form-control"','placeholder="Enter name of account holder..."','required']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Account Number <span
                                                class="text-danger">*</span></label>
                                        {{ Form::text('account_number', old('account_number', $debit_order->customerAccount->account_number ?? ''), ['class="form-control"','placeholder="Enter account number..."']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4 account_phone" style="display: block;">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Phone <span
                                                class="text-danger">*</span></label>
                                        {{ Form::text('phone', old('phone', $debit_order->customerAccount->phone ?? ''), ['class="form-control"','placeholder="Enter phone connected to the bank account..."']) }}
                                        <span class="text-danger" id="phoneError"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label>NIDA Number</label>
                                        {{ Form::text('nida_number', old('nida_number', $debit_order->customerAccount->id_number ?? ''), ['class="form-control input-mask"','placeholder="Enter NIDA number..."','data-inputmask' => "'mask': '99999999-99999-99999-99'"]) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label>Email</label>
                                        {{ Form::email('email', old('email', $debit_order->customerAccount->email ?? ''), ['class="form-control input-mask"','placeholder="Enter email..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label>Address</label>
                                        {{ Form::text('address', old('address', $debit_order->customerAccount->address ?? ''), ['class="form-control input-mask"','placeholder="Enter address..."']) }}
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row mt-4">
                                <div class="col-lg-12">
                                    <h5 class="text-capitalize">Customer Deduction Details</h5>
                                    <hr />
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row ">
                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Loan Amount <span class="text-danger">*</span></label>
                                        {{ Form::text('loan_amount',old('loan_amount', $debit_order ? number_format($debit_order->total_amount) : ''),['class="form-control number-separator"', 'placeholder="Write total loan amount..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Debit Type <span class="text-danger">*</span></label>
                                        @php
                                            $options = [
                                                'FIXED' => 'Fixed',
                                                'DYNAMIC' => 'Dynamic',
                                            ];
                                            $options = ['' => 'Select'] + $options;
                                        @endphp

                                        {{ Form::select('debit_type', $options, old('debit_type', $debit_order->debit_type ?? ''), ['class' => 'form-control','id' => 'debit_type']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3" id="standard_deduction" style="display: block">
                                    <div class="mb-3">
                                        <label>Deduction Amount <span class="text-danger">*</span></label>
                                        {{ Form::text('installment_amount',old('installment_amount', $debit_order ? number_format($debit_order->installment_amount) : ''),['class="form-control number-separator"', 'placeholder="Write deduction amount..."']) }}
                                        <span class="text-danger" id="installmentAmountError"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-6" id="max_min_deduction" style="display: none">
                                    <div class="mb-3">
                                        <label>Deduction Amount <span class="text-danger">*</span></label>
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    {{ Form::text('min_installment_amount',old('min_installment_amount', $debit_order ? number_format($debit_order->min_installment_amount) : ''),['class="form-control number-separator"','placeholder="Minimum deduction amount..."','id="min_installment_amount"']) }}
                                                    <span class="text-danger" id="minInstallmentAmountError"></span>
                                                </div>
                                            </div>
                                            <!--./col -->

                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    {{ Form::text('max_installment_amount',old('max_installment_amount', $debit_order ? number_format($debit_order->max_installment_amount) : ''),['class="form-control number-separator"','placeholder="Maximum deduction amount..."','id="max_installment_amount"']) }}
                                                    <span class="text-danger" id="maxInstallmentAmountError"></span>
                                                </div>
                                            </div>
                                            <!--./col -->
                                        </div>
                                        <!--row -->

                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Frequency of Installment <span class="text-danger">*</span></label>
                                        @php
                                            $options = [
                                                'MONTHLY' => 'Monthly',
                                                'WEEKLY' => 'Weekly',
                                            ];
                                            $options = ['' => 'Select'] + $options;
                                        @endphp

                                        {{ Form::select('peridiocity', $options, old('peridiocity', $debit_order->periodicity ?? ''), ['class' => 'form-control']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Number of deduction <span class="text-danger">*</span></label>
                                        {{ Form::number('number_of_installment', old('number_of_installment', $debit_order->installment_number ?? ''), ['class="form-control"','placeholder="Write number installment..."','min' => 1]) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Deduction Start Date <span class="text-danger">*</span></label>
                                        {{ Form::date('start_date', old('start_date', $debit_order->start_date ?? ''), ['class="form-control"']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Deduction End<span class="text-danger">*</span></label>
                                        @php
                                            $options = [
                                                'totalLoanCovered' => 'Until total loan covered',
                                                'specificDate' => 'Specific date',
                                            ];
                                            $options = ['' => 'Select'] + $options;
                                        @endphp

                                        {{ Form::select('deduction_end', $options, old('deduction_end', $debit_order->deduction_end_date ?? ''), ['class' => 'form-control','id' => 'deduction_end']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3" id="end_date" style="display: none;">
                                    <div class="mb-3">
                                        <label>End Date <span class="text-danger">*</span></label>
                                        {{ Form::date('end_date', old('end_date', $debit_order->end_date ?? ''), ['class="form-control"','id="end_date"']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label for="formFile" class="form-label">Evidence of Aggrement</label>
                                        <input class="form-control" type="file" id="formFile" name="evidence_of_aggrement"
                                            accept="application/pdf" />
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3 text-sm-end d-none d-sm-block">
                                        <a href="{{ route('payment-mandate.lists') }}"
                                            class="btn btn-danger text-medium ">Cancel</a>
                                        <button type="submit" id="btnSubmit" class="btn btn-primary">
                                            Update <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
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

            //before on change debit order
            debitType = $('#debit_type').val();

            if (debitType === 'FIXED') {
                $('#standard_deduction').css('display', '');
                $('#max_min_deduction').css('display', 'none');
            } else if (debitType === 'DYNAMIC') {
                $('#max_min_deduction').css('display', '');
                $('#standard_deduction').css('display', 'none');
            }

            //type of debit on change
            $('#debit_type').on('change', function(e) {
                debitType = $(this).val();

                if (debitType === 'FIXED') {
                    $('#standard_deduction').css('display', '');
                    $('#max_min_deduction').css('display', 'none');
                } else if (debitType === 'DYNAMIC') {
                    $('#max_min_deduction').css('display', '');
                    $('#standard_deduction').css('display', 'none');
                }
            });

            //before on change start and end date
            deductionEndDate = $('#deduction_end').val();

            if (deductionEndDate === 'specificDate')
                $('#end_date').css('display', '');
            else
                $('#end_date').css('display', 'none');

            //on change deduction start and end date
            $('#deduction_end').on('change', function(e) {
                deductionEndDate = $(this).val();

                if (deductionEndDate === 'specificDate')
                    $('#end_date').css('display', '');
                else
                    $('#end_date').css('display', 'none');
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
                        url: "{{ route('payment-mandate.update') }}",
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

                            //success message
                            $("#success-msg").html(response.success_msg);
                            $('#btn-okay').css('display', '');
                            $('#subscribeModal').modal('show');
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


            //btn close
            $('#btn-okay').on('click', function(e) {
                uuid = $(this).attr('customer-uuid');
                e.preventDefault();

                //redirect to show
                window.location.href = "/payment-mandate/details/" + uuid
            });
        });
    </script>
@endsection

<!-- subscribeModal -->
<div class="modal fade" id="subscribeModal" tabindex="-1" aria-labelledby="subscribeModalLabel" aria-hidden="true"
    data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 justify-content-center">
                <h5 class="text-uppercase">Update Payment Mandate</h5>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="avatar-md mx-auto mb-4">
                        <div class="avatar-title bg-light rounded-circle text-success h1">
                            <i class="mdi mdi-check"></i>
                        </div>
                    </div>

                    <div class="row justify-content-center">
                        <div class="col-xl-10">
                            <p class="text-muted font-size-14 mb-4" id="success-msg"></p>

                            <button type="button" id="btn-okay" name="Okay" class="btn btn-success"
                                customer-uuid="{{ $customer_account->uuid ?? null }}"
                                style="display: none;">Okay</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- end modal -->
