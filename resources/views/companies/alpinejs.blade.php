<script type="text/javascript">
    //initiate data
    function initData() {
        return {
            sidebarOpen: false,
            eModal: "Company_Information",
            uuid: "{{ $company->uuid }}",
            approved: "{{ $company->approved }}",
            status: "{{ $company->status }}",
            httpStatus: 200,
            remarks: "{{ $company->remarks }}",
            stages: [],

            formData: {
                'uuid': "{{ $company->uuid }}",
                'action': "",
                'remarks': "",
            },
            formLoading: false,
            formMessage: "",
            btnApprove: '<i class="fa fa-check"></i> Approve Registration',
            buttonSubmit: "Submit",
            buttonConfirm: "Confirm",
            confirm_btn: false,

            //status
            btnStatus: '<i class="fa fa-check"></i> Get Status',

            //fetch url info
            fetchData(uuid) {
                fetch("/companies/assessment/" + uuid)
                    .then(response => response.text())
                    .then(data => {
                        $('#detail-div').html(data)
                    });
            },

            //open reject modal
            openRejectModal() {
                $('#rejectModal').modal('show');
            },

            //reject company registration
            rejectRegistration() {
                this.formLoading = true;
                this.btnSubmit = 'Reject Registration....';

                //format form 
                this.formData.action = "REJECT";

                fetch("/companies/confirm-assessment", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Accept: "application/json",
                            'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify(this.formData),
                    }).then(response => response.json())
                    .then(responseJson => {
                        this.status = responseJson.status
                        this.approved = responseJson.approved
                        this.remarks = responseJson.remarks

                        $('#message_wrp_1').html(
                            "<div class='alert alert-success rounded-sm'>" + responseJson.message + "</div>"
                        );
                    }).catch(() => {
                        //display message
                        $('#message_wrp').html(
                            "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>"
                        );

                    }).finally(() => {
                        this.formLoading = false;
                        this.btnSubmit = 'Submit';
                    });
            },

            //approval company registration
            approveRegistration() {
                this.formLoading = true;
                this.btnApprove = '<i class="fas fa-spinner fa-spin"></i> Approving Registration....';

                //format form 
                this.formData.action = "APPROVE";
                this.formData.remarks = "Service provider registration approved by eMakato Team.";

                fetch("/companies/confirm-assessment", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Accept: "application/json",
                            'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify(this.formData),
                    }).then(response => response.json())
                    .then(responseJson => {
                        this.httpStatus = responseJson.status

                        if (this.httpStatus == 200) {
                            $('#message_wrp').html(
                                "<div class='alert alert-success rounded-sm'>" + responseJson.message + "</div>"
                            );
                        } else if (this.httpStatus == 202) {
                            $('#message_wrp').html(
                                "<div class='alert alert-warning rounded-sm'>" + responseJson.message + " " + (responseJson.remarks || "") + "</div>"
                            );
                        } else if (this.httpStatus == 400) {
                            $('#message_wrp').html(
                                "<div class='alert alert-danger rounded-sm'>" + responseJson.message + "</div>"
                            );
                        } else {
                            $('#message_wrp').html(
                                "<div class='alert alert-danger rounded-sm'>" + responseJson.message + "</div>"
                            );
                        }

                        if (this.httpStatus == 200) {
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        }
                    }).catch(() => {
                        //display message
                        $('#message_wrp').html(
                            "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>"
                        );

                    }).finally(() => {
                        this.formLoading = false;
                        this.btnApprove = '<i class="fa fa-check"></i> Approve Registration';
                    });
            },

            //approval company registration
            fetchRegistrationStatus() {
                this.formLoading = true;
                this.btnStatus = '<i class="fas fa-spinner fa-spin"></i> Get Registration status....';

                //format form 
                this.formData.action = "APPROVE";
                this.formData.remarks = "Company registration approved";

                fetch("/companies/fetch-corporate-status", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Accept: "application/json",
                            'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify(this.formData),
                    }).then(response => response.json())
                    .then(responseJson => {
                        this.httpStatus = responseJson.status
                        this.remarks = responseJson.remarks;

                        if (this.httpStatus == 200) {
                            $('#message_wrp').html(
                                "<div class='alert alert-success rounded-sm'>" + responseJson.message + "</div>"
                            );
                        } else if (this.httpStatus == 202) {
                            $('#message_wrp').html(
                                "<div class='alert alert-warning rounded-sm'>" + responseJson.message + " " + (responseJson.remarks || "") + "</div>"
                            );
                        } else if (this.httpStatus == 400) {
                            $('#message_wrp').html(
                                "<div class='alert alert-danger rounded-sm'>" + responseJson.message + "</div>"
                            );
                        } else {
                            $('#message_wrp').html(
                                "<div class='alert alert-danger rounded-sm'>" + responseJson.message + "</div>"
                            );
                        }

                        if (this.httpStatus == 200 || this.httpStatus == 202) {
                            setTimeout(function() {
                                location.reload();
                            }, 1800);
                        }

                    }).catch(() => {
                        //display message
                        $('#message_wrp').html(
                            "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>"
                        );

                    }).finally(() => {
                        this.formLoading = false;
                        this.btnStatus = '<i class="fa fa-check"></i> Get Status';
                    });
            },
        }
    }
</script>
