<div class="d-flex flex-column justify-content-start flex-md-row justify-content-md-between p-2 border-bottom">
    <div class="align-self-center">
        {{-- <button type="button" id="assess-company-information"
            class="btn btn-outline-dark waves-effect text-medium btn-sm" company-id="{{ $company->id }}"> Company
            Information
        </button> --}}
    </div>

    <div class="align-self-center">
        <button type="button" class="btn btn-outline-danger waves-effect text-medium btn-sm" id="close-offcanvas"><i
                class="bx bx-right-arrow-alt"></i></button>
    </div>
</div>
<!--./buttons -->


<div class="d-flex flex-column justify-content-start mt-4">
    <h5 class="text-uppercase">Send Remainder</h5>
    <div id="output" class="alert alert-success" style="display: none;"></div>

    <form id="myform" role="form" autocomplete="off">
        <div class="row">
            <div class="col-lg-4">
                <div class="mb-3">
                    <label>Name of Account Holder <span class="text-danger">*</span></label>
                    {{ Form::text('account_name', old('account_name', $debit_order->customerAccount->account_name ?? ''), ['class="form-control"','placeholder="Enter name of account holder..."','readonly']) }}
                    <span class="text-danger"></span>
                </div>
            </div>
            <!--./col -->

            <div class="col-lg-4">
                <div class="mb-3">
                    <label for="basicpill-namecard-input">Account Number <span
                            class="text-danger">*</span></label>
                    {{ Form::text('account_number', old('account_number', $debit_order->customerAccount->account_number ?? ''), ['class="form-control"','placeholder="Enter account number..."', 'readonly']) }}
                    <span class="text-danger"></span>
                </div>
            </div>
            <!--./col -->

            <div class="col-lg-4 account_phone">
                <div class="mb-3">
                    <label for="basicpill-namecard-input">Phone <span
                            class="text-danger">*</span></label>
                    {{ Form::text('phone', old('phone', $debit_order->customerAccount->phone ?? ''), ['class="form-control input-mask"','placeholder="Enter phone connected to the bank account..."','data-inputmask' => "'mask': '9999999999'", 'readonly']) }}
                    <span class="text-danger" id="phoneError"></span>
                </div>
            </div>
            <!--./col -->

        </div>
        <!--./row -->

        <div class="row">
            <div class="col-lg-12">
                <div class="mb-3">
                    <label>Message <span class="text-danger">*</span></label>
                    {{ Form::textarea('message', old('message'), ['class="form-control"','placeholder="Write message here..."', 'rows="3"','required']) }}
                    <span class="text-danger"></span>
                </div>
            </div>
            <!--./col -->
        </div>
        <!--./row -->


        <div class="mb-3 text-sm-end d-none d-sm-block">
            <button type='submit' id="btnSubmit" class="btn btn-primary text-medium">
                Send <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
            </button>
            <button type='reset' class="btn btn-danger text-medium">Reset</button>
        </div>
    </form>
</div>
<!--./flex -->

<script type="text/javascript">
    // $(document).ready(function() {
    //     //validate form and submit
    //     $("#myform").validate({
    //         errorLabelContainer: ".form-error",
    //         // errorPlacement: function($error, $element) {
    //         //     $error.appendTo($element.closest(".form-error"));
    //         // },
    //         rules: {
    //             "assessment_criteria": {
    //                 required: true,
    //                 minlength: 1
    //             }
    //         },
    //         messages: {
    //             "assessment_criteria": {
    //                 required: "Assessment criteria required",
    //                 minlength: "You must check at least 1 criteria",
    //             }
    //         },
    //         submitHandler: function(form, e) {
    //             e.preventDefault();

    //             // Activate the loading spinner
    //             $('.loading-spinner').toggleClass('active');

    //             // Get form
    //             var form = $(form)[0];

    //             // FormData object 
    //             var data = new FormData(form);

    //             //append token
    //             data.append("_token", "{{ csrf_token() }}");

    //             // disabled the submit button
    //             $("#btnSubmit").prop("disabled", true);

    //             //post data 
    //             $.ajax({
    //                 type: "POST",
    //                 url: "{{ url('companies/store-assessment') }}",
    //                 data: data,
    //                 processData: false,
    //                 contentType: false,
    //                 cache: false,
    //                 timeout: 800000,
    //                 success: function(data) {
    //                     $('#output').css('display', '');
    //                     $("#output").text(data.success_msg);
    //                     $("#btnSubmit").prop("disabled", false);

    //                     // Deactivate Loading Spinner
    //                     $('.loading-spinner').toggleClass('active');
    //                 },
    //                 error: function(e) {
    //                     $('#output').css('display', '');
    //                     $("#output").text(e.responseText);
    //                     $("#btnSubmit").prop("disabled", false);
    //                 }
    //             });
    //             return false;
    //         },
    //     });
    // });
</script>
