@extends('layouts.master-without-nav')

@section('title')
    Track Application
@endsection


@section('content')
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card overflow-hidden">
                        <div class="bg-primary bg-soft">
                            <div class="row">
                                <div class="col-12">
                                    <div class="text-primary p-4">
                                        <h5 class="text-primary">Track Application!</h5>
                                        <p>We sent a verification code to your email. Enter the code from the email in the
                                            field below.</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="auth-logo">
                                <a href="{{ route('login') }}" class="auth-logo-light">
                                    <div class="avatar-md profile-user-wid mb-4">
                                        <span class="avatar-title rounded-circle bg-light">
                                            <img src="{{ URL::asset('/assets/images/logo-light.svg') }}" alt=""
                                                class="rounded-circle" height="34">
                                        </span>
                                    </div>
                                </a>

                                <a href="{{ route('login') }}" class="auth-logo-dark">
                                    <div class="avatar-md profile-user-wid mb-4">
                                        <span class="avatar-title rounded-circle bg-light">
                                            <img src="{{ URL::asset('/assets/images/logo.svg') }}" alt=""
                                                class="rounded-circle" height="34">
                                        </span>
                                    </div>
                                </a>
                            </div>

                            <div class="p-2">
                                @if (session()->get('success'))
                                    <div class="alert alert-success">
                                        {{ session()->get('success') }}
                                    </div><br />
                                @elseif(session()->get('danger'))
                                    <div class="alert alert-danger">
                                        {!! session()->get('danger') !!}
                                    </div><br />
                                @endif

                                <form class="form-horizontal needs-validation" method="POST"
                                    action="{{ route('company.verify-registration', $company->uuid) }}" novalidate>
                                    @csrf

                                    <div class="mb-3">
                                        <label for="username" class="form-label">Verification code</label>
                                        <input name="verification_code" type="text"
                                            class="form-control input-mask @error('verification_code') is-invalid @enderror"
                                            value="{{ old('verification_code') }}" id="verification_code"
                                            placeholder="Enter verification code..." autocomplete="verification_code"
                                            data-inputmask="'mask': '999999'" required>
                                        @error('verification_code')
                                            <span class="invalid-feedback" role="alert">
                                                {{ $message }}
                                            </span>
                                        @enderror
                                        <div class="invalid-feedback">
                                            This field required
                                        </div>
                                    </div>

                                    <div class="mt-3 d-grid">
                                        <button class="btn btn-primary waves-effect waves-light" type="submit">
                                            Verify
                                        </button>
                                    </div>


                                    <div class="mt-4 text-center">
                                        <p>Don't have an account ? <a href="{{ url('register') }}"
                                                class="fw-medium text-primary">
                                                Apply Now </a>
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 text-center">
                        <div>
                            <p>
                                <script>
                                    document.write(new Date().getFullYear())
                                </script> eMakato. All rights reserved.
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- end account-pages -->
@endsection
