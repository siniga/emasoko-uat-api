@extends('layouts.self-registration')

@section('title', 'Service Provider Registration')

@section('content')
    <div class="row justify-content-center py-5">
        <div class="col-lg-7 col-xl-6">
            <div class="card sp-card">
                <div class="card-body p-4 p-md-5 text-center">
                    <div class="mb-3">
                        <i class="bx bx-check-circle text-success" style="font-size: 3rem"></i>
                    </div>
                    <h3>{{ $saved ? 'Registration saved' : 'Information submitted successfully' }}</h3>
                    <p class="text-muted mb-4">
                        {{ $saved
                            ? 'Your service provider information has been saved in this browser session.'
                            : 'Thank you. Your application has been received successfully. Our team will review it and get back to you shortly.' }}
                    </p>
                    @if ($resumeUuid)
                        <a href="{{ route('self-registration.information', $resumeUuid) }}" class="btn btn-primary">
                            Continue registration
                        </a>
                    @else
                        <a href="{{ route('self-registration.information') }}" class="btn btn-primary">
                            Start another registration
                        </a>
                    @endif
                    <a href="{{ route('login') }}" class="btn btn-outline-secondary ms-2">
                        Sign in
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
