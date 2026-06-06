<style>
    .pm-modal .modal-content {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(21, 53, 103, 0.18);
    }

    .pm-modal__header {
        background: linear-gradient(135deg, #153567 0%, #224b88 100%);
        color: #fff;
        padding: 1rem 1.1rem;
        border-bottom: 0;
    }

    .pm-modal__title {
        margin-bottom: 0.2rem;
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
        text-transform: uppercase;
    }

    .pm-modal__subtitle {
        margin-bottom: 0;
        color: rgba(255, 255, 255, 0.82);
        font-size: 0.85rem;
    }

    .pm-modal .modal-body {
        padding: 1.1rem;
        background: #fff;
    }

    .pm-modal__note {
        border: 1px dashed #cfe0f4;
        border-radius: 14px;
        background: #f8fbff;
        color: #58708f;
        padding: 0.85rem 0.95rem;
        font-size: 0.85rem;
        margin-bottom: 1rem;
    }

    .pm-modal__field label {
        color: #153567;
        font-weight: 600;
        margin-bottom: 0.35rem;
    }

    .pm-modal__field .form-control {
        min-height: 44px;
        border-radius: 12px;
        border-color: #d7e3f4;
        box-shadow: none;
    }

    .pm-modal__field .form-control:focus {
        border-color: #9cb8df;
        box-shadow: 0 0 0 0.15rem rgba(21, 53, 103, 0.08);
    }

    .pm-modal__footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .pm-modal__footer-actions {
        display: flex;
        gap: 0.65rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .pm-modal__helper {
        font-size: 0.84rem;
        color: #58708f;
    }

    .pm-modal__helper span {
        font-weight: 600;
        cursor: pointer;
    }
</style>

<div id="verifyOtpModal" class="modal fade pm-modal" tabindex="-1" aria-labelledby="verifyOtpModal" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header pm-modal__header">
                <div>
                    <h5 class="pm-modal__title">Otp Verification</h5>
                    <p class="pm-modal__subtitle">Confirm customer consent using the OTP sent to the registered channel.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div id="message_wrp_1"></div>

                <div class="pm-modal__note">
                    Enter the one-time PIN exactly as received by the customer. If the OTP has expired or was not delivered, resend it before retrying verification.
                </div>

                <form @submit.prevent="verifyMandateOtp();" autocomplete="off">
                    <input type="hidden" name="uuid" x-model="verifyForm.uuid">

                    <div class="pm-modal__field">
                        <label>OTP PIN <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="otpPin" x-model="verifyForm.otpPin"
                            placeholder="Enter OTP PIN">
                    </div>

                    <div class="pm-modal__footer">
                        <div class="pm-modal__helper">
                            Didn't receive OTP?
                            <span class="text-primary ms-1 resend_otp" x-on:click="createMandateOtp()" x-html="resendBtn"></span>
                        </div>

                        <div class="pm-modal__footer-actions">
                            <a href="{{ route('payment-mandate.create-account', [$customer_account->uuid, $debit_order->uuid]) }}" class="btn btn-outline-primary">
                                <i class="bx bx-edit me-1"></i> Edit Mandate
                            </a>
                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
                                Close
                            </button>
                            <button type="submit" name="submit" class="btn btn-primary"
                                x-html="buttonVerify" :disabled="verifyLoading"></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
