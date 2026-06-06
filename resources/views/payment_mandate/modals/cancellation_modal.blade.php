<style>
    .pm-cancel-modal .modal-content {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(21, 53, 103, 0.18);
    }

    .pm-cancel-modal__header {
        background: linear-gradient(135deg, #7a1f1f 0%, #b13a3a 100%);
        color: #fff;
        padding: 1rem 1.1rem;
        border-bottom: 0;
    }

    .pm-cancel-modal__title {
        margin-bottom: 0.2rem;
        font-size: 1rem;
        font-weight: 700;
         color: #fff;
        text-transform: uppercase;
    }

    .pm-cancel-modal__subtitle {
        margin-bottom: 0;
        color: rgba(255, 255, 255, 0.82);
        font-size: 0.85rem;
    }

    .pm-cancel-modal .modal-body {
        padding: 1.1rem;
        background: #fff;
    }

    .pm-cancel-modal__note {
        border: 1px dashed #f0c2c2;
        border-radius: 14px;
        background: #fff7f7;
        color: #8a4b4b;
        padding: 0.85rem 0.95rem;
        font-size: 0.85rem;
        margin-bottom: 1rem;
    }

    .pm-cancel-modal textarea.form-control {
        border-radius: 12px;
        border-color: #e4caca;
        box-shadow: none;
    }

    .pm-cancel-modal textarea.form-control:focus {
        border-color: #d29797;
        box-shadow: 0 0 0 0.15rem rgba(177, 58, 58, 0.08);
    }

    .pm-cancel-modal__actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.65rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }
</style>

<div id="cancellationModal" class="modal fade pm-cancel-modal" tabindex="-1" aria-labelledby="cancellationModal" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header pm-cancel-modal__header">
                <div>
                    <h5 class="pm-cancel-modal__title">Mandate Cancellation</h5>
                    <p class="pm-cancel-modal__subtitle">Record a clear reason before sending this mandate for cancellation.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div id="message_wrp_2"></div>

                <div class="pm-cancel-modal__note">
                    Cancellation should be used only when the mandate should no longer be collected. Provide enough detail for audit and support follow-up.
                </div>

                <form @submit.prevent="mandateCancellationForm();" autocomplete="off">
                    <div class="mb-2">
                        <label class="form-label text-dark fw-semibold">Reason for cancellation <span class="text-danger">*</span></label>
                        <textarea name="remarks" x-model="formData.remarks" placeholder="Write reason for cancellation..."
                            class="form-control" rows="4" required></textarea>
                    </div>

                    <div class="pm-cancel-modal__actions">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
                            Close
                        </button>
                        <button type="submit" name="submit" class="btn btn-danger"
                            x-html="buttonSubmit" :disabled="formLoading"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
