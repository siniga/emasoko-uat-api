<div class="d-flex flex-column justify-content-start flex-md-row justify-content-md-between p-2 border-bottom">
    <div class="align-self-center">
        <button type="button" id="assess-company-information"
            class="btn btn-outline-dark waves-effect text-medium btn-sm" company-id="{{ $company->id }}"> Company
            Information
        </button>

        <button type="button" id="company-documents" class="btn btn-outline-dark waves-effect text-medium btn-sm"
            company-id="{{ $company->id }}">Company Documents
        </button>

        <button type="button" id="collection-accounts" class="btn btn-info waves-effect text-medium btn-sm"
            company-id="{{ $company->id }}">Collection Accounts
        </button>

        <button type="button" id="authorized-representative" class="btn btn-outline-dark waves-effect text-medium btn-sm"
            company-id="{{ $company->id }}">Authorized Representative
        </button>

        <button type="button" id="confirm-assessment" class="btn btn-outline-dark waves-effect text-medium btn-sm"
            company-id="{{ $company->id }}">Confirmation
        </button>
    </div>

    <div class="align-self-center">
        <button type="button" class="btn btn-outline-danger waves-effect text-medium btn-sm" id="close-offcanvas"><i
                class="bx bx-right-arrow-alt"></i></button>
    </div>
</div>
<!--./buttons -->


<div class="d-flex flex-column justify-content-start mt-4">
    <h5 class="text-uppercase">Company Assessment</h5>

    @if ($company->partnerAccounts->isNotEmpty())
        <table class="table table-nowrap align-middle table-bordered mb-4">
            <thead>
                <tr>
                    <th width="3%">#</th>
                    <th width="20%">Account Type</th>
                    <th width="20%">Bank/MNO Name</th>
                    <th width="25%">Account Name</th>
                    <th width="20%">Account Number</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($company->partnerAccounts as $val)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $val->accountType->name ?? '' }}</td>
                        <td>{{ $val->account->name ?? '' }}</td>
                        <td>{{ $val->account_name ?? '' }}</td>
                        <td>{{ $val->account_number ?? '' }}</td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert">No any collection account at the moment!</div>
    @endif

    <div id="output" class="alert alert-success" style="display: none;"></div>

    <form id="myform" role="form" autocomplete="off">
        <input type="hidden" name="company_id" value="{{ $company->id }}">
        <input type="hidden" name="key" value="Company_Collection_Accounts">

        <div class="mb-2">
            <label for="businessname">Assessment Verdict <span class="text-danger">*</span></label><br />
            {{ Form::radio('assessment_criteria','approved',old('assessment_criteria', $company->partnerApprovals[2]->status == 'approved' ? 'checked' : '')) }}
            <label> Approve</label>&nbsp;&nbsp;

            {{ Form::radio('assessment_criteria','rejected',old('assessment_criteria', $company->partnerApprovals[2]->status == 'rejected' ? 'checked' : '')) }}
            <label> Reject</label>&nbsp;&nbsp;
            <br /><span class="form-error"></span>
        </div>

        <div class="mb-2">
            <label for="businessname">Remarks </label>
            {{ Form::textarea('remarks', old('remarks', $company->partnerApprovals[2]->remarks), ['class="form-control"','placeholder="Enter remarks here..."','rows="2"']) }}
        </div>

        @if ($company->status != 3)
            <div class="mb-3 text-sm-end d-none d-sm-block">
                <button type='submit' id="btnSubmit" class="btn btn-primary text-medium">
                    Submit <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
                </button>
                <button type='reset' class="btn btn-danger text-medium">Reset</button>
            </div>
        @endif
    </form>
</div>
<!--./flex -->

<script type="text/javascript">
    $(document).ready(function() {
        //validate form and submit
        $("#myform").validate({
            errorLabelContainer: ".form-error",
            // errorPlacement: function($error, $element) {
            //     $error.appendTo($element.closest(".form-error"));
            // },
            rules: {
                "assessment_criteria": {
                    required: true,
                    minlength: 1
                }
            },
            messages: {
                "assessment_criteria": {
                    required: "Assessment criteria required",
                    minlength: "You must check at least 1 criteria",
                }
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
                    url: "{{ url('companies/store-assessment') }}",
                    data: data,
                    processData: false,
                    contentType: false,
                    cache: false,
                    timeout: 800000,
                    success: function(data) {
                        $('#output').css('display', '');
                        $("#output").text(data.success_msg);
                        $("#btnSubmit").prop("disabled", false);

                        // Deactivate Loading Spinner
                        $('.loading-spinner').toggleClass('active');
                    },
                    error: function(e) {
                        $('#output').css('display', '');
                        $("#output").text(e.responseText);
                        $("#btnSubmit").prop("disabled", false);
                    }
                });
                return false;
            },
        });
    });
</script>
