<div class="col-md-12">
    <form action="{{ $url }}" method="post" class="d-flex justify-content-end text-sm-end otp-verification-form">
        <div class="m-1">
            @csrf
            <input type="hidden" name="uuid" class="" value="{{ $uuid }}">
            <input type="hidden" name="processName" value="{{ $action }}">
            <input type="text" class="form-control w-100" name="otpPin" placeholder="Enter otp...">
        </div>
        <div class="m-1">
            <button type="submit" name="save" class="btn btn-secondary">Verify</button>
        </div>
    </form>
</div>
<div class="col-md-12 d-flex justify-content-end text-sm-end">
    <div class="m-1">
        <i>Didn't receive otp?</i> <span role="button" class="text-primary ms-2 resend_otp">Resend</span>
    </div>
</div>
@push('push-script')
    <script>
        $(document).ready(function() {
            $('.otp-verification-form').on('submit', function(e) {
                e.preventDefault();
                // form = new FormData($('.otp-verification-form')[0]);
                $("#message_wrp").text("");
                $("#message_wrp").removeClass("alert-danger");
                $("#message_wrp").removeClass("alert-success");
                $.ajax({
                    url: $('.otp-verification-form').attr('action'),
                    method: "POST",
                    data: $('.otp-verification-form').serialize(),
                    success: function(res) {
                        $("#message_wrp").text(res.message);
                        if (res.status) {
                            $("#message_wrp").addClass("alert-success");
                        } else {
                            $("#message_wrp").addClass("alert-danger");
                        }
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                });
            });

            $('.resend_otp').on('click', function(e) {
                $("#message_wrp").text("");
                $("#message_wrp").removeClass("alert-danger");
                $("#message_wrp").removeClass("alert-success");
                $.ajax({
                    url: $('.otp-verification-form').attr('action'),
                    method: "POST",
                    data: $('.otp-verification-form').serialize(),
                    success: function(res) {
                        $("#message_wrp").text(res.message);
                        if (res.status) {
                            $("#message_wrp").addClass("alert-success");
                        } else {
                            $("#message_wrp").addClass("alert-danger");
                        }
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                });
            });
        })
    </script>
@endpush
