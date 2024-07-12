<div class="d-flex flex-column justify-content-start">
    @if ($company->partnerDocuments->isNotEmpty())
        <table class="table align-middle table-bordered mb-4">
            <thead>
                <tr>
                    <th width="3%">#</th>
                    <th width="60%">Name</th>
                    <th width="30%">Attachment</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($company->partnerDocuments as $val)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $val->name }}</td>
                        <td>
                            @if ($val->attachment != null)
                                <a href="{{ asset('assets/uploads/docs/' . $val->attachment) }}">
                                    {{ $val->attachment }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert">No any document at the moment!</div>
    @endif

    <div id="output" class="alert alert-success" style="display: none;"></div>

    <form id="myform" role="form" autocomplete="off">
        <input type="hidden" name="company_id" value="{{ $company->id }}">
        <input type="hidden" name="key" value="Company_Documents">

        <div class="mb-2">
            <label for="businessname">Assessment Verdict <span class="text-danger">*</span></label><br />
            {{ Form::radio('assessment_criteria', 'approved', old('assessment_criteria', $company->partnerApprovals[1]->status == 'approved' ? 'checked' : '')) }}
            <label> Approve</label>&nbsp;&nbsp;

            {{ Form::radio('assessment_criteria', 'rejected', old('assessment_criteria', $company->partnerApprovals[1]->status == 'rejected' ? 'checked' : '')) }}
            <label> Reject</label>&nbsp;&nbsp;
            <br /><span class="form-error"></span>
        </div>

        <div class="mb-2">
            <label for="businessname">Remarks </label>
            {{ Form::textarea('remarks', old('remarks', $company->partnerApprovals[1]->remarks), ['class="form-control"', 'placeholder="Enter remarks here..."', 'rows="2"']) }}
        </div>

        @if ($company->partnerApprovals[1]->status != 'approved')
            <div class="mb-3 text-sm-end d-none d-sm-block">
                <button type='reset' class="btn btn-outline-danger text-medium">Reset</button>

                <button type='submit' id="btnSubmit" class="btn btn-primary text-medium">
                    Submit <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
                </button>

            </div>
        @endif
    </form>
</div>


<script type="text/javascript">
    $(document).ready(function() {
        //validate form and submit
        $("#myform").validate({
            errorLabelContainer: ".form-error",
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

                        //add class
                        $("#company-documents").addClass(
                            "btn btn-success waves-effect text-medium btn-sm");

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
