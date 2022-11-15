@extends('layouts.master')

@section('title')
    Update Payment Mandate
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            Update Payment Mandate
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    {{ Form::open(['url' => route('payment-mandate.update', $debit_order->id), 'class' => 'form-horizontal needs-validation', 'enctype' => 'multipart/form-data', 'novalidate']) }}
                    @method('PUT')
                    {{ Form::token() }}

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

                                    {{ Form::select('account_type_id', $options, old('account_type_id', $debit_order->customerAccount->account_type_id ?? ''), ['class' => 'form-control account_type_id', 'id' => 'account_type_id']) }}
                                    <span class="invalid-feedback">{{ $errors->first('account_type_id') }}</span>
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="bank_mno_name">Bank Name/MNO <span class="text-danger">*</span></label>
                                    @php $options = []; @endphp
                                    @foreach ($accounts as $val)
                                        @php
                                            $options[$val->id] = $val->name;
                                        @endphp
                                    @endforeach
                                    @php
                                        $options = ['' => 'Select'] + $options;
                                    @endphp

                                    {{ Form::select('account_id', $options, old('account_id', $debit_order->customerAccount->account_id ?? ''), ['class' => 'form-control account_id', 'id' => 'account_id']) }}
                                    <span class="invalid-feedback">{{ $errors->first('account_id') }}</span>
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-4" id="account_name" style="display: block;">
                                <div class="mb-3">
                                    <label>Name of Account Holder <span class="text-danger">*</span></label>
                                    {{ Form::text('account_name', old('account_name', $debit_order->customerAccount->account_name ?? ''), ['class="form-control"', 'placeholder="Enter name of account holder..."', 'id="account_name"']) }}
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-4" id="account_number" style="display: block;">
                                <div class="mb-3">
                                    <label>Bank Account Number <span class="text-danger">*</span></label>
                                    {{ Form::text('account_number', old('account_number', $debit_order->customerAccount->account_number ?? ''), ['class="form-control"', 'placeholder="Enter account number..."']) }}
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-4" id="account_number_phone" style="display: none;">
                                <div class="mb-3">
                                    <label>MNO Phone <span class="text-danger">*</span></label>
                                    {{ Form::text('account_number_phone', old('account_number_phone', $debit_order->customerAccount ? reverse_casting_phone($debit_order->customerAccount->phone) : ''), ['class="form-control"', 'placeholder="Enter phone number..."']) }}
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-4" id="account_phone" style="display: block;">
                                <div class="mb-3">
                                    <label>Phone <span class="text-danger">*</span></label>
                                    {{ Form::text('phone', old('phone', $debit_order->customerAccount ? reverse_casting_phone($debit_order->customerAccount->phone) : ''), ['class="form-control"', 'placeholder="Enter phone connected to the bank account..."']) }}
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label>NIDA Number</label>
                                    {{ Form::text('nida_number', old('nida_number', $debit_order->customerAccount->id_number ?? ''), ['class="form-control input-mask"', 'placeholder="Enter NIDA number..."', 'data-inputmask' => "'mask': '99999999-99999-99999-99'"]) }}
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label>Email</label>
                                    {{ Form::email('email', old('email', $debit_order->customerAccount->email ?? ''), ['class="form-control input-mask"', 'placeholder="Enter email..."']) }}
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label>Address</label>
                                    {{ Form::text('address', old('address', $debit_order->customerAccount->address ?? ''), ['class="form-control input-mask"', 'placeholder="Enter address..."']) }}
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
                                    {{ Form::text('loan_amount', old('loan_amount', $debit_order ? number_format($debit_order->total_amount) : ''), ['class="form-control number-separator"', 'placeholder="Write total loan amount..."']) }}
                                    <span class="invalid-feedback">{{ $errors->first('loan_amount') }}</span>
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

                                    {{ Form::select('debit_type', $options, old('debit_type', $debit_order->debit_type ?? ''), ['class' => 'form-control', 'id' => 'debit_type']) }}
                                    <span class="invalid-feedback">{{ $errors->first('debit_type') }}</span>
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-3" id="standard_deduction" style="display: block">
                                <div class="mb-3">
                                    <label>Deduction Amount <span class="text-danger">*</span></label>
                                    {{ Form::text('installment_amount', old('installment_amount', $debit_order ? number_format($debit_order->installment_amount) : ''), ['class="form-control number-separator"', 'placeholder="Write deduction amount..."']) }}
                                    <span class="invalid-feedback">{{ $errors->first('installment_amount') }}</span>
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-6" id="max_min_deduction" style="display: none">
                                <div class="mb-3">
                                    <label>Deduction Amount <span class="text-danger">*</span></label>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                {{ Form::text('min_installment_amount', old('min_installment_amount', $debit_order ? number_format($debit_order->min_installment_amount) : ''), ['class="form-control number-separator"', 'placeholder="Minimum deduction amount..."', 'id="min_installment_amount"']) }}
                                                <span
                                                    class="invalid-feedback">{{ $errors->first('min_installment_amount') }}</span>
                                            </div>
                                        </div>
                                        <!--./col -->

                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                {{ Form::text('max_installment_amount', old('max_installment_amount', $debit_order ? number_format($debit_order->max_installment_amount) : ''), ['class="form-control number-separator"', 'placeholder="Maximum deduction amount..."', 'id="max_installment_amount"']) }}
                                                <span
                                                    class="invalid-feedback">{{ $errors->first('max_installment_amount') }}</span>
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

                                    {{ Form::select('periodicity', $options, old('periodicity', $debit_order->periodicity ?? ''), ['class' => 'form-control']) }}
                                    <span class="invalid-feedback">{{ $errors->first('periodicity') }}</span>
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>Number of deduction <span class="text-danger">*</span></label>
                                    {{ Form::number('number_of_installment', old('number_of_installment', $debit_order->installment_number ?? ''), ['class="form-control"', 'placeholder="Write number installment..."', 'min' => 1]) }}
                                    <span class="invalid-feedback">{{ $errors->first('number_of_installment') }}</span>
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>Deduction Start Date <span class="text-danger">*</span></label>
                                    {{ Form::date('start_date', old('start_date', $debit_order->start_date ?? ''), ['class="form-control"']) }}
                                    <span class="invalid-feedback">{{ $errors->first('start_date') }}</span>
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

                                    {{ Form::select('deduction_end', $options, old('deduction_end', $debit_order->deduction_end_date ?? ''), ['class' => 'form-control', 'id' => 'deduction_end']) }}
                                    <span class="invalid-feedback">{{ $errors->first('deduction_end') }}</span>
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-3" id="end_date" style="display: none;">
                                <div class="mb-3">
                                    <label>End Date <span class="text-danger">*</span></label>
                                    {{ Form::date('end_date', old('end_date', $debit_order->end_date ?? ''), ['class="form-control"', 'id="end_date"']) }}
                                    <span class="invalid-feedback">{{ $errors->first('end_date') }}</span>
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label for="formFile" class="form-label">Evidence of Aggrement</label>
                                    <input class="form-control" type="file" id="formFile"
                                        name="evidence_of_aggrement" accept="application/pdf" />
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
                                    <button type="submit" class="btn btn-primary">
                                        Update
                                    </button>
                                </div>
                            </div>
                            <!--./col -->
                        </div>
                        <!--./row -->
                    </section>
                    {{ Form::close() }}
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

        });
    </script>
@endsection
