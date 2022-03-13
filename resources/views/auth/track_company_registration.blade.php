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
                                            <p>Please provide company email and TIN Number to track application.</p>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="auth-logo">
                                    <a href="#" class="auth-logo-light">
                                        <div class="avatar-md profile-user-wid mb-4">
                                            <span class="avatar-title rounded-circle bg-light">
                                                <img src="{{ URL::asset('/assets/images/logo-light.svg') }}" alt=""
                                                    class="rounded-circle" height="34">
                                            </span>
                                        </div>
                                    </a>

                                    <a href="#" class="auth-logo-dark">
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

                                    <form class="form-horizontal" method="POST" action="{{ route('company.track-registration') }}">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Company Email</label>
                                            <input name="email" type="email"
                                                class="form-control @error('email') is-invalid @enderror"
                                                value="{{ old('email') }}" id="username" placeholder="Enter Email..."
                                                autocomplete="email" autofocus>
                                            @error('email')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="username" class="form-label">Company TIN Number</label>
                                            <input name="tin_number" type="text"
                                                class="form-control input-mask @error('tin_number') is-invalid @enderror"
                                                value="{{ old('tin_number') }}" id="tin_number" placeholder="Enter TIN Number..."
                                                autocomplete="tin_number" data-inputmask="'mask': '999-999-999'" autofocus>
                                            @error('tin_number')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>

                                        <div class="mt-3 d-grid">
                                            <button class="btn btn-primary waves-effect waves-light" type="submit">
                                                Track
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
