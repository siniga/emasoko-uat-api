<!-- Create or update Contract Price -->
<style>
    .reject-modal .modal-content {
        border: 0;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
    }

    .reject-modal__header {
        background: linear-gradient(135deg, #8f1d1d 0%, #b42318 100%);
        padding: 1.25rem 1.4rem;
        color: #fff !important;
        border-bottom: 0;
    }

    .reject-modal__title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #fff !important;
        margin-bottom: 0.2rem;
    }

    .reject-modal__subtitle {
        margin-bottom: 0;
        color: #fff !important;
        font-size: 0.9rem;
    }

    .reject-modal .modal-body {
        padding: 1.4rem;
        background: #fcfcfd;
    }

    .reject-modal__notice {
        border: 1px solid #f3d1d1;
        border-radius: 14px;
        background: #fff5f5;
        color: #7a271a;
        padding: 0.95rem 1rem;
        margin-bottom: 1rem;
    }

    .reject-modal__label {
        font-weight: 700;
        color: #153567;
        margin-bottom: 0.5rem;
    }

    .reject-modal textarea.form-control {
        border-radius: 14px;
        padding: 0.85rem 0.95rem;
        min-height: 130px;
        resize: vertical;
    }

    .reject-modal__actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-top: 1rem;
    }

    @media (max-width: 767.98px) {
        .reject-modal__actions {
            flex-direction: column;
        }

        .reject-modal__actions .btn {
            width: 100%;
        }
    }
</style>

<div id="rejectModal" class="modal fade" tabindex="-1" aria-labelledby="rejectModal" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered reject-modal">
        <div class="modal-content">
            <div class="modal-header reject-modal__header">
                <div>
                    <h5 class="modal-title reject-modal__title">Reject Service Provider Registration</h5>
                    <p class="reject-modal__subtitle">This action records a rejection decision and stores the reason
                        against the provider profile.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!--form message => success or danger -->
                <div id="message_wrp_1"></div>

                <div class="reject-modal__notice">
                    Provide a clear reason so the service provider team can understand what needs to be corrected before
                    resubmission.
                </div>

                <form @submit.prevent="rejectRegistration();" autocomplete="off">
                    <div class="mb-2">
                        <label class="reject-modal__label">Reason for rejection</label>
                        <textarea name="remarks" x-model="formData.remarks" placeholder="Write reason for rejection..." class="form-control"
                            required=""></textarea>
                    </div>

                    <div class="reject-modal__actions">
                        <button type="button" class="btn btn-outline-secondary waves-effect" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" name="submit" class="btn btn-danger" x-text="buttonSubmit"
                            :disabled="formLoading"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
