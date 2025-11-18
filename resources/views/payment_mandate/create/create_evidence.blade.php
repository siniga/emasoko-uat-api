@extends('layouts.master')

@section('title')
    Create Payment Mandate
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">Evidence of Aggrement</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('payment-mandate.lists') }}">Payment Mandates</a></li>
                    <li class="breadcrumb-item active">Evidence of Aggrement</li>
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

                        <div class="stepwizard-step bg-success">
                            <a href="#step-2" type="button" class="btn btn-success btn-circle text-white"
                                disabled="disabled">2</a>
                            <span class="text-white">Customer Deduction Information</span>
                        </div>

                        <div class="stepwizard-step bg-primary">
                                <a href="#step-3" type="button" class="btn btn-secondary btn-circle"
                                    disabled="disabled">3</a>
                                <span class="text-white">Evidence of Agrement</span>
                            </div>

                         <div class="stepwizard-step">
                                <a href="#step-4" type="button" class="btn btn-default btn-circle"
                                    disabled="disabled">4</a>
                                <span>Deduction Confirmation</span>
                        </div>
                    </div>
                </div>
                <!--./form wizard -->

                @if (session()->get('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div><br />
                @elseif(session()->get('danger'))
                    <div class="alert alert-danger">
                        {{ session()->get('danger') }}
                    </div><br />
                @endif

                {{ Form::open(['url' => route('payment-mandate.verify-evidence'), 'class' => 'form-horizontal']) }}
                @method('POST')
                {{ Form::token() }}

                <input type="hidden" name="debit_order_id" value="{{ $debit_order->id ?? null }}">
                <input type="hidden" name="debit_order_uuid" value="{{ $debit_order->uuid ?? null }}">

                <!-- Customer Information -->
                <section>
                    <div class="row">
                        <div class="offset-4 col-lg-4 col-md-4">
                            <div class="mb-3">
                                <label>OTP PIN <span class="text-danger">*</span></label>
                                {{ Form::number('otp_code', old('otp_code'), ['class="form-control"', 'placeholder="Write otp code..."']) }}
                                <span class="text-danger">{{ $errors->first('otp_code') }}</span>
                            </div>
                        </div>
                        <!--./col -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="offset-4 col-lg-4 col-md-4">
                            <div class="mb-3 text-sm-end d-none d-sm-block">
                                <a href="{{ route('payment-mandate.create-deduction',[$debit_order->customerAccount->uuid, $debit_order->uuid]) }}"
                                    class="btn btn-outline-danger text-medium">Previous</a>

                                <button type="submit" class="btn btn-primary">Next</button>
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
                    partner_id: "{{ $debit_order->partner_id }}",
                    total_amount: "{{ number_format($debit_order->total_amount) }}",
                    deduction_amount: '{{ number_format($debit_order->installment_amount) }}',
                    min_installment_amount: '{{ number_format($debit_order->min_installment_amount) }}',
                    max_installment_amount: '{{ number_format($debit_order->max_installment_amount) }}',
                    debit_type: '{{ $debit_order->debit_type }}',
                    number_of_installment: '{{ $debit_order->installment_number }}',
                    periodicity: '{{ $debit_order->periodicity }}',
                    start_date: '{{ $debit_order->start_date }}',
                    deduction_end: '{{ $debit_order->deduction_end_date }}',
                    end_date: '{{ $debit_order->start_date }}',
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
