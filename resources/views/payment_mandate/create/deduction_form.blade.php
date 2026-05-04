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
                <div class="pmc-kicker">New Mandate Creation</div>
                <div class="pmc-title">Step 2: Define Deduction Terms</div>
                @include('payment_mandate.create.partials.stepper', ['currentStep' => 2])
            </div>
        </div>

        <div class="card pmc-card">
            <div class="card-body">
                @if (session()->get('success'))
                    <div class="alert alert-success mb-2">{{ session()->get('success') }}</div>
                @elseif(session()->get('danger'))
                    <div class="alert alert-danger mb-2">{{ session()->get('danger') }}</div>
                @endif

                {{ Form::open(['url' => route('payment-mandate.store-deduction'), 'class' => 'form-horizontal', 'enctype' => 'multipart/form-data']) }}
                @method('POST')
                {{ Form::token() }}

                <input type="hidden" name="partner_id" value="{{ $provider->partner->id ?? null }}">
                <input type="hidden" name="customer_account_id" value="{{ $customer_account->id ?? null }}">
                <input type="hidden" name="customer_account_uuid" value="{{ $customer_account->uuid ?? null }}">
                <input type="hidden" name="debit_order_id" value="{{ $debit_order->id ?? null }}">
                <input type="hidden" name="debit_order_uuid" value="{{ $debit_order->uuid ?? null }}">
                <input type="hidden" name="confirm_new_mandate" id="confirm_new_mandate" value="0">

                <section>
                    @if ($provider == null && $providers)
                        <div class="pmc-section">
                            <div class="pmc-section__title">Service Provider</div>
                            <div class="pmc-section__hint">Choose the provider that owns this mandate.</div>
                            <div class="row g-3">
                                <div class="col-lg-6 col-md-6">
                                    <div class="mb-3">
                                        <label>Service Provider <span class="text-danger">*</span></label>
                                        @php
                                            $options = [];
                                        @endphp
                                        @foreach ($providers as $val)
                                            @php
                                                $options[$val->id] = $val->business_name;
                                            @endphp
                                        @endforeach
                                        @php
                                            $options = ['' => '-- Select --'] + $options;
                                        @endphp

                                        {{ Form::select('provider_id', $options, old('provider_id', $debit_order->partner_id ?? ''), ['class' => 'form-control select2', 'id' => 'provider_id']) }}
                                        <span class="text-danger">{{ $errors->first('provider_id') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="pmc-section">
                        <div class="pmc-section__title">Deduction Terms</div>
                        <div class="pmc-section__hint">Set the commercial terms the customer is agreeing to, including amount, periodicity, and number of deductions.</div>
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                <div class="mb-3">
                                    <label>Total Amount <span class="text-danger">*</span></label>
                                    {{ Form::text('loan_amount', old('loan_amount', $debit_order ? number_format($debit_order->total_amount) : ''), ['class="form-control number-separator"', 'placeholder="Write total total amount..."', 'id="loan_amount"', 'x-ref' => 'loanAmount', '@input' => 'numberOfInstallment()']) }}
                                    <span class="text-danger">{{ $errors->first('loan_amount') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                <div class="mb-3">
                                    <label>Debit Type <span class="text-danger">*</span></label>
                                    @php
                                        $options = ['FIXED' => 'Fixed'];
                                        $options = ['' => '-- Select --'] + $options;
                                    @endphp

                                    {{ Form::select('debit_type', $options, old('debit_type', $debit_order->debit_type ?? ''), ['class' => 'form-control', 'id' => 'debit_type', 'x-model' => 'debitType', '@change' => 'numberOfInstallment()']) }}
                                    <span class="text-danger">{{ $errors->first('debit_type') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-6 col-12" x-show="showFixedDeduction" x-cloak>
                                <div class="mb-3">
                                    <label>Installment Amount <span class="text-danger">*</span></label>
                                    {{ Form::text('installment_amount', old('installment_amount', $debit_order ? number_format($debit_order->installment_amount) : ''), ['class="form-control number-separator"', 'placeholder="Write deduction amount..."', 'id="installment_amount"', 'x-ref' => 'installmentAmount', '@input' => 'numberOfInstallment()']) }}
                                    <span class="text-danger">{{ $errors->first('installment_amount') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-6 col-12" x-show="showDynamicDeduction" x-cloak>
                                <div class="mb-3">
                                    <label>Min Deduction Amount <span class="text-danger">*</span></label>
                                    {{ Form::text('min_installment_amount', old('min_installment_amount', $debit_order ? number_format($debit_order->min_installment_amount) : ''), ['class="form-control number-separator"', 'placeholder="Minimum deduction amount..."', 'id="min_installment_amount"']) }}
                                    <span class="text-danger">{{ $errors->first('min_installment_amount') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-6 col-12" x-show="showDynamicDeduction" x-cloak>
                                <div class="mb-3">
                                    <label>Max Deduction Amount <span class="text-danger">*</span></label>
                                    {{ Form::text('max_installment_amount', old('max_installment_amount', $debit_order ? number_format($debit_order->max_installment_amount) : ''), ['class="form-control number-separator"', 'placeholder="Maximum deduction amount..."', 'id="max_installment_amount"', 'x-ref' => 'maxInstallmentAmount', '@input' => 'numberOfInstallment()']) }}
                                    <span class="text-danger">{{ $errors->first('max_installment_amount') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                <div class="mb-3">
                                    <label>Frequency of Installment <span class="text-danger">*</span></label>
                                    {{ Form::select('periodicity', $periodicity_options, old('periodicity', $debit_order->periodicity ?? ''), ['class' => 'form-control', 'id' => 'periodicity', '@change' => 'numberOfInstallment()']) }}
                                    <span class="text-danger">{{ $errors->first('periodicity') }}</span>
                                    @if ($customer_account && $customer_account->account)
                                        <small class="text-muted d-block mt-1">
                                            Allowed for {{ $customer_account->account->name }}:
                                            {{ implode(', ', $customer_account->account->normalizedAllowedPeriodicities()) }}
                                        </small>
                                    @endif
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                <div class="mb-3">
                                    <label>Number of installments <span class="text-danger">*</span></label>
                                    {{ Form::number('number_of_installment', old('number_of_installment', $debit_order->installment_number ?? ''), ['class="form-control"', 'placeholder="Write number installment..."', 'min' => 1, 'max' => 100, 'id' => 'number_of_installment', 'x-ref' => 'numberOfInstallmentField']) }}
                                    <span class="text-danger">{{ $errors->first('number_of_installment') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                <div class="mb-3">
                                    <label>Deduction Start Date <span class="text-danger">*</span></label>
                                    {{ Form::date('start_date', old('start_date', $debit_order->start_date ?? ''), ['class' => 'form-control', 'id' => 'start_date', 'min' => now()->addDay()->format('Y-m-d')]) }}
                                    <span class="text-danger">{{ $errors->first('start_date') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="pmc-actions">
                    <a href="{{ route('payment-mandate.create-account', [$customer_account->uuid, $debit_order ? $debit_order->uuid : '']) }}" class="btn btn-outline-danger">Previous</a>
                    <button type="submit" class="btn btn-primary px-4">Next</button>
                </div>
                {{ Form::close() }}
            </div>
        </div>

        @if (!empty($arr_debit_orders) && count($arr_debit_orders) > 0)
            @include('payment_mandate.modals.existing_mandate_modal')
        @endif
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        function initData() {
            return {
                debitType: "{{ old('debit_type', $debit_order->debit_type ?? 'FIXED') }}",
                hasExistingMandates: @json(!empty($arr_debit_orders) && count($arr_debit_orders) > 0),

                get showFixedDeduction() {
                    return this.debitType === 'FIXED' || this.debitType === '';
                },

                get showDynamicDeduction() {
                    return this.debitType === 'DYNAMIC';
                },

                init() {
                    if (this.hasExistingMandates) {
                        const modalElement = document.getElementById('existingDebitOrdersModal');

                        if (modalElement && window.bootstrap) {
                            const modal = new bootstrap.Modal(modalElement, {
                                backdrop: 'static',
                                keyboard: false
                            });

                            modal.show();
                        }
                    }

                    this.numberOfInstallment();
                },

                cancelExistingMandateRequest() {
                    window.location.href = "{{ route('payment-mandate.lists') }}";
                },

                proceedWithNewMandate() {
                    const confirmField = document.getElementById('confirm_new_mandate');
                    const modalElement = document.getElementById('existingDebitOrdersModal');

                    if (confirmField) {
                        confirmField.value = 1;
                    }

                    if (modalElement && window.bootstrap) {
                        const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                        modal.hide();
                    }
                },

                numberOfInstallment() {
                    const totalAmt = (this.$refs.loanAmount?.value || '').replace(/,/g, '');
                    const deductionAmt = (this.$refs.installmentAmount?.value || '').replace(/,/g, '');
                    const maxInstallmentAmt = (this.$refs.maxInstallmentAmount?.value || '').replace(/,/g, '');

                    if (this.debitType === 'FIXED' && totalAmt && deductionAmt) {
                        const numberInstallment = parseFloat(totalAmt) / parseFloat(deductionAmt);

                        if (!Number.isNaN(numberInstallment) && this.$refs.numberOfInstallmentField) {
                            this.$refs.numberOfInstallmentField.value = Math.ceil(numberInstallment);
                        }
                    } else if (this.debitType === 'DYNAMIC' && totalAmt && maxInstallmentAmt) {
                        const numberInstallment = parseFloat(totalAmt) / parseFloat(maxInstallmentAmt);

                        if (!Number.isNaN(numberInstallment) && this.$refs.numberOfInstallmentField) {
                            this.$refs.numberOfInstallmentField.value = Math.ceil(numberInstallment);
                        }
                    }
                },
            }
        }
    </script>
@endsection
