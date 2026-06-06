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

            //submit form
            submitForm() {
                this.formMessage = "";
                this.submitLoading = false;
                this.buttonSubmit = "Submitting...";

                fetch("/companies/confirm-registration", {
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
                        this.complete = 1;

                        //display message
                        $('#message_wrp').html(
                            "<div class='alert alert-success rounded-sm'>Service provider registered, Waiting for assessment and approval process.</div>"
                        );

                        console.log(responseJson);

                        //set timer
                        setTimeout(function() {
                            window.location.href = "/companies/show/" + responseJson.uuid;
                        }, 1000);

                    }).catch(() => {
                        //change submit button text
                        $('#submitButton').text('Update');

                        //write error message
                        $('.form-message').html(
                            "<div class='alert alert-danger py-2.5 px-2'>Failed to update service provider information</div>"
                        );
                    }).finally(() => {
                        this.submitLoading = false;
                        this.buttonSubmit = "Submit";
                    });
            },

            //saving Form
            savingForm() {
                this.formMessage = "";
                this.saveLoading = false;
                this.buttonSave = "Saving...";

                fetch("/companies/saving-registration", {
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
                            window.location.href = "/companies/show/" + response.uuid;
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
