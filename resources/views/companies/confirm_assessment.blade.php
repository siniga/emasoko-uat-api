<div class="d-flex flex-column justify-content-start flex-md-row justify-content-md-between p-2 border-bottom">
    <div class="align-self-center">
        <button type="button" id="assess-company-information"
            class="btn btn-outline-dark waves-effect text-medium btn-sm" company-id="{{ $company->id }}"> Company
            Information
        </button>

        <button type="button" id="company-documents" class="btn btn-outline-dark waves-effect text-medium btn-sm"
            company-id="{{ $company->id }}">Company Documents
        </button>

        <button type="button" id="collection-accounts" class="btn btn-outline-dark waves-effect text-medium btn-sm"
            company-id="{{ $company->id }}">Collection Accounts
        </button>

        <button type="button" id="authorized-representative" class="btn btn-outline-dark waves-effect text-medium btn-sm"
            company-id="{{ $company->id }}">Authorized Representative
        </button>

        <button type="button" id="confirm-assessment" class="btn btn-info waves-effect text-medium btn-sm"
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
    <h5 class="text-uppercase">Confirm Company Assessment</h5>

    <div id="output" class="alert alert-success" style="display: none;"></div>

    <form id="myform" role="form" autocomplete="off">
        <input type="hidden" name="company_id" value="{{ $company->id }}">

        <div class="mb-2 mt-2">
            @php
                $arr_assessment_criteria = [
                    'Company_Information' => 'Company Information',
                    'Company_Documents' => 'Company Documents',
                    'Company_Collection_Accounts' => 'Company Collection Accounts',
                    'Company_Authorized_Representative' => 'Company Authorized Representative',
                ];
            @endphp

            <table class="table table-nowrap align-middle table-bordered mb-4">
                <thead>
                    <tr>
                        <th width="3%">#</th>
                        <th width="60%">Assessment Criteria</th>
                        <th width="30%">Status</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($company->partnerApprovals as $val)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $val->label }}</td>
                            <td>
                                @if ($val->status == 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($val->status == 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @elseif($val->status == 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mb-3 text-sm-end d-none d-sm-block">
            <button type='submit' id="btnSubmit" class="btn btn-primary text-medium">
                Confirm <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
            </button>
            <button type='reset' class="btn btn-danger text-medium">Reset</button>
        </div>
    </form>
</div>
<!--./flex -->

<script type="text/javascript">
    $(document).ready(function() {
        //validate form and submit
        $("#myform").validate({
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
                    url: "{{ url('companies/confirm-assessment') }}",
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
