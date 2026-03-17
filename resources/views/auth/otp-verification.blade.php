@extends('layouts.master-without-nav')

@section('title')
    @lang('translation.Login')
@endsection

@section('content')
    <style>
        .auth-shell {
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(21, 53, 103, 0.16), transparent 32%),
                radial-gradient(circle at bottom right, rgba(21, 53, 103, 0.08), transparent 30%),
                linear-gradient(135deg, #f5f8fd 0%, #eaf0f9 48%, #f7f9fc 100%);
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }

        .auth-panel {
            border: 0;
            border-radius: 26px;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(27, 46, 58, 0.14);
            background: #ffffff;
        }

        .auth-story {
            background:
                linear-gradient(160deg, rgba(11, 29, 61, 0.98) 0%, rgba(21, 53, 103, 0.96) 58%, rgba(34, 76, 140, 0.92) 100%);
            color: #ffffff;
            padding: 2.75rem;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .auth-story::before,
        .auth-story::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
        }

        .auth-story::before {
            width: 220px;
            height: 220px;
            top: -70px;
            right: -80px;
        }

        .auth-story::after {
            width: 180px;
            height: 180px;
            bottom: -60px;
            left: -60px;
        }

        .auth-story > * {
            position: relative;
            z-index: 1;
        }

        .auth-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #f6fbfb;
            border-radius: 999px;
            padding: 0.45rem 0.85rem;
            font-size: 0.8rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .auth-story-title {
            font-size: 2rem;
            line-height: 1.15;
            font-weight: 700;
            margin: 1.25rem 0 0.85rem;
            color: #ffffff;
        }

        .auth-story-copy {
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.98rem;
            line-height: 1.75;
            max-width: 28rem;
        }

        .auth-points {
            list-style: none;
            padding: 0;
            margin: 2rem 0 0;
        }

        .auth-points li {
            display: flex;
            gap: 0.85rem;
            align-items: flex-start;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.88);
        }

        .auth-points li i {
            font-size: 1.1rem;
            margin-top: 0.1rem;
            color: #cfe1ff;
        }

        .auth-form-wrap {
            padding: 2.5rem 2.25rem;
        }

        .auth-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .auth-brand img {
            height: 44px;
        }

        .auth-form-title {
            color: #17303e;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .auth-form-copy {
            color: #627381;
            font-size: 0.95rem;
            margin-bottom: 1.6rem;
            line-height: 1.7;
        }

        .auth-step-note {
            background: #f4f8fb;
            border: 1px solid #dce7ef;
            border-radius: 16px;
            padding: 0.95rem 1rem;
            color: #52616d;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .auth-input {
            border-radius: 14px;
            min-height: 52px;
            border: 1px solid #d6e1e7;
            background: #fbfdfe;
            padding-left: 0.95rem;
            letter-spacing: 0.35em;
            font-size: 1.05rem;
            font-weight: 700;
            text-align: center;
        }

        .auth-input:focus {
            border-color: #153567;
            box-shadow: 0 0 0 0.18rem rgba(21, 53, 103, 0.16);
            background: #ffffff;
        }

        .auth-submit {
            min-height: 50px;
            border-radius: 14px;
            background: linear-gradient(135deg, #153567 0%, #204986 100%);
            border: 0;
            font-weight: 600;
            letter-spacing: 0.02em;
            box-shadow: 0 14px 28px rgba(21, 53, 103, 0.24);
        }

        .auth-submit:hover,
        .auth-submit:focus {
            background: linear-gradient(135deg, #102a53 0%, #183f74 100%);
        }

        .auth-link {
            color: #153567;
            font-weight: 600;
            text-decoration: none;
        }

        .auth-link:hover {
            color: #102a53;
        }

        .auth-footer {
            color: #72818c;
            font-size: 0.9rem;
        }

        .auth-footer a {
            color: #153567;
            font-weight: 600;
        }

        @media (max-width: 991.98px) {
            .auth-story {
                padding: 2rem;
            }

            .auth-form-wrap {
                padding: 2rem 1.4rem;
            }

            .auth-form-title,
            .auth-story-title {
                font-size: 1.55rem;
            }

            .auth-input {
                letter-spacing: 0.22em;
            }
        }
    </style>

    <div class="auth-shell">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-10 col-lg-11">
                    <div class="card auth-panel">
                        <div class="row g-0">
                            <div class="col-lg-5 d-none d-lg-block">
                                <div class="auth-story">
                                    <span class="auth-badge">
                                        <i class="mdi mdi-cellphone-key"></i>
                                        OTP Verification
                                    </span>

                                    <h2 class="auth-story-title">One more secure step before you enter the system.</h2>

                                    <p class="auth-story-copy">
                                        We have sent a one-time verification code to your registered phone and email. Enter it below to complete your login.
                                    </p>

                                    <ul class="auth-points">
                                        <li>
                                            <i class="mdi mdi-timer-sand"></i>
                                            <span>Your OTP is time-sensitive, so it should be used as soon as you receive it.</span>
                                        </li>
                                        <li>
                                            <i class="mdi mdi-email-fast-outline"></i>
                                            <span>If you do not see the code immediately, check both your inbox and your phone messages.</span>
                                        </li>
                                        <li>
                                            <i class="mdi mdi-lock-check-outline"></i>
                                            <span>This extra verification step helps protect mandate and collection operations.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="auth-form-wrap">
                                    <div class="auth-brand">
                                        <img src="{{ URL::asset('assets/images/emakato_logo_colored.png') }}" alt="eMakato">
                                    </div>

                                    <h1 class="auth-form-title">Enter verification code</h1>
                                    <p class="auth-form-copy">
                                        Confirm the OTP sent to your registered contacts so we can securely complete your sign-in.
                                    </p>

                                    <div class="auth-step-note">
                                        <strong>Tip:</strong> enter the OTP exactly as received. If the code has expired, return to the login page and request a new one.
                                    </div>

                                    @if (session()->get('success'))
                                        <div class="alert alert-success py-2">{{ session()->get('success') }}</div>
                                    @elseif(session()->get('danger'))
                                        <div class="alert alert-danger py-2">{{ session()->get('danger') }}</div>
                                    @elseif(session()->get('error'))
                                        <div class="alert alert-danger py-2">{{ session()->get('error') }}</div>
                                    @endif

                                    <form class="form-horizontal needs-validation" method="POST"
                                        action="{{ route('otp.getlogin') }}" novalidate>
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $user_id }}" />
                                        <input type="hidden" name="uuid" value="{{ $uuid }}" />

                                        <div class="mb-3">
                                            <label for="otp" class="form-label fw-semibold">OTP Pin</label>
                                            <input name="otp" type="text"
                                                class="form-control auth-input @error('otp') is-invalid @enderror"
                                                value="{{ old('otp') }}" id="otp" placeholder="00000"
                                                autocomplete="one-time-code" inputmode="numeric" maxlength="5"
                                                required>
                                            @error('otp')
                                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                            @enderror
                                            <div class="invalid-feedback">OTP Pin required</div>
                                        </div>

                                        <div class="d-grid">
                                            <button class="btn btn-primary auth-submit waves-effect waves-light"
                                                type="submit">
                                                Verify and Continue
                                            </button>
                                        </div>

                                        <div class="mt-4 text-start">
                                            <a href="{{ route('otp.login') }}" class="auth-link">
                                                <i class="mdi mdi-arrow-left me-1"></i>
                                                Back to sign in
                                            </a>
                                        </div>
                                    </form>

                                    <div class="auth-footer text-center mt-4">
                                        &copy; Copyright
                                        <script>
                                            document.write(new Date().getFullYear())
                                        </script>
                                        All rights reserved.<br>
                                        A product of <a href="#">Solocodes</a>.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
