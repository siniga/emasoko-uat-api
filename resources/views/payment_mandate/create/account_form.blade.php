@extends('layouts.master')

@section('title')
    Create Payment Mandate
@endsection

@section('content')
    @include('payment_mandate.create.partials.styles')

    <div class="pmc-shell" x-data="initData()" x-init="init()">
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

        <div class="card pmc-hero mb-4">
            <div class="card-body">
                <div class="pmc-kicker">Payment Mandate Setup</div>
                <div class="pmc-title">Step 1: Channel and Customer Details</div>
                <p class="pmc-subtitle">Start with the deduction channel, customer identity, and the account that will be used for the mandate.</p>
                @include('payment_mandate.create.partials.stepper', ['currentStep' => 1])
            </div>
        </div>

        <div class="card pmc-card">
            <div class="card-body">
                {{ Form::open(['url' => route('payment-mandate.store-account'), 'method' => 'POST', 'class' => 'form-horizontal']) }}
                {{ Form::token() }}
                <input type="hidden" name="customer_account_id" value="{{ $customer_account->id ?? null }}">
                <input type="hidden" name="customer_account_uuid" value="{{ $customer_account->uuid ?? null }}">
                <input type="hidden" name="debit_order_id" value="{{ $debit_order->id ?? null }}">
                <input type="hidden" name="debit_order_uuid" value="{{ $debit_order->uuid ?? null }}">

                <section>
                    <div class="pmc-section">
                        <div class="pmc-section__title">Account Channel</div>
                        <div class="pmc-section__hint">Select the payment rail and account identity that will be linked to this mandate.</div>

                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6 col-12">
                                <div class="mb-3">
                                    <label>Payment Channel <span class="text-danger">*</span></label>
                                    @php
                                        $options = [];
                                    @endphp
                                    @foreach ($account_types as $val)
                                        @php
                                            $options[$val->id] = $val->name;
                                        @endphp
                                    @endforeach
                                    @php
                                        $options = ['' => '-- Select --'] + $options;
                                    @endphp

                                    {{ Form::select('account_type_id', $options, old('account_type_id', $customer_account->account_type_id ?? ''), ['class' => 'form-control', 'id' => 'account_type_id', 'x-model' => 'accountTypeId', '@change' => 'handleAccountTypeChange']) }}
                                    <span class="text-danger">{{ $errors->first('account_type_id') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-12">
                                <div class="mb-3">
                                    <label x-html="bankMnoLabel"></label>
                                    @php $options = []; @endphp
                                    @foreach ($accounts as $val)
                                        @php
                                            $options[$val->id] = $val->name;
                                        @endphp
                                    @endforeach
                                    @php
                                        $options = ['' => '-- Select --'] + $options;
                                    @endphp

                                    {{ Form::select('account_id', $options, old('account_id', $customer_account->account_id ?? ''), ['class' => 'form-control', 'id' => 'account_id', 'x-ref' => 'accountId']) }}
                                    <span class="text-danger">{{ $errors->first('account_id') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-12 col-12" id="account_name" style="display: block;">
                                <div class="mb-3">
                                    <label>Name of Account Holder <span class="text-danger">*</span></label>
                                    {{ Form::text('account_name', old('account_name', $customer_account->account_name ?? ''), ['class="form-control"', 'placeholder="Enter name of account holder..."', 'id="account_name"']) }}
                                    <span class="text-danger">{{ $errors->first('account_name') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pmc-section">
                        <div class="pmc-section__title">Customer Identity</div>
                        <div class="pmc-section__hint">Use contact details that can be reached for verification, consent, and support.</div>

                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6 col-12" x-show="showBankFields" x-cloak>
                                <div class="mb-3">
                                    <label>Bank Account Number <span class="text-danger">*</span></label>
                                    {{ Form::text('account_number', old('account_number', $customer_account->account_number ?? ''), ['class="form-control"', 'placeholder="Enter account number..."']) }}
                                    <span class="text-danger">{{ $errors->first('account_number') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-12" x-show="showMnoFields" x-cloak>
                                <div class="mb-3">
                                    <label>Phone <span class="text-danger">*</span></label>
                                    {{ Form::text('account_number_phone', old('account_number_phone', $customer_account ? reverse_casting_phone($customer_account->phone) : ''), ['class="form-control"', 'placeholder="Enter phone number..."']) }}
                                    <span class="text-danger">{{ $errors->first('account_number_phone') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-12" x-show="showBankFields" x-cloak>
                                <div class="mb-3">
                                    <label>Phone <small>(Connected to bank account)</small> <span class="text-danger">*</span></label>
                                    {{ Form::text('phone', old('phone', $customer_account ? reverse_casting_phone($customer_account->phone) : ''), ['class="form-control"', 'placeholder="Enter phone connected to the bank account..."']) }}
                                    <span class="text-danger">{{ $errors->first('phone') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-12">
                                <div class="mb-3">
                                    <label>Secondary Phone</label>
                                    {{ Form::text('secondary_phone', old('secondary_phone', $customer_account->secondary_phone ?? ''), ['class="form-control input-mask"', 'placeholder="Enter secondary phone..."']) }}
                                    <span class="text-danger">{{ $errors->first('secondary_phone') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-12">
                                <div class="mb-3">
                                    <label>NIDA Number <span class="text-danger">*</span></label>
                                    {{ Form::text('nida_number', old('nida_number', $customer_account->customer->id_number ?? ''), ['class="form-control input-mask"', 'placeholder="Enter NIDA number..."', 'data-inputmask' => "'mask': '99999999-99999-99999-99'"]) }}
                                    <span class="text-danger">{{ $errors->first('nida_number') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-12">
                                <div class="mb-3">
                                    <label>Email</label>
                                    {{ Form::email('email', old('email', $customer_account->customer->email ?? ''), ['class="form-control input-mask"', 'placeholder="Enter email..."']) }}
                                    <span class="text-danger">{{ $errors->first('email') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-12 col-12">
                                <div class="mb-3">
                                    <label>Address <span class="text-danger">*</span></label>
                                    {{ Form::text('address', old('address', $customer_account->customer->address ?? ''), ['class="form-control input-mask"', 'placeholder="Enter address..."']) }}
                                    <span class="text-danger">{{ $errors->first('address') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="pmc-actions">
                    <a href="{{ route('payment-mandate.lists') }}" class="btn btn-outline-danger">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Next</button>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        function initData() {
            return {
                accountTypeId: "{{ old('account_type_id', $customer_account->account_type_id ?? '') }}",
                bankMnoLabel: 'Bank Name <span class="text-danger">*</span>',

                get showBankFields() {
                    return String(this.accountTypeId) === '1';
                },

                get showMnoFields() {
                    return String(this.accountTypeId) === '2';
                },

                init() {
                    this.syncAccountTypeState();
                },

                syncAccountTypeState() {
                    this.bankMnoLabel = String(this.accountTypeId) === '2'
                        ? 'MNO Name <span class="text-danger">*</span>'
                        : 'Bank Name <span class="text-danger">*</span>';
                },

                async handleAccountTypeChange() {
                    this.syncAccountTypeState();

                    if (!this.accountTypeId) {
                        return;
                    }

                    try {
                        const response = await fetch('/account-types/' + this.accountTypeId, {
                            headers: {
                                Accept: 'text/html',
                            },
                        });

                        if (!response.ok) {
                            return;
                        }

                        const optionsHtml = await response.text();

                        if (this.$refs.accountId) {
                            this.$refs.accountId.innerHTML = optionsHtml;
                        }
                    } catch (error) {
                        console.error('Failed to load accounts', error);
                    }
                },
            }
        }
    </script>
@endsection
