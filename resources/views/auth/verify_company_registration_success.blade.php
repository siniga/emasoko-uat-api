@extends('layouts.master-without-nav')

@section('title')
    Track Application
@endsection

@section('body')

    <body>
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
                                            <p>Company Registration Verdict</p>
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

                                    <div class="mt-4 text-center">
                                        <p>Don't have an account ? <a href="{{ url('register') }}"
                                                class="fw-medium text-primary">
                                                Apply Now </a>
                                        </p>
                                    </div>

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
