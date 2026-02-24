<script type="text/javascript">
    //initiate data
    function initData() {
        return {
            uuid: "{{ $debit_order->uuid }}",
            status: "{{ $debit_order->status }}",
            channel: "{{ $debit_order->channel }}",
            showModal: false,

            formData: {
                uuid: "{{ $debit_order->uuid }}",
            },

            verifyForm: {
                uuid: "{{ $debit_order->uuid }}",
                otpPin: "",
            },

            formMessage: "",
            saveLoading: false,
            buttonSave: "Save",

            submitLoading: false,
            buttonSubmit: "Submit",

            verifyLoading: false,
            buttonVerify: "Verify",

            resendBtn: "Resend",

            //Save Mandate
            saveMandateForm() {
                this.formMessage = "";
                this.saveLoading = false;
                this.buttonSave = "Saving...";

                fetch("{{ route('payment-mandate.save') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Accept: "application/json",
                            'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify(this.formData),
                    }).then(response => response.json())
                    .then(responseJson => {
                        console.log(responseJson);
                        // change status
                        this.status = 200;

                        // display message
                        $('#message_wrp').html(
                            "<div class='alert alert-success rounded-sm'>Debit Mandate saved.</div>");
                    }).catch(() => {
                        this.formMessage = "Something went wrong.";
                    }).finally(() => {
                        this.saveLoading = false;
                        this.buttonSave = "Save";
                    });
            },

            //Submit Mandate
            submitMandateForm() {
                this.formMessage = "";
                this.submitLoading = false;
                this.buttonSubmit = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

                fetch("{{ route('payment-mandate.confirm') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(this.formData),
                }).then(async response => {
                    const data = await response.json();

                    if (!response.ok) {
                        // Manually throw error to enter .catch
                        throw {
                            status: response.status,
                            data
                        };
                    }

                    //check if response is success
                    if (data.success == true) {
                        //check if channel is NMB
                        if (data.channel == "NMB") {
                            //set show modal to true
                            if (data.response_code == 201) {
                                //create modal for verifying OTP 
                                $('#verifyOtpModal').modal('show');
                                this.status = 201;

                                //display message
                                $('#message_wrp_1').html("<div class='alert alert-success rounded-sm'>" +
                                    data.success_msg + "</div>");

                                //TODO: redirect to success page

                            } else {
                                //display message
                                $('#message_wrp').html("<div class='alert alert-danger rounded-sm'>" + data
                                    .success_msg + "</div>");
                            }
                        } else if (data.channel == "CRDB") {
                            if (data.response_code == 201) {
                                this.status = 201;

                                //display message
                                $('#message_wrp').html("<div class='alert alert-success rounded-sm'>" + data
                                    .success_msg + "</div>");
                            }
                        } else if (data.channel == "YAS") {
                            if (data.response_code == 201) {
                                this.status = 201;
                                const url = data.redirect_url;

                                //display message
                                $('#message_wrp').html("<div class='alert alert-success rounded-sm'>" + data
                                    .success_msg + "</div>");

                                //redirect to URL
                                setTimeout(function() {
                                    window.location.href = url;
                                }, 2000);
                            }
                        } else {
                            //other channels
                        }
                    } else if (data.success == false) {
                        $('#message_wrp').html("<div class='alert alert-danger rounded-sm'>" + data
                            .success_msg + "</div>");
                    }
                }).catch(error => {
                    console.error("Error caught:", error);
                    let message = "Something went wrong.";

                    $('#message_wrp').html(
                        "<div class='alert alert-danger py-3 px-2'>" + message + "</div>"
                    );
                }).finally(() => {
                    this.submitLoading = false;
                    this.buttonSubmit = "Submit";
                });
            },

            //Button for popup modal to verify Otp
            verifyButton() {
                //display message
                $('#message_wrp_1').html(
                    "<div class='alert alert-success rounded-sm'>Input the OTP PIN sent to customer to consent and verify.</div>"
                );

                //create modal for verifying Otp 
                $('#verifyOtpModal').modal('show');
            },

            //request for create Otp
            createMandateOtp() {
                this.resendBtn = '<i class="fas fa-spinner fa-spin"></i> Resending...';

                fetch("{{ route('payment-mandate.resend-otp') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(this.formData),
                }).then(async response => {
                    const data = await response.json();

                    if (!response.ok) {
                        // Manually throw error to enter .catch
                        throw {
                            status: response.status,
                            data
                        };
                    }

                    //check if response is success
                    if (data.success == true) {
                        //check if channel is NMB
                        if (data.channel == "NMB") {
                            //set show modal to true
                            if (data.response_code == 201) {
                                this.status = 201;

                                //display message
                                $('#message_wrp_1').html("<div class='alert alert-success rounded-sm'>" +
                                    data.success_msg + "</div>");

                            } else {
                                //display message
                                $('#message_wrp_1').html("<div class='alert alert-danger rounded-sm'>" +
                                    data.success_msg + "</div>");
                            }
                        } else {
                            //check for other channels
                        }
                    } else if (data.success == false) {
                        $('#message_wrp_1').html("<div class='alert alert-danger rounded-sm'>" + data
                            .success_msg + "</div>");
                    }
                }).catch(error => {
                    console.error("Error caught:", error);
                    let message = "Something went wrong.";

                    $('#message_wrp_1').html(
                        "<div class='alert alert-danger py-3 px-2'>" + message + "</div>"
                    );
                }).finally(() => {
                    this.resendBtn = "Resend";
                });
            },

            //Request for verifying Otp
            verifyMandateOtp() {
                this.formMessage = "";
                this.verifyLoading = true;
                this.buttonVerify = '<i class="fas fa-spinner fa-spin"></i> Verifying...';

                fetch("{{ route('payment-mandate.verify-otp') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(this.verifyForm),
                }).then(async response => {
                    const data = await response.json();

                    if (!response.ok) {
                        throw {
                            status: response.status,
                            data
                        };
                    }

                    //change status
                    this.status = 201;

                    //check if response is success
                    if (data.success == true) {
                        //check if channel is NMB
                        if (data.channel == "NMB") {
                            //set show modal to true
                            if (data.response_code == 202) {
                                this.status = 202;

                                //display message
                                $('#message_wrp_1').html("<div class='alert alert-success rounded-sm'>" +
                                    data.success_msg + "</div>");

                                //Redirect to success page
                                var redirectionURL = '/payment-mandate/show/' + this.uuid;

                                setTimeout(function() {
                                    window.location.href = redirectionURL
                                }, 2000);
                            } else {
                                //display message
                                $('#message_wrp').html("<div class='alert alert-danger rounded-sm'>" + data
                                    .success_msg + "</div>");
                            }
                        } else {
                            //check for other channels
                        }
                    } else if (data.success == false) {
                        $('#message_wrp_1').html("<div class='alert alert-danger rounded-sm'>" + data
                            .success_msg + "</div>");
                    }
                }).catch(error => {
                    console.error("Error caught:", error);
                    let message = "Something went wrong.";

                    if (error.status === 422 && error.data?.errors) {
                        const errors = error.data.errors;
                        message = Object.values(errors).flat().join(" ");
                    } else {
                        message = error.data?.message || "Something went wrong. Try again.";
                    }

                    $('#message_wrp_1').html(
                        "<div class='alert alert-danger py-3 px-2'>" + message + "</div>"
                    );
                }).finally(() => {
                    this.verifyLoading = false;
                    this.buttonVerify = "Verify";
                });
            },
        }
    }
</script>
