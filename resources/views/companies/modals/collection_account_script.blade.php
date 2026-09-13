<script>
    $(function () {
        function initializeModalSelects(modal) {
            modal.find('.select2').select2({ dropdownParent: modal, width: '100%' });
        }

        function displayFormErrors(form, error) {
            const response = error.responseJSON || {};
            const errors = response.errors || {};
            let content = '';

            Object.keys(errors).forEach(function (key) {
                const values = Array.isArray(errors[key]) ? errors[key] : [errors[key]];
                values.forEach(function (value) {
                    content += "<div class='alert alert-danger py-2'>" + value + "</div>";
                });
            });

            if (!content) {
                content = "<div class='alert alert-danger py-2'>" +
                    (response.message || 'The action could not be completed.') + "</div>";
            }

            form.find('.account-form-message').html(content);
        }

        function submitAccountForm(form, defaultLabel) {
            const button = form.find('.account-submit');
            form.find('.account-form-message').empty();
            button.prop('disabled', true).text('Saving...');

            $.ajax({
                type: 'POST',
                url: form.attr('action'),
                data: form.serialize(),
                success: function (response) {
                    form.closest('.modal').modal('hide');
                    $('#message_wrp').html(
                        "<div class='alert alert-success rounded-sm'>" + response.message + "</div>"
                    );
                    setTimeout(function () { window.location.reload(); }, 700);
                },
                error: function (error) {
                    displayFormErrors(form, error);
                },
                complete: function () {
                    button.prop('disabled', false).text(defaultLabel);
                }
            });
        }

        const addModal = $('#addCollectionAccountModal');
        const addForm = $('#addCollectionAccountForm');
        initializeModalSelects(addModal);

        addModal.on('hidden.bs.modal', function () {
            addForm[0].reset();
            addForm.find('.select2').val('').trigger('change');
            addForm.find('.account-form-message').empty();
        });

        addForm.on('submit', function (event) {
            event.preventDefault();
            submitAccountForm(addForm, 'Add Account');
        });

        @if ($canReviewRegistration)
        const editModal = $('#editCollectionAccountModal');
        const editForm = $('#editCollectionAccountForm');
        initializeModalSelects(editModal);

        $(document).on('click', '.edit-collection-account', function () {
            const button = $(this);
            editForm.find('.account-form-message').empty();
            editForm.attr('action', button.data('update-url'));
            editForm.find('[name="account_name"]').val(button.attr('data-account-name'));
            editForm.find('[name="account_number"]').val(button.attr('data-account-number'));
            editForm.find('[name="customer_code"]').val(button.attr('data-customer-code'));
            editForm.find('[name="corporate_limit"]').val(
                Number(button.attr('data-corporate-limit') || 0).toLocaleString('en-US')
            );
            editForm.find('[name="currency_id"]').val(String(button.data('currency-id'))).trigger('change');
            editModal.modal('show');
        });

        editForm.on('submit', function (event) {
            event.preventDefault();
            submitAccountForm(editForm, 'Update Account');
        });
        @endif

        $(document).on('click', '.delete-collection-account', function () {
            const button = $(this);

            Swal.fire({
                title: 'Delete collection account?',
                text: 'This account will be permanently removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: button.data('delete-url'),
                    data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
                    success: function (response) {
                        Swal.fire('Deleted', response.message, 'success')
                            .then(function () { window.location.reload(); });
                    },
                    error: function (error) {
                        Swal.fire(
                            'Unable to delete',
                            error.responseJSON?.message || 'The collection account could not be deleted.',
                            'error'
                        );
                    }
                });
            });
        });

        $(document).on('click', '.deactivate-partner-user', function () {
            const button = $(this);

            Swal.fire({
                title: 'Deactivate user login?',
                text: button.data('user-name') + ' will no longer be able to sign in.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Deactivate',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: button.data('deactivate-url'),
                    data: { _token: '{{ csrf_token() }}', _method: 'PATCH' },
                    success: function (response) {
                        Swal.fire('Deactivated', response.message, 'success')
                            .then(function () { window.location.reload(); });
                    },
                    error: function (error) {
                        Swal.fire(
                            'Unable to deactivate',
                            error.responseJSON?.message || 'The user could not be deactivated.',
                            'error'
                        );
                    }
                });
            });
        });
    });
</script>
