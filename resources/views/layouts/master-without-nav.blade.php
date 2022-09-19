<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <title> @yield('title') | eMakato</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta content="eMakato" name="description" />
    <meta content="Solocodes" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('assets/images/favicon.ico') }}">
    @include('layouts.head-css')
</head>

<body data-bs-spy="scroll" data-bs-target="#topnav-menu" data-bs-offset="60">
    <nav class="navbar navbar-expand-lg navigation bg-primary fixed-top sticky">
        <div class="container">
            <a class="navbar-logo" href="{{ url('/') }}">
                <span class="logo logo-dark font-size-24 text-dark">eMakato</span>
                <span class="logo logo-light font-size-24 text-white">eMakato</span>
                {{-- <img src="{{ URL::asset('/assets/images/logo-dark.png') }}" alt="" height="19" class="logo logo-dark">
                <img src="{{ URL::asset('/assets/images/logo-light.png') }}" alt="" height="19" class="logo logo-light"> --}}
            </a>

            <button type="button" class="btn btn-sm px-3 font-size-16 d-lg-none header-item waves-effect waves-light"
                data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                <i class="fa fa-fw fa-bars"></i>
            </button>

            <div class="collapse navbar-collapse" id="topnav-menu-content">
                <ul class="navbar-nav ms-auto" id="topnav-menu">
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ url('/') }}#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/') }}#about">About eMakato</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/') }}#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/') }}#roadmap">How it works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/') }}#team">Our Team</a>
                    </li>
                    {{-- <li class="nav-item">
                        <a class="nav-link" href="#news">News</a>
                    </li> --}}
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/') }}#faqs">FAQs</a>
                    </li>

                </ul>

                <div class="my-2 ms-lg-2">
                    <a href="{{ url('register') }}" class="btn btn-outline-success w-xs">Sign up</a>
                    <a href="{{ url('login') }}" class="btn btn-outline-danger w-xs">Sign in</a>
                </div>
            </div>
        </div>
    </nav>

    @yield('content')

    @include('layouts.vendor-scripts')
</body>

</html>
