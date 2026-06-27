<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | eMakato</title>
    <link rel="shortcut icon" href="{{ URL::asset('assets/images/favicon.png') }}">
    @include('layouts.head-css')
    <link href="{{ URL::asset('/assets/css/self-registration.css') }}" rel="stylesheet" type="text/css">
</head>
<body class="self-registration-body">
    <div class="self-registration-frame">
        <aside class="self-registration-brand">
            <div class="self-registration-brand__content">
                <a href="{{ url('https://emakato.co.tz') }}" class="self-registration-brand__logo">
                    <img src="{{ URL::asset('assets/images/emakato_logo_white.png') }}" alt="eMakato">
                </a>

                <div class="self-registration-brand__message">
                    <span class="self-registration-brand__eyebrow">Service providers</span>
                    <h1>Start collecting payments with confidence.</h1>
                    <p>Register your business, supporting documents, and contact representative in one guided process.</p>
                </div>

                <div class="self-registration-brand__signin">
                    <span>Already registered?</span>
                    <a href="{{ route('login') }}">Login</a>
                </div>
            </div>
        </aside>

        <main class="self-registration-workspace">
            <div class="self-registration-workspace__inner">
                <div class="self-registration-mobile-brand">
                    <img src="{{ URL::asset('assets/images/emakato_logo_colored.png') }}" alt="eMakato">
                    <a href="{{ route('login') }}">Login</a>
                </div>

            @yield('content')
            </div>
        </main>
    </div>

    @include('layouts.vendor-scripts')
</body>
</html>
