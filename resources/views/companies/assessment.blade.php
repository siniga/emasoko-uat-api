<div class="d-flex flex-column justify-content-start flex-md-row justify-content-md-between">

    <div class="align-self-center">
        <h5 class="text-uppercase font-weight-600 pt-2">Company Assessment</h5>
    </div>

    <div class="align-self-center">
        <button type="button" class="btn btn-sm btn-outline-danger waves-effect" id="close-offcanvas"><i
                class="bx bx-right-arrow-alt"></i></button>
    </div>
</div>


<div class="mt-4">
    <!--./output -->
    <div id="output" class="alert alert-success" style="display: none;"></div>

    <form id="myform" role="form" autocomplete="off">
        <input type="hidden" name="company_id" value="{{ $company->id }}">

        <div class="mb-3">
            <label>Assessment Criteria <span class="text-danger">*</span></label><br />

            @php
                $arr_assessment_criteria = [
                    'Company_Information' => 'Company Information',
                    'Company_Documents' => 'Company Documents',
                    'Company_Collection_Accounts' => 'Company Collection Accounts',
                    'Company_Authorized_Representative' => 'Company Authorized Representative',
                ];
            @endphp

            <div class="checkbox">
                @foreach ($company->partnerApprovals as $val)
                    {{ Form::checkbox('assessment_criteria[]',$val->key,old('assessment_criteria[]', $val->active == 1 ? 'checked' : '')) }}
                    <label> {{ $val->label }}</label>&nbsp;&nbsp;<br />
                @endforeach
            </div>
            <span class="form-error"></span>
        </div>

        <div class="mb-3">
            <button type='submit' id="btnSubmit" class="btn btn-primary text-medium">
                Submit <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
            </button>
            <button type='reset' class="btn btn-danger text-medium">Reset</button>
        </div>
    </form>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        //validate form and submit
        $("#myform").validate({
            errorLabelContainer: ".form-error",
            // errorPlacement: function($error, $element) {
            //     $error.appendTo($element.closest(".form-error"));
            // },
            rules: {
                "assessment_criteria[]": {
                    required: true,
                    minlength: 1
                }
            },
            messages: {
                "assessment_criteria[]": {
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
