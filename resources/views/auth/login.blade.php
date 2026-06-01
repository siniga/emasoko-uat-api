@extends('layouts.master-without-nav')

@section('title')
    @lang('translation.Login')
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/auth-login.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="login-shell">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-10 col-lg-11">
                    <div class="card login-card">
                        <div class="row g-0">
                            <div class="col-lg-5 d-none d-lg-block">
                                <div class="login-aside">
                                    <span class="login-badge">
                                        <i class="mdi mdi-shield-check-outline"></i>
                                        Secure Access
                                    </span>

                                    <h2>Access your eMakato workspace without the page shifting or stretching.</h2>
                                    <p>
                                        Sign in to manage mandates, collections, and approvals from one clean dashboard.
                                    </p>

                                    <ul class="login-points">
                                        <li>
                                            <i class="mdi mdi-check-decagram-outline"></i>
                                            <span>Focused sign-in experience with a fixed viewport layout.</span>
                                        </li>
                                        <li>
                                            <i class="mdi mdi-form-select"></i>
                                            <span>Form fields now follow the shared eMakato form styling direction.</span>
                                        </li>
                                        <li>
                                            <i class="mdi mdi-lock-outline"></i>
                                            <span>Secure access for teams handling direct debit operations.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="login-form-wrap">
                                    <div class="login-brand">
                                        <img src="{{ URL::asset('assets/images/emakato_logo_colored.png') }}"
                                            alt="eMakato">
                                    </div>

                                    <h1 class="login-title">Login to your eMakato account</h1>
                                    <p class="login-copy">Please provide your credentials to continue.</p>

                                    @if (session()->get('success'))
                                        <div class="alert alert-success">
                                            {{ session()->get('success') }}
                                        </div>
                                    @elseif(session()->get('danger'))
                                        <div class="alert alert-danger">
                                            {{ session()->get('danger') }}
                                        </div>
                                    @endif

                                    <form class="login-form form-horizontal needs-validation" method="POST"
                                        action="{{ route('auth.login') }}" novalidate>
                                        @csrf
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Email Address</label>
                                            <input name="email" type="email"
                                                class="form-control @error('email') is-invalid @enderror"
                                                value="{{ old('email') }}" id="username"
                                                placeholder="name@company.com" autocomplete="email" required>
                                            @error('email')
                                                <span class="invalid-feedback" role="alert">
                                                    {{ $message }}
                                                </span>
                                            @enderror

                                            <div class="invalid-feedback">
                                                Email required
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="userpassword">Password</label>
                                            <div
                                                class="input-group auth-pass-inputgroup @error('password') is-invalid @enderror">
                                                <input type="password" name="password"
                                                    class="form-control @error('password') is-invalid @enderror"
                                                    id="userpassword" placeholder="Enter your password"
                                                    aria-label="Password" aria-describedby="password-addon" required>
                                                <button class="btn" type="button" id="password-addon">
                                                    <i class="mdi mdi-eye-outline"></i>
                                                </button>
                                                @error('password')
                                                    <span class="invalid-feedback" role="alert">
                                                        {{ $message }}
                                                    </span>
                                                @enderror

                                                <div class="invalid-feedback">
                                                    Password required
                                                </div>
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
                                                    class="login-link text-decoration-none">
                                                    Forgot your password?
                                                </a>
                                            @endif
                                        </div>

                                        <div class="d-grid">
                                            <button class="btn btn-primary login-submit waves-effect waves-light"
                                                type="submit">
                                                Login
                                            </button>
                                        </div>
                                    </form>

                                    <div class="login-footer text-center mt-4">
                                        &copy; Copyright
                                        <script>
                                            document.write(new Date().getFullYear())
                                        </script>
                                        All rights reserved.<br>
                                        A product of <a href="#" class="text-muted">Solocodes</a>.
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
