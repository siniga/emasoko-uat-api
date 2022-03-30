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
                                <a href="#step-1" type="button" class="btn btn-success btn-circle" disabled="disabled">1</a>
                                <span>Deduction Channel</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-secondary btn-circle"
                                    disabled="disabled">2</a>
                                <span>Customer Deduction Details</span>
                            </div>


                        </div>
                    </div>
                    <!--./form wizard -->

                    <form action="#" id="registrationForm" enctype="multipart/form-data" autocomplete="off">
                        <input type="hidden" name="uuid" value="{{ $customer_account->uuid ?? null }}">

                        <!-- Customer Information -->
                        <section>
                            <div class="row ">
                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Loan Amount <span class="text-danger">*</span></label>
                                        {{ Form::text('loan_amount', old('loan_amount', number_format($debit_order->total_amount)), ['class="form-control number-separator"','placeholder="Write total loan amount..."']) }}
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

                                        {{ Form::select('debit_type', $options, old('debit_type', $debit_order->debit_type), ['class' => 'form-control','id' => 'debit_type']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3" id="standard_deduction" style="display: block">
                                    <div class="mb-3">
                                        <label>Deduction Amount <span class="text-danger">*</span></label>
                                        {{ Form::text('installment_amount',old('installment_amount', number_format($debit_order->max_installment_amount)),['class="form-control number-separator"', 'placeholder="Write deduction amount..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-6" id="max_min_deduction" style="display: none">
                                    <div class="mb-3">
                                        <label>Deduction Amount <span class="text-danger">*</span></label>
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    {{ Form::text('min_installment_amount',old('min_installment_amount', number_format($debit_order->min_installment_amount)),['class="form-control number-separator"', 'placeholder="Minimum deduction amount..."']) }}
                                                </div>
                                            </div>
                                            <!--./col -->

                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    {{ Form::text('max_installment_amount',old('max_installment_amount', number_format($debit_order->max_installment_amount)),['class="form-control number-separator"', 'placeholder="Maximum deduction amount..."']) }}
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

                                        {{ Form::select('peridiocity', $options, old('peridiocity', $debit_order->periodicity), ['class' => 'form-control']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Number of deduction <span class="text-danger">*</span></label>
                                        {{ Form::number('number_of_installment', old('number_of_installment', $debit_order->installment_number), ['class="form-control"','placeholder="Write number installment..."','min' => 1]) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Deduction Start Date <span class="text-danger">*</span></label>
                                        {{ Form::date('start_date', old('start_date', $debit_order->start_date), ['class="form-control"']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Deduction End Date <span class="text-danger">*</span></label>
                                        @php
                                            $options = [
                                                'total_loan_covered' => 'Until total loan covered',
                                                'specific_date' => 'Specific date',
                                            ];
                                            $options = ['' => 'Select'] + $options;
                                        @endphp

                                        {{ Form::select('deduction_end_date', $options, old('deduction_end_date', $debit_order->deduction_end_date), ['class' => 'form-control','id' => 'deduction_end_date']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3" id="end_date" style="display: none;">
                                    <div class="mb-3">
                                        <label>End Date <span class="text-danger">*</span></label>
                                        {{ Form::date('end_date', old('end_date', $debit_order->start_date), ['class="form-control"']) }}
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
                                        <a href="{{ url('payment-mandate/create-account/' . $customer_account->uuid) }}"
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
                    loan_amount: {
                        required: true
                    },
                    debit_type: {
                        required: true
                    },
                    peridiocity: {
                        required: true
                    },
                    number_of_installment: {
                        required: true
                    },
                    start_date: {
                        required: true
                    },
                    deduction_end_date: {
                        required: true
                    },
                },
                messages: {

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
                        url: "{{ route('payment-mandate.store-deduction-details') }}",
                        data: data,
                        processData: false,
                        contentType: false,
                        cache: false,
                        timeout: 800000,
                        success: function(data) {
                            $("#btnSubmit").prop("disabled", false);

                            // Deactivate Loading Spinner
                            $('.loading-spinner').toggleClass('active');

                            //redirect to show
                            window.location.href = "/payment-mandate/preview/" + data.uuid
                        },
                        error: function(e) {
                            $("#btnSubmit").prop("disabled", false);
                        }
                    });
                    return false;
                },
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
            deductionEndDate = $('#deduction_end_date').val();

            if (deductionEndDate === 'specific_date')
                $('#end_date').css('display', '');
            else
                $('#end_date').css('display', 'none');

            //on change deduction start and end date
            $('#deduction_end_date').on('change', function(e) {
                deductionEndDate = $(this).val();

                if (deductionEndDate === 'specific_date')
                    $('#end_date').css('display', '');
                else
                    $('#end_date').css('display', 'none');
            });

        });
    </script>
@endsection
