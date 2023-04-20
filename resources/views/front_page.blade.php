<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Welcome to eMakato | eMakato</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="eMakato" name="description" />
    <meta content="Solocodes" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('/assets/images/favicon.ico') }}">

    <!-- owl.carousel css -->
    <link rel="stylesheet" href="{{ URL::asset('/assets/libs/owl.carousel/owl.carousel.min.css') }}">

    @include('layouts.head-css')
</head>

<body data-bs-spy="scroll" data-bs-target="#topnav-menu" data-bs-offset="60">

    <nav class="navbar navbar-expand-lg navigation fixed-top sticky">
        <div class="container">
            <a class="navbar-logo" href="#">
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
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About eMakato</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#our-solution">Our Solution</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#contacts">Contacts</a>
                    </li>

                </ul>

                <div class="my-2 ms-lg-2">
                    <a href="{{ route('request-for-information') }}" class="btn btn-outline-success w-xs">Request for
                        Information</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- hero section start -->
    <section class="section hero-section bg-ico-hero" id="home">
        <div class="bg-overlay bg-primary"></div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="text-white-50">
                        <h1 class="text-white fw-semibold mb-3 hero-title">
                            Aggregate, manage and monitor your <span
                                class="font-weight-600 text-warning px-1">debit</span> <span
                                class="font-weight-600 text-success px-1">mandate.</span>
                        </h1>
                        <p class="font-size-18 text-white">
                            Enabling a seamless debit mandate ecosystem.
                        </p>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <a href="{{ route('request-for-information') }}" class="btn btn-success">Request for
                                Information</a>
                            <a href="#our-solution" class="btn btn-light">How it works</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 col-md-8 col-sm-10 ms-lg-auto">
                    <div class="card overflow-hidden">
                        <img src="{{ URL::asset('/assets/images/image2.png') }}"  width="100%" />
                    </div>
                </div>
            </div>
            <!-- end row -->
        </div>
        <!-- end container -->
    </section>
    <!-- hero section end -->

    <!-- about section start -->
    <section class="section pt-4 bg-light-grey" id="about">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="text-center mb-5">
                        <div class="font-size-24 font-weight-600 pb-2">About eMakato</div>
                        <h4 class="font-size-18 text-muted">What is eMakato?</h4>
                    </div>
                </div>
            </div>

            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="text-muted mb-4">
                        <p class="mb-2 font-size-16">
                            Powerful tool for service providers to automate their debit mandate process, interfacing
                            with
                            their customers to promote a seamless debit mandate experience.
                        </p>

                        <p class="mb-2 font-size-16">
                            Let your customers pay you in a more convenient and flexible manner.
                        </p>
                    </div>

                    <div class="">
                        <h4 class="font-size-16">Our Solution</h4>
                        <p class="mb-2 font-size-14 text-muted ">
                            <i class="mdi mdi-checkbox-marked-circle text-primary"></i>
                            Let your customers pay "small small".
                        </p>

                        <p class="mb-2 font-size-14 text-muted ">
                            <i class="mdi mdi-checkbox-marked-circle text-primary"></i>
                            Automate payments without manual interventions.
                        </p>

                        <p class="mb-2 font-size-14 text-muted ">
                            <i class="mdi mdi-checkbox-marked-circle text-primary"></i>
                            Track status of Direct Debit mandate activations.
                        </p>

                        <p class="mb-2 font-size-14 text-muted ">
                            <i class="mdi mdi-checkbox-marked-circle text-primary"></i>
                            Enjoy real-time online access to reports of transactions.
                        </p>
                    </div>
                </div>

                <div class="col-lg-6 ms-auto">
                    <div class="mt-4 mt-lg-0">
                        <video width="100%" height="320" autoplay loop muted preload="auto"
                            poster="https://cdn.twikey.net/img/v2/animatedimages/digitalmandate.jpg">
                            <source src="https://cdn.twikey.net/img/v2/animatedimages/digitalmandate.mp4"
                                type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                </div>
            </div>
            <!-- end row -->
        </div>
        <!-- end container -->
    </section>
    <!-- about section end -->


    <!-- Roadmap start -->
    <section class="section pt-4 bg-white" id="our-solution">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="text-center mb-5">
                        <div class="font-size-24 font-weight-600 pb-2">Our Solution</div>
                        <h4 class="font-size-18 text-muted">The <span class="font-weight-600 text-dark">Service</span>
                            that helps <span class="font-weight-600 text-dark">Growth </span> of your <span
                                class="font-weight-600 text-dark"> Business.</span></h4>
                    </div>
                </div>
            </div>
            <!-- end row -->

            <div class="row">
                <div class="col-lg-12 col-sm-12">
                    <!-- Nav tabs -->
                    <ul class="nav solutions-tabs nav-justified mb-4" role="tablist">
                        <li class="nav-item waves-effect waves-light">
                            <a class="nav-link active" data-bs-toggle="tab" href="#providers-1" role="tab">
                                <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                                <span class="d-none d-sm-block font-size-20 font-weight-800">
                                    Customers
                                </span>
                            </a>
                        </li>

                        <li class="nav-item waves-effect waves-light">
                            <a class="nav-link" data-bs-toggle="tab" href="#providers-2" role="tab">
                                <span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
                                <span class="d-none d-sm-block font-size-20 font-weight-800">
                                    Service Providers
                                </span>
                            </a>
                        </li>

                        <li class="nav-item waves-effect waves-light">
                            <a class="nav-link" data-bs-toggle="tab" href="#institutions-1" role="tab">
                                <span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
                                <span class="d-none d-sm-block font-size-20 font-weight-800">
                                    Financial Institutions
                                </span>
                            </a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content px-3 text-muted">
                        <div class="tab-pane active" id="providers-1" role="tabpanel">
                            <div class="row">
                                <div class="col-md-4 col-12">
                                    <div class="flex flex-col align-items-center justify-content-center">
                                        <h4 class="font-size-16 text-primary">
                                            Debit Mandate Transparency
                                        </h4>

                                        <p class="font-size-14 text-muted text-justify">
                                            eMakato goal is to reduce processing time, processing cost, make your staff
                                            less frustrated as well as as your client happy as being notified to consent
                                            at every stage of debit mandate.
                                        </p>
                                    </div>
                                </div>

                                <div class="col-md-8 col-12">
                                    <img src="{{ URL::asset('/assets/images/image2.png') }}"
                                        width="100%" />
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane" id="providers-2" role="tabpanel">
                            <div class="row">
                                <div class="col-md-4 col-12">
                                    <div class="flex flex-col align-items-center justify-content-center">
                                        <h4 class="font-size-16 text-primary">
                                            Automate debit mandate workflow
                                        </h4>

                                        <p class="font-size-14 text-muted text-justify">
                                            eMakato goal is to reduce processing time, processing cost, make your staff
                                            less frustrated as well as as your client happy as being notified to consent
                                            at every stage of debit mandate.
                                        </p>
                                    </div>
                                </div>

                                <div class="col-md-8 col-12">
                                    <img src="{{ URL::asset('/assets/images/image2.png') }}"
                                        width="100%" />
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane" id="institutions-1" role="tabpanel">
                            <div class="row">
                                <div class="col-md-4 col-12">
                                    <div class="flex flex-col align-items-center justify-content-center">
                                        <h4 class="font-size-16 text-primary">
                                            Controlling all your debit mandate.
                                        </h4>

                                        <p class="font-size-14 text-muted text-justify">
                                            eMakato goal is to reduce processing time, processing cost, make your staff
                                            less frustrated as well as as your client happy as being notified to consent
                                            at every stage of debit mandate.
                                        </p>
                                    </div>
                                </div>

                                <div class="col-md-8 col-12">
                                    <img src="{{ URL::asset('/assets/images/image2.png') }}"
                                        width="100%" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end container -->
    </section>
    <!-- Roadmap end -->


    <!-- about section start -->
    <section class="section bg-light-grey-1" id="partners">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="text-center">
                        <div class="font-size-24 font-weight-600 pb-2">Our Partners</div>
                        <h4 class="font-size-18 text-muted">Trusted by Some of Africa's Most Exciting Businesses</h4>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="owl-carousel owl-theme clients-carousel" id="clients-carousel" dir="ltr">
                        <div class="item">
                            <div class="client-images">
                                <img src="{{ URL::asset('/assets/images/clients/nmb_logo.jpeg') }}" alt="partner-img"
                                    class="mx-auto d-block">
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end row -->

        </div>
    </section>

    <!-- Footer start -->
    <footer class="landing-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-sm-6">
                    <div class="mb-4 mb-lg-0">
                        <h5 class="mb-3 footer-list-title">eMakato</h5>
                        <p class="font-size-12 text-white">
                            15th Floor, Victoria Noble Centre
                        </p>

                        <p class="font-size-12 text-white">Dar es Salaam, Tanzania</p>

                        <p class="font-size-12 text-white">Tel: <a class="font-size-12 text-white"
                                href="tel:+255717705746" target="_blank" rel="noopener noreferrer">+255 717 705
                                746</a>
                        </p>
                        <p> </p>

                        <p class="font-size-12 text-white">
                            Write to us through our email address: <a class="font-size-12 text-white"
                                href="mailto:info@solocodes.io">info@solocodes.io</a>
                        </p>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-6">
                    <div class="mb-4 mb-lg-0">
                        <h5 class="mb-3 footer-list-title">Company</h5>
                        <ul class="list-unstyled footer-list-menu">
                            <li><a href="javascript: void(0);">About eMakato</a></li>
                            <li><a href="javascript: void(0);">Privacy Policy</a></li>
                            <li><a href="javascript: void(0);">Terms & Conditions</a></li>
                            <li><a href="javascript: void(0);">FAQs</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-6">
                    <div class="mb-4 mb-lg-0">
                        <h5 class="mb-3 footer-list-title">Features</h5>
                        <ul class="list-unstyled footer-list-menu">
                            <li><a href="#">eMakato</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-6">
                    <div class="mb-4 mb-lg-0">
                        <h5 class="mb-3 footer-list-title">Resources</h5>
                        <ul class="list-unstyled footer-list-menu">
                            <li><a href="javascript: void(0);">Developers</a></li>
                            <li><a href="javascript: void(0);">Roadmap</a></li>
                            <li><a href="javascript: void(0);">Support</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- end row -->

            <hr class="footer-border my-2">

            <div class="row my-2">
                <div class="col-lg-6">
                    <p class="mb-0 text-white">
                        © Copyright
                        <script>
                            document.write(new Date().getFullYear())
                        </script> All rights reserved.

                        <br />A product of <a href="#" class="text-muted">Solocodes</a>.
                    </p>
                </div>

                <div class="col-lg-6">
                    <div class="mt-2 d-flex justify-content-end">
                        <ul class="header-social-icon">
                            <li>
                                <a href="#" target="_blank"><i class="mdi mdi-twitter text-white"></i></a>
                            </li>
                            <li>
                                <a href="#" target="_blank"><i class="mdi mdi-facebook text-white"></i></a>
                            </li>
                            <li>
                                <a href="#" target="_blank"><i class="mdi mdi-instagram text-white"></i></a>
                            </li>
                            <li>
                                <a href="#" target="_blank"><i class="mdi mdi-youtube text-white"></i></a>
                            </li>
                            <li>
                                <a href="#" target="_blank"><i class="mdi mdi-linkedin text-white"
                                        data-path="4"></i></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- end container -->
    </footer>
    <!-- Footer end -->

    @include('layouts.vendor-scripts')

    <script src="{{ URL::asset('/assets/libs/jquery.easing/jquery.easing.min.js') }}"></script>

    <!-- Plugins js-->
    <script src="{{ URL::asset('/assets/libs/jquery-countdown/jquery-countdown.min.js') }}"></script>

    <!-- owl.carousel js -->
    <script src="{{ URL::asset('/assets/libs/owl.carousel/owl.carousel.min.js') }}"></script>

    <!-- ICO landing init -->
    <script src="{{ URL::asset('/assets/js/pages/ico-landing.init.js') }}"></script>

    <script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>

</body>

</html>
