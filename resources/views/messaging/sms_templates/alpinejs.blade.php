<script type="text/javascript">
    function initData() {
        return {
            page_title: "Create SMS template",
            arr_category: {},

            template_form: {
                action: 'create',
                id: '',
                category_id: '',
                name: '',
                code: '',
                channel: 'ALL',
                message: '',
                is_active: true
            },
            formLoading: false,
            btnSubmit: "Submit",

            updateData(id) {
                this.page_title = "Update SMS Template";
                this.template_form.action = "update";
                this.template_form.id = id;

                fetch("/messaging/sms-templates/" + id + "/get-by")
                    .then(response => response.json())
                    .then(data => {
                        this.template_form.name = data.data.name;
                        this.template_form.code = data.data.code || '';
                        this.template_form.channel = data.data.channel || 'ALL';
                        this.template_form.message = data.data.message;
                        this.template_form.category_id = data.data.category_id;
                        this.template_form.is_active = data.data.is_active;
                    });
            },

            createOrUpdateForm() {
                this.formLoading = true;
                this.btnSubmit = '<i class="fas fa-spinner"></i> Submitting...';

                fetch("{{ route('sms-templates.store') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(this.template_form),
                }).then(response => response.json())
                .then(responseJson => {
                    if (!responseJson.success) {
                        throw responseJson;
                    }

                    $('.message_wrp').html(
                        "<div class='alert alert-success rounded-sm'>" + responseJson.success_msg + "</div>"
                    );

                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }).catch(() => {
                    $('.message_wrp').html(
                        "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>"
                    );
                }).finally(() => {
                    this.formLoading = false;
                    this.btnSubmit = 'Submit';
                });
            },
        }
    }
</script>
