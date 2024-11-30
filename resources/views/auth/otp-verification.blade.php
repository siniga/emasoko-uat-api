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
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body pt-0">

                            <div class="p-2">
                                @if (session()->get('success'))
                                    <div class="alert alert-success py-2">
                                        {{ session()->get('success') }}
                                    </div>
                                @elseif(session()->get('danger'))
                                    <div class="alert alert-danger py-2">
                                        {{ session()->get('danger') }}
                                    </div>
                                @endif

                                <form class="form-horizontal needs-validation" method="POST" action="{{ route('otp.getlogin') }}" novalidate>
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{$user_id}}" />
                                    <input type="hidden" name="uuid" value="{{$uuid}}" />
                                    
                                    <div class="mb-3">
                                        <label for="username" class="form-label">OTP Pin <span class="text-danger">*</span></label>
                                        <input name="otp" type="text"
                                            class="form-control @error('otp') is-invalid @enderror"
                                            value="{{ old('otp') }}" id="otp" placeholder="Enter OTP..."
                                            autocomplete="otp" required>
                                        @error('otp')
                                            <span class="invalid-feedback" role="alert">
                                                {{ $message }}
                                            </span>
                                        @enderror

                                        <div class="invalid-feedback">
                                            OTP Pin required
                                        </div>
                                    </div>

                                    <div class="mt-3 d-grid">
                                        <button class="btn btn-primary waves-effect waves-light" type="submit">
                                            Submit
                                        </button>
                                    </div>

                                    <div class="mt-4 text-sm-start">
                                        <a href="{{ route('otp.login') }}" class="text-muted"><i class="mdi mdi-home me-1"></i> Back Home</a>
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
