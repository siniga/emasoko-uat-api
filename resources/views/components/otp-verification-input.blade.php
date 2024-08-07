<div class="col-md-12">
    <form @submit.prevent="verifyOtp();" class="d-flex justify-content-end text-sm-end">
        <div class="m-1">
            @csrf
            <input type="hidden" name="uuid" x-model="verifyForm.uuid">
            <input type="text" class="form-control w-100" name="otpPin" x-model="verifyForm.otpPin" placeholder="Enter otp...">
        </div>
        <div class="m-1">
            <button type="submit" name="verify" class="btn btn-primary"
                x-text="buttonVerify" :disabled="verifyLoading">
            </button>
        </div>
    </form>
</div>

<div class="col-md-12 d-flex justify-content-end text-sm-end">
    <div class="m-1">
        <i>Didn't receive otp?</i> <span role="button" class="text-primary ms-2 resend_otp" x-on:click="resendOtp()">Resend</span>
    </div>
</div>
