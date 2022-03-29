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
                                <a href="#step-1" type="button" class="btn btn-secondary btn-circle">1</a>
                                <span class="text-primary">Customer Information</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-default btn-circle" disabled="disabled">2</a>
                                <span>Deduction Channel</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-default btn-circle" disabled="disabled">3</a>
                                <span>Customer Deduction Details</span>
                            </div>


                        </div>
                    </div>
                    <!--./form wizard -->

                    <form action="#" id="registrationForm" enctype="multipart/form-data" autocomplete="off">
                        <input type="hidden" name="customer_id" value="{{ $customer->id ?? null }}">
                        <input type="hidden" name="customer_uuid" value="{{ $customer->uuid ?? null }}">

                        <!-- Customer Information -->
                        <section>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label for="businessname">First Name <span class="text-danger">*</span>
                                        </label>
                                        {{ Form::text('first_name', old('first_name', $customer->first_name ?? ''), ['class="form-control"','placeholder="Enter first name as appears in bank account..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        <label for="businessname">Middle Name
                                        </label>
                                        {{ Form::text('middle_name', old('middle_name', $customer->middle_name ?? ''), ['class="form-control"','placeholder="Enter middle name..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="businessname">Surname <span class="text-danger">*</span>
                                        </label>
                                        {{ Form::text('surname', old('surname', $customer->last_name ?? ''), ['class="form-control"','placeholder="Enter surname as appears in bank account..."']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label>NIDA Number</label>
                                        {{ Form::text('nida_number', old('nida_number', $customer->id_number ?? ''), ['class="form-control input-mask"','placeholder="Enter NIDA number..."','data-inputmask' => "'mask': '99999999-99999-99999-99'"]) }}
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
                                                    {{ Form::select('year', $year_options, old('year', $customer ? date('Y', strtotime($customer->dob)) : ''), ['class' => 'form-control']) }}
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
                                                    {{ Form::select('month', $month_options, old('month', $customer ? date('n', strtotime($customer->dob)) : ''), ['class' => 'form-control']) }}
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
                                                    {{ Form::select('day', $day_options, old('day', $customer ? date('j', strtotime($customer->dob)) : ''), ['class' => 'form-control']) }}
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
                                        <label>Gender</label>
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
                                        {{ Form::select('gender_id', $options, old('gender_id', $customer->gender_id ?? ''), ['class' => 'form-control']) }}
                                        <span class="text-danger">{{ $errors->first('gender_id') }}</span>
                                    </div>
                                </div>
                                <!--./col-lg-3 -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Marital Status</label>
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
                                        {{ Form::select('marital_status_id', $options, old('marital_status_id', $customer->marital_status_id ?? ''), ['class' => 'form-control']) }}
                                        <span class="text-danger">{{ $errors->first('marital_status_id') }}</span>
                                    </div>
                                </div>
                                <!--./col-lg-3 -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Sector</label>
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
                                        {{ Form::select('sector_id', $options, old('sector_id', $customer->sector_id ?? ''), ['class' => 'form-control']) }}
                                        <span class="text-danger">{{ $errors->first('sector_id') }}</span>
                                    </div>
                                </div>
                                <!--./col-lg-3 -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label>Occupation</label>
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
                                        {{ Form::select('occupation_id', $options, old('occupation_id', $customer->occupation_id ?? ''), ['class' => 'form-control']) }}
                                        <span class="text-danger">{{ $errors->first('occupation_id') }}</span>
                                    </div>
                                </div>
                                <!--./col-lg-3 -->
                            </div>
                            <!--./row -->


                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3 text-sm-end d-none d-sm-block">
                                        <a href="#" class="btn btn-danger text-medium ">Cancel</a>
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
                    first_name: {
                        required: true
                    },
                    surname: {
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
                    }
                },
                messages: {
                    first_name: {
                        required: "First name required"
                    },
                    surname: {
                        required: "Surname required"
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
                        url: "{{ route('customers.store-details') }}",
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
                            window.location.href = "/customers/payment-details/" + data.uuid
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
