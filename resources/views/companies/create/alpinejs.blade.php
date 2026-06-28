<script type="text/javascript">
    //initiate data
    function initData() {
        return {
            formData: {
                id: "{{ $company->id }}",
                uuid: "{{ $company->uuid }}",
            },
            complete: "{{ $company->complete }}",
            approved: "{{ $company->approved }}",
            status: "{{ $company->status }}",
            formMessage: "",

            saveLoading: false,
            buttonSave: "Save",

            submitLoading: false,
            buttonSubmit: "Submit",

            cancelLoading: false,
            buttonCancel: "Cancel",

            cancelForm() {
                if (!window.confirm("Cancel this registration and permanently delete all submitted information?")) {
                    return;
                }

                this.cancelLoading = true;
                this.buttonCancel = "Cancelling...";

                fetch(@json(($selfRegistration ?? false) ? route('self-registration.cancel') : '#'), {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Accept: "application/json",
                            'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify(this.formData),
                    })
                    .then(async response => {
                        const responseJson = await response.json();

                        if (!response.ok) {
                            throw responseJson;
                        }

                        return responseJson;
                    })
                    .then(() => {
                        window.location.href = @json(route('self-registration.information'));
                    })
                    .catch((error) => {
                        $('#message_wrp').html(
                            "<div class='alert alert-danger'>" +
                            (error?.message || "Failed to cancel registration.") +
                            "</div>"
                        );
                    })
                    .finally(() => {
                        this.cancelLoading = false;
                        this.buttonCancel = "Cancel";
                    });
            },

            //submit form
            submitForm() {
                this.formMessage = "";
                this.submitLoading = true;
                this.buttonSubmit = "Submitting...";

                fetch(@json(($selfRegistration ?? false) ? route('self-registration.submit') : route('companies.confirm-registration')), {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Accept: "application/json",
                            'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify(this.formData),
                    }).then(async response => {
                        const responseJson = await response.json();

                        if (!response.ok) {
                            throw responseJson;
                        }

                        return responseJson;
                    })
                    .then(responseJson => {
                        //change complete status
                        this.complete = 1;

                        //display message
                        $('#message_wrp').html(
                            "<div class='alert alert-success rounded-sm'>Service provider registered, Waiting for assessment and approval process.</div>"
                        );

                        console.log(responseJson);

                        //set timer
                        setTimeout(function() {
                            @if ($selfRegistration ?? false)
                                window.location.href = @json(route('self-registration.success', ['status' => 'submitted']));
                            @else
                                window.location.href = "/companies/show/" + responseJson.uuid;
                            @endif
                        }, 1000);

                    }).catch((error) => {
                        const message = error?.message
                            || "Failed to submit service provider information.";

                        $('#message_wrp').html(
                            "<div class='alert alert-danger'>" + message + "</div>"
                        );

                    }).finally(() => {
                        this.submitLoading = false;
                        this.buttonSubmit = "Submit";
                    });
            },

            //saving Form
            savingForm() {
                this.formMessage = "";
                this.saveLoading = true;
                this.buttonSave = "Saving...";

                fetch(@json(($selfRegistration ?? false) ? route('self-registration.save') : route('companies.saving-registration')), {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Accept: "application/json",
                            'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify(this.formData),
                    }).then(response => response.json())
                    .then(responseJson => {
                        //change complete status
                        this.complete = 0;

                        //display message
                        $('#message_wrp').html(
                            "<div class='alert alert-success rounded-sm'>Service provider information saved.</div>"
                        );

                        //set timer
                        setTimeout(function() {
                            @if ($selfRegistration ?? false)
                                window.location.href = @json(route('self-registration.success', ['status' => 'saved'])) +
                                    "&uuid=" + encodeURIComponent(responseJson.uuid);
                            @else
                                window.location.href = "/companies/show/" + responseJson.uuid;
                            @endif
                        }, 1000);
                    }).catch(() => {
                        this.formMessage = "Something went wrong.";
                    }).finally(() => {
                        this.saveLoading = false;
                        this.buttonSave = "Save";
                    });
            }
        }
    }
</script>
