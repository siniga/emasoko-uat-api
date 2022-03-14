@extends('layouts.master')

@section('title')
    Payment Mandate
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
                                <a href="#step-1" type="button" class="btn btn-success btn-circle">1</a>
                                <span class="text-primary">Customer Information</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-success btn-circle" disabled="disabled">2</a>
                                <span>Deduction Channel</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-success btn-circle" disabled="disabled">3</a>
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
                                <div class="col-lg-12">
                                    <h5 class="text-capitalize">Customer Information</h5>
                                    <hr />
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-lg-12">
                                    <table class="table table-stripped table-bordered">
                                        <tr>
                                            <th width=25%>Fullname</th>
                                            <td width=25%>
                                                <span>{{ $customer->full_name }}</span>
                                            </td>

                                            <th>Status</th>
                                            <td>
                                                <span>{{ ucfirst($customer->status) }}</span>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th width=25%>Date of Birth</th>
                                            <td width=25%>
                                                <span>{{ $customer->dob }}</span>
                                            </td>

                                            <th>NIDA Number</th>
                                            <td>
                                                <span>{{ $customer->id_number ?? 'NIL' }}</span>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th width=25%>Marital status</th>
                                            <td width=25%>
                                                <span>{{ $customer->maritalStatus->name ?? 'NIL' }}</span>
                                            </td>

                                            <th>Gender</th>
                                            <td>
                                                <span>{{ $customer->gender->name ?? 'NIL' }}</span>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th width=25%>Sector</th>
                                            <td width=25%>
                                                <span>{{ $customer->sector->name ?? 'NIL' }}</span>
                                            </td>

                                            <th>Occupation</th>
                                            <td>
                                                <span>{{ $customer->occupation->name ?? 'NIL' }}</span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row-->

                            <div class="row mt-4">
                                <div class="col-lg-12">
                                    <h5 class="text-capitalize">Deduction Channel</h5>
                                    <hr />
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-lg-12">
                                    <table class="table table-stripped table-bordered">
                                        <tr>
                                            <th width=25%>Payment Channel</th>
                                            <td width=25%>
                                                <span>{{ $customer->customerAccount->accountType->name ?? '' }}</span>
                                            </td>

                                            <th>Bank/Institution</th>
                                            <td>
                                                <span>{{ $customer->customerAccount->account->name ?? '' }}</span>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th width=25%>Account Name</th>
                                            <td width=25%>
                                                <span>{{ $customer->customerAccount->account_name ?? '' }}</span>
                                            </td>

                                            <th>Account Number</th>
                                            <td>
                                                <span>{{ $customer->customerAccount->account_number ?? '' }}</span>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th>Phone <small>(Linked to Bank Account)</small></th>
                                            <td colspan="4">
                                                <span>{{ $customer->customerAccount->phone ?? '' }}</span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row mt-4">
                                <div class="col-lg-12">
                                    <h5 class="text-capitalize">Customer Deduction Details</h5>
                                    <hr />
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-lg-12">
                                    @if ($customer->customerAccount->debitOrders)
                                        <table class="table table-bordered" id="dynamicTable">
                                            <tr>
                                                <th width="3%">#</th>
                                                <th width="12%">Loan Amount</th>
                                                <th width="10%">Debit Type</th>
                                                <th width="10%">Installment Amount</th>
                                                <th width="10%">Peridiocity</th>
                                                <th width="10%">Number of Deduction</th>
                                                <th width="12%">Start Date</th>
                                                <th width="12%">End Date</th>
                                                <th width="15%">Evidence of Aggrement</th>
                                            </tr>

                                            @foreach ($customer->customerAccount->debitOrders as $val)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ number_format($val->total_amount) ?? '' }}</td>
                                                    <td>{{ $val->debit_type ?? '' }}</td>
                                                    <td>{{ number_format($val->max_installment_amount) ?? '' }}</td>
                                                    <td>{{ $val->periodicity }}</td>
                                                    <td>{{ $val->installment_number ?? '' }}</td>
                                                    <td>{{ date('d-m-Y', strtotime($val->start_date)) }}</td>
                                                    <td>{{ date('d-m-Y', strtotime($val->end_date)) }}</td>
                                                    <td>
                                                        @if ($val->evidence_of_aggrement != null)
                                                            <a download=""
                                                                href="{{ asset('assets/uploads/docs' . $val->evidence_of_aggrement) }}">
                                                                {{ $val->evidence_of_aggrement }}
                                                            </a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    @endif
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            @if ($customer->status == 'inactive')
                                <div class="row mt-2" id="btn-section" style="display: block;">
                                    <div class="col-md-12">
                                        <div class="mb-3 text-sm-end d-none d-sm-block">
                                            <a href="#" class="btn btn-danger text-medium">Cancel</a>

                                            <a href="{{ url('customers/register/' . $customer->uuid) }}"
                                                class="btn btn-info text-medium">Edit</a>

                                            <button type="submit" id="btnSave" name="save" class="btn btn-secondary">
                                                Save <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
                                            </button>

                                            <button type="submit" id="btnSubmit" name="submit" class="btn btn-success">
                                                Save & Submit <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <!--./col -->
                                </div>
                                <!--./row -->
                            @endif
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
                        url: "{{ route('customers.confirm-registration') }}",
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

                            if (data.action == 'save') {
                                //hide and show sections
                                $('#btn-section').css('display', '');
                                $("#success-msg").text(data.success_msg);
                            } else {
                                //hide and show sections
                                $('#btn-section').css('display', 'none');
                                $("#success-msg").text(data.success_msg);
                            }
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
                e.preventDefault();

                //redirect to show
                window.location.href = "/customers/register"
            });


            //type of debit
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

            //deduction start and end date
            $('#deduction_end_date').on('change', function(e) {
                deductionEndDate = $(this).val();

                if (deductionEndDate === 'specific_date')
                    $('#end_date').css('display', '');
                else
                    $('#end_date').css('display', 'none');
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
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
