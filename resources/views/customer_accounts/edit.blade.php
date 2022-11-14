@extends('layouts.master')

@section('title')
    Edit Customer Account
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            Edit Customer Account
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">

                    <!--./row -->
                    <div class="row">
                        <div class="col-lg-12">
                            {{ Form::open(['url' => route('customer-accounts.update', $customer_account->id), 'class' => 'form-horizontal needs-validation', 'enctype' => 'multipart/form-data', 'novalidate']) }}
                            @method('PUT')
                            {{ Form::token() }}

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
                                <div class="col-lg-12">
                                    <div class="mb-3 text-sm-end d-none d-sm-block">
                                        <a href="{{ url('customer-accounts.lists') }}"
                                            class="btn btn-danger btn-xs text-medium">Cancel</a>
                                        <button type="submit" id="btnSubmit" class="btn btn-primary">
                                            Update <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
                                        </button>
                                    </div>
                                </div>
                                <!--./col-lg-12 -->
                            </div>
                            <!--./row -->
                            {{ Form::close() }}
                        </div>
                    </div>

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
        });
    </script>
@endsection
