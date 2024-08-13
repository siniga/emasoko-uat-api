@extends('layouts.master-without-nav')

@section('title')
    @lang('translation.Login')
@endsection

@section('content')
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">

                    <div class="card bg-white border overflow-hidden">
                        <div class="my-2">
                            <div class="row">
                                <div class="col-12">
                                    <div class="text-center py-2 mb-4">
                                        <img src="{{ URL::asset('assets/images/emakato_logo_colored.png') }}" alt="" height="48">
                                    </div>

                                    
                                    <div class="text-center">
                                        <h5 class="text-gray-800 font-size-18">Login to your eMakato account!</h5>
                                        <p class="text-muted font-size-14">Please provide your credentials.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body pt-0">

                            <div class="p-2">
                                @if (session()->get('success'))
                                    <div class="alert alert-success">
                                        {{ session()->get('success') }}
                                    </div><br />
                                @elseif(session()->get('danger'))
                                    <div class="alert alert-danger">
                                        {{ session()->get('danger') }}
                                    </div><br />
                                @endif

                                <form class="form-horizontal needs-validation" method="POST" action="{{ route('otp.generate') }}" novalidate>
                                    @csrf
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Email</label>
                                        <input name="email" type="email"
                                            class="form-control @error('email') is-invalid @enderror"
                                            value="{{ old('email') }}" id="username" placeholder="Enter Email..."
                                            autocomplete="email" required>
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
                                        <label for="username" class="form-label">Phone</label>
                                        <input name="phone" type="text"
                                            class="form-control @error('phone') is-invalid @enderror"
                                            value="{{ old('phone') }}" id="username" placeholder="Enter phone..."
                                            autocomplete="phone" required>
                                        @error('phone')
                                            <span class="invalid-feedback" role="alert">
                                                {{ $message }}
                                            </span>
                                        @enderror

                                        <div class="invalid-feedback">
                                            Phone required
                                        </div>
                                    </div>

 

                                    <div class="mt-3 d-grid">
                                        <button class="btn btn-primary waves-effect waves-light" type="submit">
                                            Submit
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 text-center">
                        <div>
                            <p class="mb-0">
                                © Copyright
                                <script>
                                    document.write(new Date().getFullYear())
                                </script>All rights reserved.

                               <br /> A product of <a href="#" class="text-muted">Solocodes</a>.
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- end account-pages -->
@endsection
