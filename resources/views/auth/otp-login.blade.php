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

                                        <h2 class="auth-story-title">Manage mandates and collections from one secure
                                            workspace.</h2>

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
                                                    <span class="auth-point-copy">Track mandate activity, schedules, and
                                                        payment progress in one place.</span>
                                                </span>
                                            </li>
                                            <li>
                                                <i class='bx bxs-chart'></i>
                                                <span>
                                                    <span class="auth-point-title">Built for providers</span>
                                                    <span class="auth-point-copy">Designed for service providers managing
                                                        direct debit operations at scale.</span>
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="auth-form-wrap">
                                    <div class="auth-brand">
                                        <a href="{{ url('https://emakato.co.tz') }}">
                                            <img src="{{ URL::asset('assets/images/emakato_logo_colored.png') }}"
                                                alt="eMakato">
                                        </a>
                                    </div>

                                    <h1 class="auth-form-title">Sign in to eMakato</h1>
                                    <p class="auth-form-copy">
                                        Enter your account credentials to login.
                                    </p>

                                    @if (session()->get('success'))
                                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                                    @elseif(session()->get('danger'))
                                        <div class="alert alert-danger">{{ session()->get('danger') }}</div>
                                    @elseif(session()->get('error'))
                                        <div class="alert alert-danger">{{ session()->get('error') }}</div>
                                    @endif

                                    <div class="auth-form-card">
                                        <form class="form-horizontal needs-validation" method="POST"
                                            action="{{ route('otp.generate') }}" novalidate>
                                            @csrf

                                            <div class="mb-3">
                                                <label for="username" class="form-label">Email Address</label>
                                                <input name="email" type="email"
                                                    class="form-control @error('email') is-invalid @enderror"
                                                    value="{{ old('email') }}" id="username"
                                                    placeholder="name@company.com" autocomplete="email" required>
                                                @error('email')
                                                    <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                                @enderror
                                                <div class="invalid-feedback">Email required</div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label" for="userpassword">Password</label>
                                                <div
                                                    class="input-group auth-pass-inputgroup @error('password') is-invalid @enderror">
                                                    <input type="password" name="password"
                                                        class="form-control @error('password') is-invalid @enderror"
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
                                                        name="remember" {{ old('remember') ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="remember">
                                                        Remember me
                                                    </label>
                                                </div>

                                                @if (Route::has('forget.password.get'))
                                                    <a href="{{ route('forget.password.get') }}"
                                                        class="auth-link text-decoration-none">
                                                        Forgot password?
                                                    </a>
                                                @endif
                                            </div>

                                            <div class="d-grid">
                                                <button class="btn btn-primary auth-submit waves-effect waves-light"
                                                    type="submit">
                                                    Login
                                                </button>
                                            </div>

                                            <div class="text-center mt-3">
                                                <span class="text-muted">New service provider?</span>
                                                <a href="{{ route('self-registration.information') }}"
                                                    class="auth-link text-decoration-none ms-1">
                                                    Register your company
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
