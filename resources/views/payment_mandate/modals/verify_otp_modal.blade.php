<!-- Verify Otp Modal -->
<div id="verifyOtpModal" class="modal fade" tabindex="-1" aria-labelledby="verifyOtpModal" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-uppercase font-weight-600">Otp Verification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!--form message => success or danger -->
                <div id="message_wrp_1"></div>

                <form @submit.prevent="verifyOtp();" autocomplete="off">
                    <input type="hidden" name="uuid" x-model="verifyForm.uuid">

                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-12">
                            <div class="mb-2">
                                <label>OTP PIN <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="otpPin" x-model="verifyForm.otpPin"
                                    placeholder="Enter otp...">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="mb-2 d-flex justify-content-between">
                                <div>
                                    <a href="{{ route('payment-mandate.create-account', [$customer_account->uuid, $debit_order->uuid]) }}" class="btn btn-outline-primary">
                                        <i class="bx bx-edit"></i> Edit Mandate
                                    </a>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-outline-danger waves-effect"
                                        data-bs-dismiss="modal">
                                        Close
                                    </button>
                                    <button type="submit" name="submit" class="btn btn-primary btn-block"
                                        x-html="buttonVerify" :disabled="verifyLoading"></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="row mb-2">
                    <div class="col-md-12 d-flex justify-content-end text-sm-end">
                        <div class="m-1">
                            <i>Didn't receive otp?</i> 
                            <span role="button" class="text-primary ms-2 resend_otp" x-on:click="resendOtp()" x-html="resendBtn"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
