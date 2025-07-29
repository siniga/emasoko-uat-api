    <script type="text/javascript">
        //initiate data
        function initData() {
            return {
                sidebarOpen: false,
                eModal: "Mandate_Information",
                debit_order_uuid: "{{ $debit_order->uuid }}",
                status: "{{ $debit_order->status }}",
                channel: "{{ $debit_order->channel }}",
                deleted: "{{ $debit_order->deleted }}",
                remarks: "{{ $debit_order->remarks }}",
                approved: "{{ $debit_order->approved }}",
                verifyTxt: "<i class='bx bx-check-square'></i> Verify OTP",

                formData: {
                    mandate_id: "{{ $debit_order->id }}",
                    uuid: "{{ $debit_order->uuid }}",
                    'remarks': "",
                },

                resendForm: {
                    uuid: "{{ $debit_order->uuid }}",
                },

                verifyForm: {
                    uuid: "{{ $debit_order->uuid }}",
                    otpPin: "",
                },

                formLoading: false,
                formMessage: "",
                btnCancel: '<i class="fa fa-times"></i> Cancel Mandate',
                btnStatus: '<i class="fa fa-check"></i> Mandate Status',
                buttonSubmit: "Submit",

                verifyLoading: false,
                buttonVerify: "Verify",
                resendBtn: "Resend",

                //approval mandate status
                fetchMandateStatus() {
                    this.formLoading = true;
                    this.btnStatus = '<i class="fas fa-spinner fa-spin"></i> Fetching mandate status....';

                    fetch("{{ route('payment-mandate.fetch-status') }}", {
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
                                if (data.response_code == 240) {
                                    this.status = 240;

                                    //display message
                                    $('#message_wrp').html("<div class='alert alert-success rounded-sm'>" + data
                                        .success_msg + "</div>");
                                } else if (http_response_code == 249 || http_response_code == 430) {
                                    $('#message_wrp').html("<div class='alert alert-info rounded-sm'>" + data
                                        .success_msg + "</div>");
                                } else {
                                    $('#message_wrp').html("<div class='alert alert-danger rounded-sm'>" + data
                                        .success_msg + "</div>");
                                }
                            } else {
                                //check for other channels
                            }
                        } else if (data.success == false) {
                            $('#message_wrp').html("<div class='alert alert-danger rounded-sm'>" + data.success_msg + "</div>");
                        }
                    }).catch(error => {
                        console.error("Error caught:", error);
                        let message = "Something went wrong.";
                        // display error message
                        $('#message_wrp_1').html("<div class='alert alert-danger py-3 px-2'>" + message + "</div>");
                    }).finally(() => {
                        this.formLoading = false;
                        this.btnStatus = '<i class="fa fa-check"></i> Mandate Status';
                    });
                },

                //archive mandate
                archiveMandate() {
                    Swal.fire({
                        title: 'Archive Mandate',
                        html: "Are sure you want to archive mandate?",
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonColor: '#4CAF50',
                        cancelButtonColor: '#F44336',
                        reverseButtons: true,
                        confirmButtonText: 'Yes, Archive',
                        cancelButtonText: 'No, Cancel',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            //call api to archive mandate
                            fetch("/payment-mandate/request-archive/" + this.debit_order_uuid, {
                                    method: "GET",
                                    credentials: 'same-origin'
                                })
                                .then(res => res.json())
                                .then(response => {
                                    //display message
                                    $('#message_wrp').html(
                                        "<div class='alert alert-success rounded-sm'>" + response[
                                            'success_msg'] + "</div>"
                                    );
                                });
                        }
                    });
                },

                //delete mandate

                //open reject modal
                openMandateCancellationModal() {
                    $('#cancellationModal').modal('show');
                },

                //mandate cancellation
                mandateCancellationForm() {
                    this.formLoading = true;
                    this.buttonSubmit = '<i class="fas fa-spinner fa-spin"></i> Cancelling Mandate....';

                    fetch("{{ route('payment-mandate.cancel-mandate') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Accept: "application/json",
                            'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify(this.formData),
                    }).then(async response => {
                        const data = await response.json();
                        console.log(data);

                        if (!response.ok) {
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
                                    this.status = 405;

                                    //display message
                                    $('#message_wrp_2').html("<div class='alert alert-success rounded-sm'>" + data.success_msg + "</div>");
                                } else if (http_response_code == 249 || http_response_code == 430) {
                                    $('#message_wrp_2').html("<div class='alert alert-info rounded-sm'>" + data.success_msg + "</div>");
                                } else {
                                    $('#message_wrp_2').html("<div class='alert alert-danger rounded-sm'>" + data.success_msg + "</div>");
                                }
                            } else {
                                //check for other channels
                            }
                        } else if (data.success == false) {
                            $('#message_wrp_2').html("<div class='alert alert-danger rounded-sm'>" + data.success_msg + "</div>");
                        }
                    }).catch(error => {
                        console.error("Error caught:", error);
                        let message = "Something went wrong.";
                        // display error message
                        $('#message_wrp_w').html("<div class='alert alert-danger py-3 px-2'>" + message + "</div>");
                    }).finally(() => {
                        this.formLoading = false;
                        this.buttonSubmit = 'Submit';
                    });
                },

                //verifyButton
                verifyButton() {
                    //display message
                    $('#message_wrp_1').html(
                        "<div class='alert alert-success rounded-sm'>Resend OTP and input the OTP PIN to confirm customer consent.</div>"
                    );

                    //todo: create modal for verifying Otp 
                    $('#verifyOtpModal').modal('show');
                },

                //resend OTP
                resendOtp() {
                    //api for action
                    fetch("/payment-mandate/resend-otp", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify(this.resendForm),
                        }).then(response => response.json())
                        .then(responseJson => {
                            console.log(responseJson);

                            //response code 
                            const http_response_code = responseJson.http_response_code;

                            if (http_response_code == 200) {
                                //display message
                                $('#message_wrp_1').html(
                                    "<div class='alert alert-success rounded-sm'>OTP sent successfully</div>"
                                );
                            } else if (http_response_code == 500) {
                                //display message
                                $('#message_wrp_1').html(
                                    "<div class='alert alert-warning rounded-sm'>Failed to send OTP, Try again.</div>"
                                );
                            } else {
                                //display message
                                $('#message_wrp_1').html(
                                    "<div class='alert alert-warning rounded-sm'>Failed to send OTP, Try again.</div>"
                                );
                            }

                        }).catch(() => {
                            this.formMessage = "Something went wrong.";
                        }).finally(() => {

                        });
                },

                //verify OTP
                verifyOtp() {
                    this.formMessage = "";
                    this.verifyLoading = true;
                    this.buttonVerify = "Verifying...";

                    //api for action
                    fetch("/payment-mandate/verify-otp", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify(this.verifyForm),
                        }).then(response => response.json())
                        .then(responseJson => {
                            console.log(responseJson);

                            //response code 
                            const http_response_code = responseJson.http_response_code;

                            if (http_response_code == 202) {
                                //display message
                                $('#message_wrp_1').html(
                                    "<div class='alert alert-success rounded-sm'>OTP validated</div>"
                                );

                                //redirect to details page
                                var redirectionURL = '/payment-mandate/show/' + this.verifyForm.uuid;

                                setTimeout(function() {
                                    window.location.href = redirectionURL
                                }, 2000);
                            } else if (http_response_code == 500) {
                                //display message
                                $('#message_wrp_1').html(
                                    "<div class='alert alert-danger rounded-sm'>Failed to verify OTP, Try again or resend OTP.</div>"
                                );
                            } else {
                                //display message
                                $('#message_wrp_1').html(
                                    "<div class='alert alert-danger rounded-sm'>Failed to verify OTP, Try again or contact support.</div>"
                                );
                            }
                        }).catch(() => {
                            this.formMessage = "Something went wrong.";
                        }).finally(() => {
                            this.verifyLoading = false;
                            this.buttonVerify = "Verify";
                        });
                },

            }
        }
    </script>
