@extends('layouts.master')

@section('title')
    Edit Payment Mandate
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Payment Mandate
        @endslot
        @slot('title')
            Edit Payment Mandate
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="#" id="registrationForm" enctype="multipart/form-data" autocomplete="off">
                        <input type="hidden" name="customer_id" value="{{ $customer->id ?? null }}">
                        <input type="hidden" name="customer_uuid" value="{{ $customer->uuid ?? null }}">

                        <!-- Customer Information -->
                        <section>
                            <div class="row mb-4">
                                <div class="col-lg-12">
                                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                                        <b>Note: </b> Register first name, middle name and surname as appear in bank
                                        account!
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

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
                                        {{ Form::text('first_name', old('first_name', $customer->first_name ?? ''), ['class="form-control"','placeholder="Write first name..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        <label for="businessname">Middle Name
                                        </label>
                                        {{ Form::text('middle_name', old('middle_name', $customer->middle_name ?? ''), ['class="form-control"','placeholder="Write middle name..."']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="businessname">Surname <span class="text-danger">*</span>
                                        </label>
                                        {{ Form::text('surname', old('surname', $customer->last_name ?? ''), ['class="form-control"','placeholder="Write surname..."']) }}
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
                                        {{ Form::text('nida_number', old('nida_number', $customer->id_number ?? ''), ['class="form-control input-mask"','placeholder="Write NIDA number..."','data-inputmask' => "'mask': '99999999-99999-99999-99'"]) }}
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

                            <div class="row mt-4">
                                <div class="col-lg-12">
                                    <h5 class="text-capitalize">Deduction Channel</h5>
                                    <hr />
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

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

                                        {{ Form::select('account_type_id',$options,old('account_type_id', $customer->customerAccount->account_type_id ?? ''),['class' => 'form-control', 'id' => 'account_type_id']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Bank/Institution <span
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

                                        {{ Form::select('account_id', $options, old('account_id', $customer->customerAccount->account_id ?? ''), ['class' => 'form-control','id' => 'account_id']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label>Account Name <span class="text-danger">*</span></label>
                                        {{ Form::text('account_name', old('account_name', $customer->customerAccount->account_name ?? ''), ['class="form-control"','placeholder="Write account name..."','required']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Account Number <span
                                                class="text-danger">*</span></label>
                                        {{ Form::text('account_number', old('account_number', $customer->customerAccount->account_number ?? ''), ['class="form-control"','placeholder="Write account number..."']) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-4" id="account_phone" style="display: block;">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Phone <small>(Linked to Bank
                                                Account)</small><span class="text-danger">*</span></label>
                                        {{ Form::text('phone', old('phone', $customer->customerAccount->phone ?? ''), ['class="form-control input-mask"','placeholder="Write phone..."','data-inputmask' => "'mask': '9999999999'"]) }}
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3 text-sm-end d-none d-sm-block">
                                        <a href="{{ route('customers.lists') }}"
                                            class="btn btn-danger text-medium ">Cancel</a>
                                        <button type="submit" id="btnSubmit" class="btn btn-primary">
                                            Update <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
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
                    },
                    account_type_id: {
                        required: true
                    },
                    account_id: {
                        required: true
                    },
                    account_name: {
                        required: true
                    },
                    account_number: {
                        required: true
                    },
                    phone: {
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
                    year: {
                        required: "Year required"
                    },
                    month: {
                        required: "Month required"
                    },
                    day: {
                        required: "Day required"
                    },
                    account_type_id: {
                        required: "Payment channel required"
                    },
                    account_id: {
                        required: "Bank/Institution required"
                    },
                    account_name: {
                        required: "Account name required"
                    },
                    account_number: {
                        required: "Account number required"
                    },
                    phone: {
                        required: "Phone required"
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
                        url: "{{ route('customers.update') }}",
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

                            //success message
                            $("#success-msg").text(data.success_msg);
                            $('#subscribeModal').modal('show');
                        },
                        error: function(e) {
                            $("#btnSubmit").prop("disabled", false);
                        }
                    });
                    return false;
                },
            });

            //btn close
            $('.btn-close').on('click', function(e) {
                uuid = $(this).attr('customer-uuid');
                e.preventDefault();
                //location.reload(); //reload page
                

                //redirect to show
                window.location.href = "/customers/details/" + uuid
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

<!-- subscribeModal -->
<div class="modal fade" id="subscribeModal" tabindex="-1" aria-labelledby="subscribeModalLabel" aria-hidden="true"
    data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                    customer-uuid={{ $customer->uuid }}></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="avatar-md mx-auto mb-4">
                        <div class="avatar-title bg-light rounded-circle text-success h1">
                            <i class="mdi mdi-check"></i>
                        </div>
                    </div>

                    <div class="row justify-content-center">
                        <div class="col-xl-10">
                            <p class="text-muted font-size-14 mb-4" id="success-msg"></p>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- end modal -->
