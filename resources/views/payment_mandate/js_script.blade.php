    <script type="text/javascript">
        //initiate data
        function initData() {
            return {
                sidebarOpen: false,
                eModal: "Mandate_Information",
                debit_order_uuid: "{{ $debit_order->uuid }}",
                status: "{{ $debit_order->status }}",
                lifecycleStatus: "{{ $debit_order->lifecycle_status ?? $debit_order->resolved_lifecycle_status }}",
                channel: "{{ $debit_order->channel }}",
                deleted: "{{ $debit_order->deleted }}",
                cancellationAttempted: @js(!empty($debit_order->cancellationStartedAt)),
                // remarks: "{{ $debit_order->remarks }}",
                remarks: @js($debit_order->remarks_message),
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

                canEdit() {
                    return ['draft', 'submitted', 'failed'].includes(this.lifecycleStatus);
                },

                canArchive() {
                    return this.deleted == 0 && ['draft', 'submitted', 'awaiting_otp', 'verified', 'failed'].includes(this.lifecycleStatus);
                },

                canCancel() {
                    return ['submitted', 'awaiting_otp', 'verified', 'active'].includes(this.lifecycleStatus) ||
                        (this.lifecycleStatus === 'failed' && this.cancellationAttempted);
                },

                canVerifyOtp() {
                    return this.channel === 'NMB' && this.lifecycleStatus === 'awaiting_otp';
                },

                canFetchStatus() {
                    return this.channel === 'NMB' && ['submitted', 'awaiting_otp', 'verified', 'failed'].includes(this.lifecycleStatus);
                },

                lifecycleBadgeHtml() {
                    const config = {
                        draft: ['Draft', 'secondary'],
                        submitted: ['Submitted', 'info'],
                        awaiting_otp: ['Awaiting OTP', 'warning'],
                        verified: ['Verified', 'info'],
                        active: ['Active', 'success'],
                        cancellation_requested: ['Cancellation Requested', 'warning'],
                        cancelled: ['Cancelled', 'danger'],
                        closed: ['Closed', 'closed'],
                        archived: ['Archived', 'secondary'],
                        failed: ['Failed', 'danger'],
                    };
                    const current = config[this.lifecycleStatus] || ['Unknown Status', 'secondary'];
                    return `<span class="badge badge-pill badge-soft-${current[1]}">${current[0]}</span>`;
                },

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

                        this.cancellationAttempted = true;

                        //check if response is success
                        if (data.success == true) {
                            //check if channel is NMB
                            if (data.channel == "NMB") {
                                //set show modal to true
                                if (data.response_code == 240) {
                                    this.status = 240;
                                    this.lifecycleStatus = data.lifecycle_status || 'active';

                                    //display message
                                    $('#message_wrp').html("<div class='alert alert-success rounded-sm'>" + data
                                        .success_msg + "</div>");
                                } else if (data.response_code == 249 || data.response_code == 430) {
                                    this.lifecycleStatus = data.lifecycle_status || this.lifecycleStatus;
                                    $('#message_wrp').html("<div class='alert alert-info rounded-sm'>" + data
                                        .success_msg + "</div>");
                                } else {
                                    this.lifecycleStatus = data.lifecycle_status || this.lifecycleStatus;
                                    $('#message_wrp').html("<div class='alert alert-danger rounded-sm'>" + data
                                        .success_msg + "</div>");
                                }
                            } else {
                                //check for other channels
                            }
                        } else if (data.success == false) {
                            $('#message_wrp').html("<div class='alert alert-danger rounded-sm'>" + data
                                .success_msg + "</div>");
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
                            if (data.channel == "NMB" || data.channel == "CRDB") {
                                //set show modal to true
                                if (data.response_code == 201) {
                                    this.status = 405;
                                    this.lifecycleStatus = data.lifecycle_status || 'cancellation_requested';

                                    //display message
                                    $('#message_wrp_2').html("<div class='alert alert-success rounded-sm'>" + data.success_msg + "</div>");
                                } else if (data.response_code == 249 || data.response_code == 430) {
                                    this.lifecycleStatus = data.lifecycle_status || this.lifecycleStatus;
                                    $('#message_wrp_2').html("<div class='alert alert-info rounded-sm'>" + data.success_msg + "</div>");
                                } else {
                                    this.lifecycleStatus = data.lifecycle_status || this.lifecycleStatus;
                                    $('#message_wrp_2').html("<div class='alert alert-danger rounded-sm'>" + data.success_msg + "</div>");
                                }
                            } else {
                                //check for other channels
                            }
                        } else if (data.success == false) {
                            this.lifecycleStatus = data.lifecycle_status || this.lifecycleStatus;
                            $('#message_wrp_2').html("<div class='alert alert-danger rounded-sm'>" + data.success_msg + "</div>");
                        }
                    }).catch(error => {
                        console.error("Error caught:", error);
                        const message = error.data?.errors?.mandate?.[0] ||
                            error.data?.message ||
                            "Something went wrong.";
                        $('#message_wrp_2').html("<div class='alert alert-danger py-3 px-2'>" + message + "</div>");
                    }).finally(() => {
                        this.formLoading = false;
                        this.buttonSubmit = 'Submit';
                    });
                },

                //verifyButton
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
                                    this.lifecycleStatus = data.lifecycle_status || 'awaiting_otp';

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
                                this.lifecycleStatus = data.lifecycle_status || 'verified';

                                //display message
                                $('#message_wrp_1').html("<div class='alert alert-success rounded-sm'>" +
                                        data.success_msg + "</div>");

                                    //Redirect to success page
                                    var redirectionURL = '/payment-mandate/show/' + this.debit_order_uuid;

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
