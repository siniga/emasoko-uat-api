<div class="d-flex flex-column justify-content-start">
    @if ($company->partnerStaff->isNotEmpty())
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th width="3%">#</th>
                    <th width="30%">Staff</th>
                    <th width="25%">Email</th>
                    <th width="20%">Phone</th>
                    <th width="20%">Title</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($company->partnerStaff as $val)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $val->name ?? '' }}<br />
                            {{ $val->NIDA_number ?? '' }}
                        </td>
                        <td>{{ $val->email ?? '' }}</td>
                        <td>{{ $val->phone ?? '' }}</td>
                        <td>{{ $val->title ?? '' }}</td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert">No any staff at the moment!</div>
    @endif


    <div id="output" class="alert alert-success" style="display: none;"></div>

    <form id="myform" role="form" autocomplete="off">
        <input type="hidden" name="company_id" value="{{ $company->id }}">
        <input type="hidden" name="key" value="Company_Authorized_Representative">

        <div class="mb-2">
            <label for="businessname">Assessment Verdict <span class="text-danger">*</span></label><br />
            {{ Form::radio('assessment_criteria', 'approved', old('assessment_criteria', $company->partnerApprovals[3]->status == 'approved' ? 'checked' : '')) }}
            <label> Approve</label>&nbsp;&nbsp;

            {{ Form::radio('assessment_criteria', 'rejected', old('assessment_criteria', $company->partnerApprovals[3]->status == 'rejected' ? 'checked' : '')) }}
            <label> Reject</label>&nbsp;&nbsp;
            <br /><span class="form-error"></span>
        </div>

        <div class="mb-2">
            <label for="businessname">Remarks </label>
            {{ Form::textarea('remarks', old('remarks', $company->partnerApprovals[3]->remarks), ['class="form-control"', 'placeholder="Enter remarks here..."', 'rows="2"']) }}
        </div>

        @if ($company->partnerApprovals[3]->status != 'approved')
            <div class="mb-3 text-sm-end d-none d-sm-block">
                <button type='reset' class="btn btn-outline-danger text-medium">Reset</button>
                
                <button type='submit' id="btnSubmit" class="btn btn-primary text-medium">
                    Submit <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
                </button>
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
