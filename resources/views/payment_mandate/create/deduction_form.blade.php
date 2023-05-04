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
                            <div class="stepwizard-step">
                                <a href="#step-1" type="button" class="btn btn-success btn-circle text-white"
                                    disabled="disabled">1</a>
                                <span class="text-success">Deduction Channel</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-secondary btn-circle"
                                    disabled="disabled">2</a>
                                <span class="text-primary">Customer Deduction Information</span>
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

                    {{ Form::open(['url' => route('payment-mandate.store-deduction'), 'class' => 'form-horizontal', 'enctype' => 'multipart/form-data']) }}
                    @method('POST')
                    {{ Form::token() }}

                    <input type="hidden" name="customer_account_id" value="{{ $customer_account->id ?? null }}">
                    <input type="hidden" name="debit_order_id" value="{{ $debit_order->id ?? null }}">

                    <!-- Customer Information -->
                    <section>
                        <div class="row ">
                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>Loan Amount <span class="text-danger">*</span></label>
                                    {{ Form::text('loan_amount', old('loan_amount', $debit_order ? number_format($debit_order->total_amount) : ''), ['class="form-control number-separator"', 'placeholder="Write total loan amount..."']) }}
                                    <div class="text-danger">{{ $errors->first('loan_amount') }}</div>
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
                                    <div class="text-danger">{{ $errors->first('debit_type') }}</div>
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-3" id="standard_deduction" style="display: block">
                                <div class="mb-3">
                                    <label>Deduction Amount <span class="text-danger">*</span></label>
                                    {{ Form::text('installment_amount', old('installment_amount', $debit_order ? number_format($debit_order->installment_amount) : ''), ['class="form-control number-separator"', 'placeholder="Write deduction amount..."']) }}
                                    <div class="text-danger">{{ $errors->first('installment_amount') }}</div>
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
                                                <div class="text-danger">{{ $errors->first('min_installment_amount') }}</div>
                                            </div>
                                        </div>
                                        <!--./col -->

                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                {{ Form::text('max_installment_amount', old('max_installment_amount', $debit_order ? number_format($debit_order->max_installment_amount) : ''), ['class="form-control number-separator"', 'placeholder="Maximum deduction amount..."', 'id="max_installment_amount"']) }}
                                                <div class="text-danger">{{ $errors->first('max_installment_amount') }}</div>
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
                                    <div class="text-danger">{{ $errors->first('periodicity') }}</div>
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>Number of deduction <span class="text-danger">*</span></label>
                                    {{ Form::number('number_of_installment', old('number_of_installment', $debit_order->installment_number ?? ''), ['class="form-control"', 'placeholder="Write number installment..."', 'min' => 1]) }}
                                    <div class="text-danger">{{ $errors->first('number_of_installment') }}</div>
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label>Deduction Start Date <span class="text-danger">*</span></label>
                                    {{ Form::date('start_date', old('start_date', $debit_order->start_date ?? ''), ['class="form-control"', 'min' => date('Y-m-d')]) }}
                                    <div class="text-danger">{{ $errors->first('start_date') }}</div>
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
                                    <div class="text-danger">{{ $errors->first('deduction_end') }}</div>
                                </div>
                            </div>
                            <!--./col -->

                            <div class="col-lg-3" id="end_date" style="display: none;">
                                <div class="mb-3">
                                    <label>End Date <span class="text-danger">*</span></label>
                                    {{ Form::date('end_date', old('end_date', $debit_order->end_date ?? ''), ['class="form-control"', 'id="end_date"']) }}
                                    <div class="text-danger">{{ $errors->first('end_date') }}</div>
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
                                    <a href="{{ url('payment-mandate/create-account/' . $customer_account->id . '/' . ($debit_order ? $debit_order->id : '')) }}"
                                        class="btn btn-info text-medium">Previous</a>

                                    <button type="submit" class="btn btn-primary">Next</button>
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
