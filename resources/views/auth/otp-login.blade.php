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
            color: #ffe3a5;
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
            padding: 0.9rem 1rem;
            color: #52616d;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .auth-input {
            border-radius: 14px;
            min-height: 48px;
            border: 1px solid #d6e1e7;
            background: #fbfdfe;
            padding-left: 0.95rem;
        }

        .auth-input:focus {
            border-color: #153567;
            box-shadow: 0 0 0 0.18rem rgba(21, 53, 103, 0.16);
            background: #ffffff;
        }

        .auth-pass-btn {
            border-radius: 0 14px 14px 0 !important;
            border: 1px solid #d6e1e7 !important;
            border-left: 0 !important;
            background: #fbfdfe !important;
            color: #59707d !important;
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
                                        <i class="mdi mdi-shield-check-outline"></i>
                                        Direct Debit Platform
                                    </span>

                                    <h2 class="auth-story-title">Manage mandates, collections, and approvals from one secure workspace.</h2>

                                    <p class="auth-story-copy">
                                        Sign in with your email and password, then confirm your one-time verification code to continue into eMakato.
                                    </p>

                                    <ul class="auth-points">
                                        <li>
                                            <i class="mdi mdi-check-decagram-outline"></i>
                                            <span>Secure two-step login with OTP sent to your registered phone and email.</span>
                                        </li>
                                        <li>
                                            <i class="mdi mdi-finance"></i>
                                            <span>Track mandate performance, collection schedules, and payment progress in one place.</span>
                                        </li>
                                        <li>
                                            <i class="mdi mdi-office-building-cog-outline"></i>
                                            <span>Built for service providers managing direct debit operations at scale.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="auth-form-wrap">
                                    <div class="auth-brand">
                                        <img src="{{ URL::asset('assets/images/emakato_logo_colored.png') }}" alt="eMakato">
                                    </div>

                                    <h1 class="auth-form-title">Sign in to eMakato</h1>
                                    <p class="auth-form-copy">
                                        Enter your account credentials to request a login OTP and continue to your dashboard.
                                    </p>

                                    <div class="auth-step-note">
                                        <strong>How it works:</strong> after submitting your credentials, we send a one-time code for verification before access is granted.
                                    </div>

                                    @if (session()->get('success'))
                                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                                    @elseif(session()->get('danger'))
                                        <div class="alert alert-danger">{{ session()->get('danger') }}</div>
                                    @elseif(session()->get('error'))
                                        <div class="alert alert-danger">{{ session()->get('error') }}</div>
                                    @endif

                                    <form class="form-horizontal needs-validation" method="POST"
                                        action="{{ route('otp.generate') }}" novalidate>
                                        @csrf

                                        <div class="mb-3">
                                            <label for="username" class="form-label fw-semibold">Email Address</label>
                                            <input name="email" type="email"
                                                class="form-control auth-input @error('email') is-invalid @enderror"
                                                value="{{ old('email') }}" id="username"
                                                placeholder="name@company.com" autocomplete="email" required>
                                            @error('email')
                                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                            @enderror
                                            <div class="invalid-feedback">Email required</div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold" for="userpassword">Password</label>
                                            <div
                                                class="input-group auth-pass-inputgroup @error('password') is-invalid @enderror">
                                                <input type="password" name="password"
                                                    class="form-control auth-input @error('password') is-invalid @enderror"
                                                    id="userpassword" placeholder="Enter your password"
                                                    aria-label="Password" aria-describedby="password-addon" required>
                                                <button class="btn auth-pass-btn" type="button" id="password-addon">
                                                    <i class="mdi mdi-eye-outline"></i>
                                                </button>
                                                @error('password')
                                                    <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                                @enderror
                                                <div class="invalid-feedback">Password required</div>
                                            </div>
                                        </div>

                                        <div
                                            class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-2">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="checkbox" id="remember"
                                                    {{ old('remember') ? 'checked' : '' }}>
                                                <label class="form-check-label text-muted" for="remember">
                                                    Remember me
                                                </label>
                                            </div>

                                            @if (Route::has('forget.password.get'))
                                                <a href="{{ route('forget.password.get') }}"
                                                    class="text-decoration-none small fw-semibold"
                                                    style="color: #153567;">
                                                    Forgot password?
                                                </a>
                                            @endif
                                        </div>

                                        <div class="d-grid">
                                            <button class="btn btn-primary auth-submit waves-effect waves-light"
                                                type="submit">
                                                Continue to OTP Verification
                                            </button>
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
