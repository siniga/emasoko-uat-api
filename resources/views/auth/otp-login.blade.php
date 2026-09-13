@extends('layouts.master-without-nav')

@section('title')
    @lang('translation.Login')
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/auth-otp-login.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('/assets/css/auth-login-split.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <main class="login-screen">
        <section class="login-promo" aria-labelledby="login-promo-title">
            <div class="login-promo__content">
                <a class="login-promo__brand" href="https://emakato.co.tz" aria-label="eMakato website">
                    <img src="{{ URL::asset('assets/images/emakato_logo_white.png') }}" alt="eMakato">
                </a>

                <p class="login-promo__eyebrow">Tanzania's direct debit infrastructure</p>
                <h1 id="login-promo-title" class="login-promo__title">
                    Create mandates.<br>
                    Collect payments.<br>
                    <span>Reconcile clearly.</span>
                </h1>
                <p class="login-promo__copy">
                    One secure platform for service providers to manage customer consent, scheduled collections,
                    settlement, and reporting.
                </p>

                <div class="login-capabilities" aria-label="Platform capabilities">
                    <div class="login-capability">
                        <i class="mdi mdi-shield-check-outline" aria-hidden="true"></i>
                        <span><strong>Secure</strong> Mandates</span>
                    </div>
                    <div class="login-capability">
                        <i class="mdi mdi-calendar-check-outline" aria-hidden="true"></i>
                        <span><strong>Scheduled</strong> Collections</span>
                    </div>
                    <div class="login-capability">
                        <i class="mdi mdi-chart-box-outline" aria-hidden="true"></i>
                        <span><strong>Clear</strong> Reporting</span>
                    </div>
                </div>

                @if ($integratedBanks->isNotEmpty())
                    <div class="login-banks">
                        <p class="login-banks__label">Collection Partners</p>
                        <div class="login-banks__list">
                            @foreach ($integratedBanks as $bank)
                                @php
                                    $bankSlug = strtoupper((string) $bank->slug);
                                    $configuredLogo = $bank->logo && file_exists(public_path($bank->logo))
                                        ? URL::asset(ltrim($bank->logo, '/'))
                                        : null;
                                    $bankLogo = $configuredLogo ?: ($bankSlug === 'NMB'
                                        ? URL::asset('assets/images/clients/nmb.webp')
                                        : null);
                                @endphp
                                <div class="login-bank" title="{{ $bank->name }}">
                                    @if ($bankLogo)
                                        <img src="{{ $bankLogo }}" alt="{{ $bank->name }} logo">
                                    @else
                                        <span class="login-bank__fallback" aria-hidden="true">
                                            <i class="mdi mdi-bank-outline"></i>
                                        </span>
                                        <span>{{ $bank->name }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="login-assurance">
                    <i class="mdi mdi-lock-check-outline" aria-hidden="true"></i>
                    <div>
                        <strong>Built for trusted payment operations</strong>
                        <span>Protected access, traceable approvals, and consistent collection records.</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="login-main" aria-labelledby="login-form-title">
            <div class="login-form-panel">
                <div class="login-mobile-brand">
                    <img src="{{ URL::asset('assets/images/emakato_logo_colored.png') }}" alt="eMakato">
                </div>

                <h2 id="login-form-title" class="login-form-title">Welcome back</h2>
                <p class="login-form-copy">Sign in to your eMakato account to continue.</p>

                @if (session()->get('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @elseif(session()->get('danger'))
                    <div class="alert alert-danger">{{ session()->get('danger') }}</div>
                @elseif(session()->get('error'))
                    <div class="alert alert-danger">{{ session()->get('error') }}</div>
                @endif

                <form class="needs-validation" method="POST" action="{{ route('otp.generate') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="username" class="form-label">Email address</label>
                        <input name="email" type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}" id="username" placeholder="you@company.com"
                            autocomplete="email" required autofocus>
                        @error('email')
                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback">Email is required.</div>
                    </div>

                    <div class="mb-3">
                        <div class="login-password-heading">
                            <label class="form-label mb-0" for="userpassword">Password</label>
                            @if (Route::has('forget.password.get'))
                                <a href="{{ route('forget.password.get') }}" class="login-link">Forgot password?</a>
                            @endif
                        </div>
                        <div class="input-group login-password @error('password') is-invalid @enderror">
                            <input type="password" name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                id="userpassword" placeholder="Enter your password" aria-label="Password"
                                aria-describedby="password-addon" required>
                            <button class="btn login-password__toggle" type="button" id="password-addon"
                                aria-label="Show password" title="Show password">
                                <i class="mdi mdi-eye-outline" aria-hidden="true"></i>
                            </button>
                            @error('password')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback">Password is required.</div>
                        </div>
                    </div>

                    <div class="form-check login-remember">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember"
                            {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>

                    <div class="d-grid">
                        <button class="btn btn-primary login-submit waves-effect waves-light" type="submit">
                            Sign in
                            <i class="mdi mdi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>

                    <p class="login-register">
                        New service provider?
                        <a href="{{ route('self-registration.information') }}" class="login-link">Register your company</a>
                    </p>
                </form>

                <p class="login-footer">
                    A product of <a href="https://solocodes.co.tz" target="_blank" rel="noopener">Solocodes</a>
                </p>
            </div>
        </section>
    </main>
@endsection
