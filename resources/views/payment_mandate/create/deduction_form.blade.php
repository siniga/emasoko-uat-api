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

<div class="row" x-data="initData();">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">

                <div class="stepwizard mb-4">
                    <div class="stepwizard-row setup-panel">
                        <div class="stepwizard-step bg-success">
                            <a href="#step-1" type="button" class="btn btn-success btn-circle text-white"
                                disabled="disabled">1</a>
                            <span class="text-white">Deduction Channel</span>
                        </div>

                        <div class="stepwizard-step bg-primary">
                            <a href="#step-2" type="button" class="btn btn-secondary btn-circle"
                                disabled="disabled">2</a>
                            <span class="text-white">Customer Deduction Information</span>
                        </div>

                         <div class="stepwizard-step">
                            <a href="#step-3" type="button" class="btn btn-default btn-circle"
                                disabled="disabled">3</a>
                            <span>Deduction Confirmation</span>
                        </div>
                    </div>
                </div>
                <!--./form wizard -->

                @if (session()->get('success'))
                    <div class="alert alert-success mb-2">
                        {{ session()->get('success') }}
                    </div>
                @elseif(session()->get('danger'))
                    <div class="alert alert-danger mb-2">
                        {{ session()->get('danger') }}
                    </div>
                @endif

                {{ Form::open(['url' => route('payment-mandate.store-deduction'), 'class' => 'form-horizontal', 'enctype' => 'multipart/form-data']) }}
                @method('POST')
                {{ Form::token() }}

                <input type="hidden" name="partner_id" value="{{ $provider->partner->id ?? null }}">
                <input type="hidden" name="customer_account_id" value="{{ $customer_account->id ?? null }}">
                <input type="hidden" name="customer_account_uuid" value="{{ $customer_account->uuid ?? null }}">
                <input type="hidden" name="debit_order_id" value="{{ $debit_order->id ?? null }}">
                <input type="hidden" name="debit_order_uuid" value="{{ $debit_order->uuid ?? null }}">

                <!-- Customer Information -->
                <section>
                    @if ($provider == null)
                        <div class="row">
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
                                        $options = ['' => 'Select'] + $options;
                                    @endphp

                                    {{ Form::select('provider_id', $options, old('provider_id', $debit_order->partner_id ?? ''), ['class' => 'form-control select2', 'id' => 'provider_id', 'x-model="form_data.partner_id"']) }}
                                    <span class="text-danger">{{ $errors->first('provider_id') }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-lg-3 col-md-3 col-sm-3 col-12">
                            <div class="mb-3">
                                <label>Total Amount <span class="text-danger">*</span></label>
                                {{ Form::text('loan_amount', old('loan_amount'), ['class="form-control number-separator"', 'placeholder="Write total total amount..." x-model="form_data.total_amount"']) }}
                                <span class="text-danger">{{ $errors->first('loan_amount') }}</span>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-3 col-12">
                            <div class="mb-3">
                                <label>Debit Type <span class="text-danger">*</span></label>
                                @php
                                    $options = ['FIXED' => 'Fixed',];
                                    $options = ['' => 'Select'] + $options;
                                @endphp

                                {{ Form::select('debit_type', $options, old('debit_type'), ['class' => 'form-control', 'id' => 'debit_type', 'x-model="form_data.debit_type"']) }}
                                <span class="text-danger">{{ $errors->first('debit_type') }}</span>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-3 col-12" id="fixed_deduction" style="display: block">
                            <div class="mb-3">
                                <label>Installment Amount <span class="text-danger">*</span></label>
                                {{ Form::text('installment_amount', old('installment_amount'), ['class="form-control number-separator"', 'placeholder="Write deduction amount..." x-model="form_data.deduction_amount"']) }}
                                <span class="text-danger">{{ $errors->first('installment_amount') }}</span>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-3 col-12" id="min_deduction" style="display: none">
                            <div class="mb-3">
                                <label>Min Deduction Amount <span class="text-danger">*</span></label>
                                {{ Form::text('min_installment_amount', old('min_installment_amount'), ['class="form-control number-separator"', 'placeholder="Minimum deduction amount..." x-model="form_data.min_installment_amount"', 'id="min_installment_amount"']) }}
                                <span class="text-danger">{{ $errors->first('min_installment_amount') }}</span>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-3 col-12" id="max_deduction" style="display: none">
                            <div class="mb-3">
                                <label>Installment Amount <span class="text-danger">*</span></label>
                                {{ Form::text('max_installment_amount', old('max_installment_amount'), ['class="form-control number-separator"', 'placeholder="Maximum deduction amount..." x-model="form_data.max_installment_amount"', 'id="max_installment_amount"']) }}
                                <span class="text-danger">{{ $errors->first('max_installment_amount') }}</span>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-3 col-12">
                            <div class="mb-3">
                                <label>Frequency of Installment <span class="text-danger">*</span></label>
                                @php
                                    $options = [
                                        'MONTHLY' => 'Monthly',
                                    ];
                                    $options = ['' => 'Select'] + $options;
                                @endphp

                                {{ Form::select('periodicity', $options, old('periodicity'), ['class' => 'form-control', 'x-model="form_data.periodicity" x-on:change="numberOfInstallment();"']) }}
                                <span class="text-danger">{{ $errors->first('periodicity') }}</span>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-3 col-12">
                            <div class="mb-3">
                                <label>Number of installments <span class="text-danger">*</span></label>
                                {{ Form::number('number_of_installment', old('number_of_installment'), ['class="form-control"', 'placeholder="Write number installment..." x-model="form_data.number_of_installment"', 'min' => 1, 'max' => 100]) }}
                                <span class="text-danger">{{ $errors->first('number_of_installment') }}</span>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-3 col-12">
                            <div class="mb-3">
                                <label>Deduction Start Date <span class="text-danger">*</span></label>
                                {{ Form::date('start_date', old('start_date'), ['class="form-control"', 'min' => date('Y-m-d'), 'x-model="form_data.start_date"']) }}
                                <span class="text-danger">{{ $errors->first('start_date') }}</span>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-3 col-12">
                            <div class="mb-3">
                                <label>Deduction End<span class="text-danger">*</span></label>
                                @php
                                    $options = [
                                        'totalLoanCovered' => 'Until total loan covered',
                                        //'specificDate' => 'Specific date',
                                    ];
                                    $options = ['' => 'Select'] + $options;
                                @endphp

                                {{ Form::select('deduction_end', $options, old('deduction_end'), ['class' => 'form-control', 'id' => 'deduction_end', 'x-model="form_data.deduction_end"']) }}
                                <span class="text-danger">{{ $errors->first('deduction_end') }}</span>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-3 col-12" id="end_date" style="display: none;">
                            <div class="mb-3">
                                <label>End Date <span class="text-danger">*</span></label>
                                {{ Form::date('end_date', old('end_date'), ['class="form-control"', 'id="end_date" x-model="form_data.end_date"']) }}
                                <span class="text-danger">{{ $errors->first('end_date') }}</span>
                            </div>
                        </div>
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                            <div class="mb-3 text-sm-end d-sm-block">
                                <a href="{{ route('payment-mandate.create-account',[$customer_account->uuid, ($debit_order ? $debit_order->uuid : '')]) }}"
                                    class="btn btn-outline-danger text-medium">Previous</a>

                                <button type="submit" class="btn btn-primary px-3">Next</button>
                            </div>
                        </div>
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
            //before on change debit order
            debitType = $('#debit_type').val();

            if (debitType === 'FIXED') {
                $('#fixed_deduction').css('display', '');
                $('#min_deduction').css('display', 'none');
                $('#max_deduction').css('display', 'none');
            } else if (debitType === 'DYNAMIC') {
                $('#min_deduction').css('display', '');
                $('#max_deduction').css('display', '');
                $('#fixed_deduction').css('display', 'none');
            }

            //type of debit on change
            $('#debit_type').on('change', function(e) {
                debitType = $(this).val();

                if (debitType === 'FIXED') {
                    $('#fixed_deduction').css('display', '');
                    $('#min_deduction').css('display', 'none');
                    $('#max_deduction').css('display', 'none');
                } else if (debitType === 'DYNAMIC') {
                    $('#min_deduction').css('display', '');
                    $('#max_deduction').css('display', '');
                    $('#fixed_deduction').css('display', 'none');
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

        //alpine
        function initData() {
            return {
                form_data: {
                    partner_id: "{{ $debit_order ? $debit_order->partner_id : '' }}",
                    total_amount: "{{ $debit_order ? number_format($debit_order->total_amount) : '' }}",
                    deduction_amount: '{{ $debit_order ? number_format($debit_order->installment_amount) : '' }}',
                    min_installment_amount: '{{ $debit_order ? number_format($debit_order->min_installment_amount) : '' }}',
                    max_installment_amount: '{{ $debit_order ? number_format($debit_order->max_installment_amount) : '' }}',
                    debit_type: '{{ $debit_order ? $debit_order->debit_type : '' }}',
                    number_of_installment: '{{ $debit_order ? $debit_order->installment_number : '' }}',
                    periodicity: '{{ $debit_order ? $debit_order->periodicity : '' }}',
                    start_date: '{{ $debit_order ? $debit_order->start_date : '' }}',
                    deduction_end: '{{ $debit_order ? $debit_order->deduction_end_date : '' }}',
                    end_date: '{{ $debit_order ? $debit_order->start_date : '' }}',
                },

                //calculate number_of_installment
                numberOfInstallment() {
                    totalAmt = this.form_data.total_amount.replace(/,/g, "");
                    deductionAmt = this.form_data.deduction_amount.replace(/,/g, "");
                    maxInstallmentAmt = this.form_data.max_installment_amount.replace(/,/g, "");

                    if (this.form_data.debit_type == "FIXED"){
                        numberInstallment = parseFloat(totalAmt)/parseFloat(deductionAmt)
                        this.form_data.number_of_installment = Math.ceil(numberInstallment)

                        console.log("== number of installment ==")
                        console.log(numberInstallment)
                    } else if(this.form_data.debit_type == "DYNAMIC") {
                        numberInstallment = parseFloat(totalAmt)/parseFloat(maxInstallmentAmt)
                        this.form_data.number_of_installment = Math.ceil(numberInstallment)

                        console.log("== number of installment ==")
                        console.log(numberInstallment)
                    }
                },
            }
        }
    </script>
@endsection
