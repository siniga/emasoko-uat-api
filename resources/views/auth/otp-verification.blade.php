@extends('layouts.master-without-nav')

@section('title')
    @lang('translation.Login')
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/auth-otp-login.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="auth-shell">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-10 col-lg-11">
                    <div class="card auth-panel">
                        <div class="row g-0">
                            <div class="col-lg-6 d-none d-lg-block">
                                <div class="auth-story">
                                    <div class="auth-story-top">
                                         <span class="auth-badge">
                                            <i class="mdi mdi-shield-check-outline"></i>
                                            Direct Debit Platform
                                        </span>

                                        <h2 class="auth-story-title">Manage mandates and collections from one secure workspace.</h2>

                                        <p class="auth-story-copy">
                                            Built for teams handling direct debit operations with clear approvals,
                                            secure access, and reliable payment workflows.
                                        </p>
                                    </div>

                                    <div class="auth-story-bottom">
                                        <ul class="auth-points">
                                            <li>
                                                <i class='bx bxs-badge-check'></i>
                                                <span>
                                                    <span class="auth-point-title">Secure Mandate</span>
                                                    <span class="auth-point-copy">Secure way to create debit debit.</span>
                                                </span>
                                            </li>
                                            <li>
                                                <i class='bx bxs-chart'></i>
                                                <span>
                                                    <span class="auth-point-title">Collections oversight</span>
                                                    <span class="auth-point-copy">Track mandate activity, schedules, and payment progress in one place.</span>
                                                </span>
                                            </li>
                                            <li>
                                                <i class='bx bxs-chart'></i>
                                                <span>
                                                    <span class="auth-point-title">Built for providers</span>
                                                    <span class="auth-point-copy">Designed for service providers managing direct debit operations at scale.</span>
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="auth-form-wrap">
                                    <div class="auth-brand">
                                        <img src="{{ URL::asset('assets/images/emakato_logo_colored.png') }}" alt="eMakato">
                                    </div>

                                    <h1 class="auth-form-title">Enter verification code</h1>
                                    <p class="auth-form-copy">
                                        Confirm the OTP sent to your registered contacts so we can securely complete your sign-in.
                                    </p>

                                    @if (session()->get('success'))
                                        <div class="alert alert-success py-2">{{ session()->get('success') }}</div>
                                    @elseif(session()->get('danger'))
                                        <div class="alert alert-danger py-2">{{ session()->get('danger') }}</div>
                                    @elseif(session()->get('error'))
                                        <div class="alert alert-danger py-2">{{ session()->get('error') }}</div>
                                    @endif

                                    <div class="auth-form-card">
                                        <form class="form-horizontal needs-validation" method="POST"
                                            action="{{ route('otp.getlogin') }}" novalidate>
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $user_id }}" />
                                            <input type="hidden" name="uuid" value="{{ $uuid }}" />

                                            <div class="mb-3">
                                                <label for="otp" class="form-label">OTP Pin</label>
                                                <input name="otp" type="text"
                                                    class="form-control auth-otp-input @error('otp') is-invalid @enderror"
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
                                                <a href="{{ route('otp.login') }}" class="auth-link text-decoration-none">
                                                    <i class="mdi mdi-arrow-left me-1"></i>
                                                    Back to sign in
                                                </a>
                                            </div>
                                        </form>
                                    </div>

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
