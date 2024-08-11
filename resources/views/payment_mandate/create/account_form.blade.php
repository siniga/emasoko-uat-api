@extends('layouts.master')

@section('title')
    Create Payment Mandate
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">Create Payment Mandate</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('payment-mandate.lists') }}">Payment Mandates</a></li>
                    <li class="breadcrumb-item active">Create Payment mandate</li>
                </ol>
            </div>

        </div>
    </div>
</div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">

                    <div class="stepwizard mb-4">
                        <div class="stepwizard-row setup-panel">
                            <div class="stepwizard-step bg-primary">
                                <a href="#step-1" type="button" class="btn btn-secondary btn-circle" disabled="disabled">1</a>
                                <span class="text-white">Deduction Channel</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-default btn-circle"
                                    disabled="disabled">2</a>
                                <span>Customer Deduction Information</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-default btn-circle"
                                    disabled="disabled">3</a>
                                <span>Deduction Confirmation</span>
                            </div>
                        </div>
                    </div>
                    <!--./form wizard -->

                    {{ Form::open(['url' => route('payment-mandate.store-account'), 'method' => 'POST', 'class' => 'form-horizontal']) }}
                    {{ Form::token() }}
                    <input type="hidden" name="customer_account_id" value="{{ $customer_account->id ?? null }}">
                    <input type="hidden" name="customer_account_uuid" value="{{ $customer_account->uuid ?? null }}">
                    <input type="hidden" name="debit_order_id" value="{{ $debit_order->id ?? null }}">  
                    <input type="hidden" name="debit_order_uuid" value="{{ $debit_order->uuid ?? null }}">  

                    <!-- Customer Information -->
                    <section>
                        <div class="row ">                        
                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>Payment Channel <span class="text-danger">*</span></label>
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
                                    <span class="text-danger">{{ $errors->first('account_type_id') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>Bank Name/MNO <span class="text-danger">*</span></label>
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
                                    <span class="text-danger">{{ $errors->first('account_id') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-6" id="account_name" style="display: block;">
                                <div class="mb-3">
                                    <label>Name of Account Holder <span class="text-danger">*</span></label>
                                    {{ Form::text('account_name', old('account_name', $customer_account->account_name ?? ''), ['class="form-control"', 'placeholder="Enter name of account holder..."', 'id="account_name"']) }}
                                    <span class="text-danger">{{ $errors->first('account_name') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3" id="account_number" style="display: block;">
                                <div class="mb-3">
                                    <label>Bank Account Number <span class="text-danger">*</span></label>
                                    {{ Form::text('account_number', old('account_number', $customer_account->account_number ?? ''), ['class="form-control"', 'placeholder="Enter account number..."']) }}
                                    <span class="text-danger">{{ $errors->first('account_number') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3" id="bank_code" style="display: block;">
                                <div class="mb-3">
                                    <label>Bank Code <span class="text-danger">*</span></label>
                                    {{ Form::text('bank_code', old('bank_code', $customer_account->bank_code ?? ''), ['class="form-control"', 'placeholder="Enter bank code..."']) }}
                                    <span class="text-danger">{{ $errors->first('bank_code') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3" id="account_number_phone" style="display: none;">
                                <div class="mb-3">
                                    <label>MNO Phone <span class="text-danger">*</span></label>
                                    {{ Form::text('account_number_phone', old('account_number_phone', $customer_account ? reverse_casting_phone($customer_account->phone) : ''), ['class="form-control"', 'placeholder="Enter phone number..."']) }}
                                    <span class="text-danger">{{ $errors->first('account_number_phone') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3" id="account_phone" style="display: block;">
                                <div class="mb-3">
                                    <label>Phone <small>(Connected to bank account)</small> <span class="text-danger">*</span></label>
                                    {{ Form::text('phone', old('phone', $customer_account ? reverse_casting_phone($customer_account->phone) : ''), ['class="form-control"', 'placeholder="Enter phone connected to the bank account..."']) }}
                                    <span class="text-danger">{{ $errors->first('phone') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>Secondary Phone</label>
                                    {{ Form::text('secondary_phone', old('secondary_phone', $customer_account->secondary_phone ?? ''), ['class="form-control input-mask"', 'placeholder="Enter secondary phone..."']) }}
                                    <span class="text-danger">{{ $errors->first('secondary_phone') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>NIDA Number <span class="text-danger">*</span></label>
                                    {{ Form::text('nida_number', old('nida_number', $customer_account->customer->id_number ?? ''), ['class="form-control input-mask"', 'placeholder="Enter NIDA number..."', 'data-inputmask' => "'mask': '99999999-99999-99999-99'"]) }}
                                    <div class="text-danger">{{ $errors->first('nida_number') }}</div>
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>Email</label>
                                    {{ Form::email('email', old('email', $customer_account->customer->email ?? ''), ['class="form-control input-mask"', 'placeholder="Enter email..."']) }}
                                    <div class="text-danger">{{ $errors->first('email') }}</div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label>Address <span class="text-danger">*</span></label>
                                    {{ Form::text('address', old('address', $customer_account->customer->address ?? ''), ['class="form-control input-mask"', 'placeholder="Enter address..."']) }}
                                    <div class="text-danger">{{ $errors->first('address') }}</div>
                                </div>
                            </div>
                        </div>
                        <!--./row -->

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3 text-sm-end d-none d-sm-block">
                                    <a href="{{ route('payment-mandate.lists') }}"
                                        class="btn btn-outline-danger text-medium">
                                        Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        Next
                                    </button>
                                </div>
                            </div>
                        </div>
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
                    $('#bank_code').css('display', '')
                    $('#account_number_phone').css('display', 'none')
                    $('#account_phone').css('display', '')
                } else if (accountTypeId == 2) {
                    $('.bank_mno_name').html('MNO Name <span class="text-danger">*</span>');

                    //hide or show
                    $('#account_name').css('display', 'none')
                    $('#account_number').css('display', 'none')
                    $('#bank_code').css('display', 'none')
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
                    $('#bank_code').css('display', '')
                    $('#account_number_phone').css('display', 'none')
                    $('#account_phone').css('display', '')
                } else if (accountTypeId == 2) {
                    $('.bank_mno_name').html('MNO Name <span class="text-danger">*</span>');

                    //hide or show
                    $('#account_name').css('display', 'none')
                    $('#account_number').css('display', 'none')
                    $('#bank_code').css('display', 'none')
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
        });
    </script>
@endsection
