@extends('layouts.master')

@section('title')
    Register Customer
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Customers
        @endslot
        @slot('title')
            Register Customer
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="#" id="registrationForm" enctype="multipart/form-data" autocomplete="off">

                        <!-- Customer Information -->
                        <section>
                            <div class="row">
                                <div class="col-lg-12">
                                    <h5 class="text-capitalize">Customer Information</h5>
                                    <hr />
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label for="businessname">First Name <span class="text-danger">*</span>
                                        </label>
                                        {{ Form::text('first_name', old('first_name'), ['class="form-control"', 'placeholder="Write first name..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        <label for="businessname">Middle Name
                                        </label>
                                        {{ Form::text('middle_name', old('middle_name'), ['class="form-control"','placeholder="Write middle name..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="businessname">Surname <span class="text-danger">*</span>
                                        </label>
                                        {{ Form::text('surname', old('surname'), ['class="form-control"', 'placeholder="Write surname..."']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label>NIDA Number <span class="text-danger">*</span></label>
                                        {{ Form::text('nida_number', old('nida_number'), ['class="form-control input-mask"','placeholder="Write NIDA number..."','data-inputmask' => "'mask': '99999999-99999-99999-99'"]) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label>Date of Birth <span style="color: red;">*</span></label>
                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    @php
                                                        $year_options = [];
                                                        $current_year = date('Y');
                                                    @endphp

                                                    @foreach (range(1940, $current_year - 15) as $v)
                                                        @php $year_options[$v] = $v; @endphp
                                                    @endforeach

                                                    @php $year_options = ['' => 'Year'] + $year_options; @endphp
                                                    {{ Form::select('year', $year_options, old('year'), ['class' => 'form-control']) }}
                                                </div>
                                            </div>
                                            <!--./col-lg-4 -->

                                            <div class="col-lg-4">
                                                <div class="mb-3">
                                                    @php $month_options = []; @endphp

                                                    @foreach (range(1, 12) as $v)
                                                        @php
                                                            $month_name = date('F', mktime(0, 0, 0, $v, 10)); // convert number to month
                                                            $month_options[$v] = $month_name;
                                                        @endphp
                                                    @endforeach
                                                    @php $month_options = ['' => 'Month'] + $month_options; @endphp
                                                    {{ Form::select('month', $month_options, old('month'), ['class' => 'form-control']) }}
                                                </div>
                                            </div>
                                            <!--./col-lg-4 -->

                                            <div class="col-lg-4">
                                                <div class="mb-3">
                                                    <?php
                                                    $day_options = [];
                                                    $number_of_days = date('t');
                                                    
                                                    foreach (range(1, 31) as $v) {
                                                        $day_options[$v] = $v;
                                                    }
                                                    $day_options = ['' => 'Day'] + $day_options;
                                                    ?>
                                                    {{ Form::select('day', $day_options, old('day'), ['class' => 'form-control']) }}
                                                </div>
                                            </div>
                                            <!--./col-lg-4 -->
                                        </div>
                                        <!--./row -->
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Gender <span style="color: red;">*</span></label>
                                        @php
                                            $options = [];
                                        @endphp

                                        @foreach ($genders as $item)
                                            @php
                                                $options[$item->id] = $item->name;
                                            @endphp
                                        @endforeach

                                        @php
                                            $options = ['' => 'Select'] + $options;
                                        @endphp
                                        {{ Form::select('gender_id', $options, old('gender_id'), ['class' => 'form-control']) }}
                                        <span class="text-danger">{{ $errors->first('gender_id') }}</span>
                                    </div>
                                </div>
                                <!--./col-lg-3 -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Marital Status <span style="color: red;">*</span></label>
                                        @php
                                            $options = [];
                                        @endphp

                                        @foreach ($marital_status as $item)
                                            @php
                                                $options[$item->id] = $item->name;
                                            @endphp
                                        @endforeach

                                        @php
                                            $options = ['' => 'Select'] + $options;
                                        @endphp
                                        {{ Form::select('marital_status_id', $options, old('marital_status_id'), ['class' => 'form-control']) }}
                                        <span class="text-danger">{{ $errors->first('marital_status_id') }}</span>
                                    </div>
                                </div>
                                <!--./col-lg-3 -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Sector <span style="color: red;">*</span></label>
                                        @php
                                            $options = [];
                                        @endphp

                                        @foreach ($sectors as $item)
                                            @php
                                                $options[$item->id] = $item->name;
                                            @endphp
                                        @endforeach

                                        @php
                                            $options = ['' => 'Select'] + $options;
                                        @endphp
                                        {{ Form::select('sector_id', $options, old('sector_id'), ['class' => 'form-control']) }}
                                        <span class="text-danger">{{ $errors->first('sector_id') }}</span>
                                    </div>
                                </div>
                                <!--./col-lg-3 -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Occupation <span style="color: red;">*</span></label>
                                        @php
                                            $options = [];
                                        @endphp

                                        @foreach ($occupations as $item)
                                            @php
                                                $options[$item->id] = $item->name;
                                            @endphp
                                        @endforeach

                                        @php
                                            $options = ['' => 'Select'] + $options;
                                        @endphp
                                        {{ Form::select('occupation_id', $options, old('occupation_id'), ['class' => 'form-control']) }}
                                        <span class="text-danger">{{ $errors->first('occupation_id') }}</span>
                                    </div>
                                </div>
                                <!--./col-lg-3 -->
                            </div>
                            <!--./row -->


                            <div class="row">
                                <div class="col-lg-12">
                                    <h5 class="text-capitalize">Bank Details</h5>
                                    <hr />
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row ">
                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Account Category <span
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

                                        {{ Form::select('account_type_id', $options, old('account_type_id'), ['class' => 'form-control','id' => 'account_type_id']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Account Type <span
                                                class="text-danger">*</span></label>
                                        @php $options = []; @endphp
                                        @foreach ($accounts as $val)
                                            @php
                                                $options[$val->id] = $val->name;
                                            @endphp
                                        @endforeach
                                        @php
                                            $account_options = $options;
                                            $options = ['' => 'Select'] + $options;
                                            
                                        @endphp

                                        {{ Form::select('account_id', $options, old('account_id'), ['class' => 'form-control', 'id' => 'account_id']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Account Number <span
                                                class="text-danger">*</span></label>
                                        {{ Form::text('account_number', old('account_number'), ['class="form-control"','placeholder="Write account number..."']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3" id="account_phone" style="display: block;">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Phone </label>
                                        {{ Form::text('phone', old('phone'), ['class="form-control input-mask"','placeholder="Write phone..."','data-inputmask' => "'mask': '9999999999'"]) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->


                            <div class="row">
                                <div class="col-lg-12">
                                    <h5 class="text-capitalize">Loan Details</h5>
                                    <hr />
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row ">
                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Loan Purpose <span class="text-danger">*</span></label>
                                        @php
                                            $options = [];
                                        @endphp
                                        @foreach ($loan_purposes as $val)
                                            @php
                                                $options[$val->id] = $val->name;
                                            @endphp
                                        @endforeach
                                        @php
                                            $options = ['' => 'Select'] + $options;
                                        @endphp

                                        {{ Form::select('loan_purpose_id', $options, old('loan_purpose_id'), ['class' => 'form-control']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Loan Amount <span class="text-danger">*</span></label>
                                        {{ Form::text('loan_amount', old('loan_amount'), ['class="form-control"', 'id="number_comma"','placeholder="Write total loan amount..."']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Installment Amount <span class="text-danger">*</span></label>
                                        {{ Form::text('installment_amount', old('installment_amount'), ['class="form-control"' , 'id="number_comma2"' ,'placeholder="Write installment amount..."']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Frequency of Installment <span class="text-danger">*</span></label>
                                        @php
                                            $options = [
                                                'ANNUALLY' => 'Annually',
                                                'MONTHLY' => 'Monthly',
                                                'WEEKLY' => 'Weekly',
                                                'DAILY' => 'Daily',
                                            ];
                                            $options = ['' => 'Select'] + $options;
                                        @endphp

                                        {{ Form::select('peridiocity', $options, old('peridiocity'), ['class' => 'form-control']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row ">
                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Number of installment <span class="text-danger">*</span></label>
                                        {{ Form::number('number_of_installment', old('number_of_installment'), ['class="form-control"','placeholder="Write number installment..."','min' => 1]) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Start Date <span class="text-danger">*</span></label>
                                        {{ Form::date('start_date', old('start_date'), ['class="form-control"']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label for="formFile" class="form-label">Evidence of Aggrement</label>
                                        <input class="form-control" type="file" id="formFile"
                                            name="evidence_of_aggrement" />
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3 text-sm-end d-none d-sm-block">
                                        <button type="reset" class="btn btn-danger text-medium ">Reset</button>
                                        <button type="submit" id="btnSubmit" class="btn btn-primary">
                                            Submit <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
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
                    first_name: {
                        required: true
                    },
                    surname: {
                        required: true
                    },
                    nida_number: {
                        required: true
                    },
                    year: {
                        required: true
                    },
                    month: {
                        required: true
                    },
                    day: {
                        required: true
                    },
                    gender_id: {
                        required: true
                    },
                    marital_status_id: {
                        required: true
                    },
                    sector_id: {
                        required: true
                    },
                    occupation_id: {
                        required: true
                    },
                    account_type_id: {
                        required: true
                    },
                    account_id: {
                        required: true
                    },
                    account_number: {
                        required: true
                    },
                    loan_purpose_id: {
                        required: true
                    },
                    loan_amount: {
                        required: true
                    },
                    installment_amount: {
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
                },
                messages: {
                    first_name: {
                        required: "First name required"
                    },
                    surname: {
                        required: "Surname required"
                    },
                    nida_number: {
                        required: "NIDA Number required"
                    },
                    year: {
                        required: "Year required"
                    },
                    month: {
                        required: "Month required"
                    },
                    day: {
                        required: "Day required"
                    },
                    gender_id: {
                        required: "Gender required"
                    },
                    marital_status_id: {
                        required: "Marital status required"
                    },
                    sector_id: {
                        required: "Sector required"
                    },
                    occupation_id: {
                        required: "Occupation required"
                    },
                    account_type_id: {
                        required: "Account category required"
                    },
                    account_id: {
                        required: "Account type required"
                    },
                    account_number: {
                        required: "Account number required"
                    },
                    loan_purpose_id: {
                        required: "Loan purpose required"
                    },
                    loan_amount: {
                        required: "Total loan amount required"
                    },
                    installment_amount: {
                        required: "Installment amount required"
                    },
                    peridiocity: {
                        required: "Frequency of installment required"
                    },
                    number_of_installment: {
                        required: "Number of installment required"
                    },
                    start_date: {
                        required: "Start date of deduction required"
                    },
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
                        url: "{{ route('customers.store') }}",
                        data: data,
                        processData: false,
                        contentType: false,
                        cache: false,
                        timeout: 800000,
                        success: function(data) {
                            $("#btnSubmit").prop("disabled", false);
                            //$(this)[0].reset();

                            // Deactivate Loading Spinner
                            $('.loading-spinner').toggleClass('active');

                            //redirect to show
                            window.location.href = "/customers/details/" + data.uuid
                        },
                        error: function(e) {
                            $("#btnSubmit").prop("disabled", false);
                        }
                    });
                    return false;
                },
            });

            //on select account category
            $('#account_type_id').on('change', function(e) {
                e.preventDefault();
                accountTypeId = $(this).val();

                $.ajax({
                    url: '/account-types/' + accountTypeId,
                    type: "get",
                    success: function(data) {
                        $('#account_id').html(data);
                    }
                });
            });

            //filter
            $('#account_type_id').on('change', function(e) {
                accountTypeId = $(this).val();

                if (accountTypeId == 1)
                    $('#account_phone').css('display', '');
                else
                    $('#account_phone').css('display', 'none');
            });

            //format amount 
            document.getElementById('number_comma').addEventListener('input', event =>
                event.target.value = (parseInt(event.target.value.replace(/[^\d]+/gi, '')) || 0).toLocaleString(
                    'en-US')
            );

            //format amount 
            document.getElementById('number_comma2').addEventListener('input', event =>
                event.target.value = (parseInt(event.target.value.replace(/[^\d]+/gi, '')) || 0).toLocaleString(
                    'en-US')
            );

        });
    </script>
@endsection
