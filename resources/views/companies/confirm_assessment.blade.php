<div class="d-flex flex-column justify-content-start">
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
                    @php
                        $serial = 0;
                    @endphp

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
                        @if ($val->status == 'approved')
                            @php
                                $serial++;
                            @endphp
                        @endif
                    @endforeach
                </tbody>
            </table> 
        </div>

        @if ($serial == 4 && $company->status != 3)
            <div class="mb-3 text-sm-end d-none d-sm-block">
                <button type='reset' class="btn btn-outline-danger text-medium">Reset</button>
                <button type='submit' id="btnSubmit" class="btn btn-primary text-medium">
                    Confirm <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
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
