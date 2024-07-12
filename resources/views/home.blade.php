<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> @yield('title') | e-Makato</title>
    <meta content="e-Makato" name="description" />
    <meta content="Laina Finance" name="author" />
    <meta name="website" content="https://lainafinance.co.tz" />
    <meta name="keywords" content="Onepage, creative, modern, bootstrap 5, multipurpose, clean" />
    <meta name="email" content="support@lainafinance.co.tz" />
    <meta name="version" content="1.0.0" />
    <!-- favicon -->
    <link href="images/favicon.ico" rel="shortcut icon">
    <!-- Bootstrap core CSS -->
    <link href="{{ asset('assets/fronter/css/bootstrap.min.css') }}" type="text/css" rel="stylesheet" />
    <!-- Slider -->
    <link href="{{ asset('assets/fronter/css/tiny-slider.css') }}" rel="stylesheet" />
    <!-- Tobii -->
    <link href="{{ asset('assets/fronter/css/tobii.min.css') }}" rel="stylesheet" type="text/css" />
    <!--Material Icon -->
    <link href="css/materialdesignicons.min.css" rel="stylesheet" type="text/css" />
    <link href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" rel="stylesheet">
    <!-- Custom  Css -->
    <link href="{{ asset('assets/fronter/css/style.min.css') }}" rel="stylesheet" type="text/css" id="theme-opt" />
    <link href="{{ asset('assets/fronter/css/default.css') }}" rel="stylesheet" id="color-opt">
</head>

<body>
    <!-- Navbar STart -->
    <header id="topnav" class="defaultscroll sticky bg-white">
        <div class="container">
            <!-- Logo container-->
            <a class="logo" href="index-startup.html">
                <img src="images/logo-dark.png" class="logo-light-mode" alt="">
                <img src="images/logo-light.png" class="logo-dark-mode" alt="">
            </a>
            <!-- Logo End -->

            <!-- Start navbar Toggle-->
            <div class="menu-extras">
                <div class="menu-item">
                    <!-- Mobile menu toggle-->
                    <a class="navbar-toggle" id="isToggle" onclick="toggleMenu()">
                        <div class="lines">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </a>
                    <!-- End mobile menu toggle-->
                </div>
            </div>
            <!-- End navbar Toggle-->

            <div id="navigation">
                <!-- Navigation Menu-->
                <ul class="navigation-menu nav-right" id="navmenu-nav">
                    <li class="has-submenu">
                        <a href="#home">Home</a>
                    </li>
                    <li class="has-submenu">
                        <a href="#about">About Us</a>
                    </li>
                    <li class="has-submenu">
                        <a href="#features">Feature</a>
                    </li>
                    <li class="has-submenu">
                        <a href="#testimonial">Reviews</a>
                    </li>
                    <li class="has-submenu">
                        <a href="#news">Blog</a>
                    </li>
                    <li class="has-submenu">
                        <a href="#contact">Contact</a>
                    </li>

                    
                </ul>
                <!--end navigation menu-->
            </div>
            <!--end navigation-->
        </div>
        <!--end container-->
    </header>
    <!--end header-->
    <!-- Navbar End -->

    <!-- Hero Start -->
    <section class="bg-home startup-wrapper d-flex align-items-center overflow-hidden" id="home">
        <div class="bg-overlay bg-transparent" id="overlay"></div>
        <div class="container">
            <div class="row mt-5">
                <div class="col-12">
                    <div class="title-heading">
                        <img src="images/logo-icon-36.png" alt="">
                        <h4 class="display-5 fw-bold my-4">Build, Grow, And <br> Manage Your Brand</h4>
                        <p class="text-muted para-desc mb-0">Start working with Fronter that can provide everything you
                            need to generate awareness, drive traffic, connect.</p>

                        <div class="mt-4">
                            <a href="{{ route('login')}}" class="btn btn-primary">Get Started</a>
                            <a href="#!" data-type="youtube" data-id="yba7hPeTSjk"
                                class="btn btn-icon btn-pills btn-primary lightbox ms-2"><i data-feather="video"
                                    class="fea icon-sm"></i></a><span class="fw-normal align-middle ms-2">Watch
                                Now</span>
                        </div>
                    </div>

                    <img src="{{ asset('assets/fronter/images/square-danger.png') }}"
                        class="img-fluid rounded-pill bg-image-position avatar avatar-md-md mover-2 z-index-0" alt="">
                    <img src="assets/images/square-success.png"
                        class="img-fluid rounded-md avatar avatar-md-md bg-image-position-2 spin-anything z-index-0"
                        alt="">
                </div>
                <!--end col-->
            </div>
            <!--end row-->
            <img src="{{ asset('assets/fronter/images/square-primary.png') }}"
                class="img-fluid rounded-pill position-absolute top-0 start-50 translate-middle-x avatar avatar-md-md zoom-in-out z-index-0"
                alt="">
        </div>
        <!--end container-->
    </section>
    <!--end section-->
    <!-- Hero End -->

    <!-- Start -->
    <section class="section-two bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mt-4 pt-2">
                    <div class="d-flex features feature-primary">
                        <div class="feature-icon text-center">
                            <i class="uil uil-water rounded h4"></i>
                        </div>
                        <div class="flex-1 ms-4">
                            <h5 class="mt-0">24/7 Support</h5>
                            <p class="text-muted mb-0">The phrasal sequence of the Lorem Ipsum text is now so that many
                                generate</p>
                        </div>
                    </div>
                </div><!-- End Col -->

                <div class="col-lg-4 col-md-6 mt-4 pt-2">
                    <div class="d-flex features feature-primary">
                        <div class="feature-icon text-center">
                            <i class="uil uil-dropbox rounded h4"></i>
                        </div>
                        <div class="flex-1 ms-4">
                            <h5 class="mt-0">Take Ownership</h5>
                            <p class="text-muted mb-0">The phrasal sequence of the Lorem Ipsum text is now so that many
                                generate</p>
                        </div>
                    </div>
                </div><!-- End Col -->

                <div class="col-lg-4 col-md-6 mt-4 pt-2">
                    <div class="d-flex features feature-primary">
                        <div class="feature-icon text-center">
                            <i class="uil uil-focus-target rounded h4"></i>
                        </div>
                        <div class="flex-1 ms-4">
                            <h5 class="mt-0">Team Work</h5>
                            <p class="text-muted mb-0">The phrasal sequence of the Lorem Ipsum text is now so that many
                                generate</p>
                        </div>
                    </div>
                </div><!-- End Col -->
            </div>
            <!--end row-->
        </div>
        <!--end container-->
    </section>
    <!--end section-->
    <!-- End -->

    <!-- Start -->
    <section class="section" id="about">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="about-image position-relative">
                        <img src="{{ asset('assets/fronter/images/about01.jpg') }}" class="img-fluid rounded shadow" alt="">

                        <div class="about-image-position">
                            <img src="{{ asset('assets/fronter/images/about02.jpg') }}" class="img-fluid rounded shadow-lg" alt="">
                            <div class="play-icon">
                                <a href="#!" data-type="youtube" data-id="yba7hPeTSjk" class="play-btn lightbox">
                                    <i class="mdi mdi-play text-primary rounded-circle bg-white shadow-lg"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end col-->

                <div class="col-md-6 mt-4 pt-2 mt-sm-0 pt-sm-0">
                    <div class="section-title ms-lg-5">
                        <h6 class="text-primary fw-normal">Startup About</h6>
                        <h4 class="title mb-4">We are a Big Team</h4>
                        <p class="text-muted">Launch your campaign and benefit from our expertise on designing and
                            managing conversion centered bootstrap html page.</p>
                        <p class="text-muted mb-0">It seems that only fragments of the original text remain in the Lorem
                            Ipsum texts used today. One may speculate that over the course of time certain letters were
                            added or deleted at various positions within the text. This might also explain why one can
                            now find slightly different versions.</p>

                        <div class="mt-4 pt-2">
                            <a href="javascript:void(0)" class="btn btn-primary">Read More <i
                                    class="uil uil-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->
        </div>
        <!--end container-->
    </section>
    <!--end section-->
    <!-- End -->

    <!-- CTA Start -->
    <section class="section" data-jarallax='{"speed": 0.5}'
        style="background: url('assets/fronter/images/bg01.jpg') center center;" id="cta">
        <div class="bg-overlay"></div>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 text-center">
                    <div class="section-title">
                        <h4 class="title title-dark text-white mb-4">Stop leaving money on the table.</h4>
                        <p class="text-light para-dark para-desc mx-auto">Start working with Fronter that can provide
                            everything you need to generate awareness, drive traffic, connect.</p>
                        <a href="#!" data-type="youtube" data-id="yba7hPeTSjk"
                            class="play-btn border border-light mt-4 lightbox">
                            <i data-feather="play" class="fea icon-ex-md text-white title-dark"></i>
                        </a>
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->
        </div>
        <!--end container-->
    </section>
    <!--end section-->
    <!-- CTA End -->

    <!-- Start -->
    <section class="section bg-soft-primary" id="features">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="section-title text-center mb-4 pb-2">
                        <h4 class="title text-dark mb-3">Our Services</h4>
                        <p class="text-muted para-dark para-desc mx-auto">Start working with Fronter that can provide
                            everything you need to generate awareness, drive traffic, connect.</p>
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->

            <div class="row">
                <div class="col-lg-4 col-sm-6 mt-4 pt-2">
                    <div
                        class="card features feature-primary feature-clean feature-transition p-4 border-0 shadow rounded overflow-hidden">
                        <div class="icons text-center rounded">
                            <i class="uil uil-presentation-line d-block rounded h4 mb-0"></i>
                        </div>
                        <div class="content mt-4">
                            <a href="page-service-detail.html" class="h5 text-dark title">Digital Marketing</a>
                            <p class="text-muted mt-3">The phrasal sequence of the Lorem Ipsum text is now so that many
                                DTP programmes can generate</p>

                            <a href="page-service-detail.html" class="link">Read more <i
                                    class="uil uil-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <!--end col-->

                <div class="col-lg-4 col-sm-6 mt-4 pt-2">
                    <div
                        class="card features feature-primary feature-clean feature-transition p-4 border-0 shadow rounded overflow-hidden">
                        <div class="icons text-center rounded">
                            <i class="uil uil-bill d-block rounded h4 mb-0"></i>
                        </div>
                        <div class="content mt-4">
                            <a href="page-service-detail.html" class="h5 text-dark title">Investing</a>
                            <p class="text-muted mt-3">The phrasal sequence of the Lorem Ipsum text is now so that many
                                DTP programmes can generate</p>

                            <a href="page-service-detail.html" class="link">Read more <i
                                    class="uil uil-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <!--end col-->

                <div class="col-lg-4 col-sm-6 mt-4 pt-2">
                    <div
                        class="card features feature-primary feature-clean feature-transition p-4 border-0 shadow rounded overflow-hidden">
                        <div class="icons text-center rounded">
                            <i class="uil uil-money-withdrawal d-block rounded h4 mb-0"></i>
                        </div>
                        <div class="content mt-4">
                            <a href="page-service-detail.html" class="h5 text-dark title">Mortgage Advisor</a>
                            <p class="text-muted mt-3">The phrasal sequence of the Lorem Ipsum text is now so that many
                                DTP programmes can generate</p>

                            <a href="page-service-detail.html" class="link">Read more <i
                                    class="uil uil-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <!--end col-->

                <div class="col-lg-4 col-sm-6 mt-4 pt-2">
                    <div
                        class="card features feature-primary feature-clean feature-transition p-4 border-0 shadow rounded overflow-hidden">
                        <div class="icons text-center rounded">
                            <i class="uil uil-presentation-line d-block rounded h4 mb-0"></i>
                        </div>
                        <div class="content mt-4">
                            <a href="page-service-detail.html" class="h5 text-dark title">Real Estate</a>
                            <p class="text-muted mt-3">The phrasal sequence of the Lorem Ipsum text is now so that many
                                DTP programmes can generate</p>

                            <a href="page-service-detail.html" class="link">Read more <i
                                    class="uil uil-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <!--end col-->

                <div class="col-lg-4 col-sm-6 mt-4 pt-2">
                    <div
                        class="card features feature-primary feature-clean feature-transition p-4 border-0 shadow rounded overflow-hidden">
                        <div class="icons text-center rounded">
                            <i class="uil uil-usd-circle d-block rounded h4 mb-0"></i>
                        </div>
                        <div class="content mt-4">
                            <a href="page-service-detail.html" class="h5 text-dark title">Payroll & Accounting</a>
                            <p class="text-muted mt-3">The phrasal sequence of the Lorem Ipsum text is now so that many
                                DTP programmes can generate</p>

                            <a href="page-service-detail.html" class="link">Read more <i
                                    class="uil uil-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <!--end col-->

                <div class="col-lg-4 col-sm-6 mt-4 pt-2">
                    <div
                        class="card features feature-primary feature-clean feature-transition p-4 border-0 shadow rounded overflow-hidden">
                        <div class="icons text-center rounded">
                            <i class="uil uil-invoice d-block rounded h4 mb-0"></i>
                        </div>
                        <div class="content mt-4">
                            <a href="page-service-detail.html" class="h5 text-dark title">Branch Registration</a>
                            <p class="text-muted mt-3">The phrasal sequence of the Lorem Ipsum text is now so that many
                                DTP programmes can generate</p>

                            <a href="page-service-detail.html" class="link">Read more <i
                                    class="uil uil-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->
        </div>
        <!--end container-->
    </section>
    <!--end section-->
    <!-- End -->

    <!-- Start Testimonial -->
    <section class="section" id="testimonial">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col">
                    <div class="section-title text-center mb-4 pb-2">
                        <h6 class="text-primary fw-normal mb-2">Testimonial</h6>
                        <h4 class="title mb-4">What our customer <br> say about us</h4>
                        <p class="text-muted para-desc mb-0 mx-auto">Start working with Fronter that can provide
                            everything you need to generate awareness, drive traffic, connect.</p>
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->

            <div class="row">
                <div class="col-12 mt-4">
                    <div class="tiny-three-item">
                        <div class="tiny-slider client-testi">
                            <div class="card border-0 bg-white">
                                <div class="card-body p-4 rounded shadow m-2">
                                    <i class="mdi mdi-format-quote-open fs-1 text-primary"></i>

                                    <p class="text-muted fst-italic mb-0">" It seems that only fragments of the
                                        original text remain in the Lorem Ipsum texts used today. One may speculate that
                                        over the course of time certain letters were added or deleted at various
                                        positions within the text. "</p>

                                    <div class="d-flex mt-4">
                                        <img src="images/client/01.jpg"
                                            class="avatar avatar-md-sm rounded-circle shadow-md" alt="">
                                        <div class="flex-1 ms-3">
                                            <h6 class="mb-0">Young Bonetti</h6>
                                            <small class="text-muted">Product Manager</small>
                                        </div>
                                    </div>

                                    <ul class="list-unstyled mb-0 mt-3 text-warning h5">
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="tiny-slider client-testi">
                            <div class="card border-0 bg-white">
                                <div class="card-body p-4 rounded shadow m-2">
                                    <i class="mdi mdi-format-quote-open fs-1 text-primary"></i>

                                    <p class="text-muted fst-italic mb-0">" It seems that only fragments of the
                                        original text remain in the Lorem Ipsum texts used today. One may speculate that
                                        over the course of time certain letters were added or deleted at various
                                        positions within the text. "</p>

                                    <div class="d-flex mt-4">
                                        <img src="images/client/02.jpg"
                                            class="avatar avatar-md-sm rounded-circle shadow-md" alt="">
                                        <div class="flex-1 ms-3">
                                            <h6 class="mb-0">Ofelia Bell</h6>
                                            <small class="text-muted">Product Manager</small>
                                        </div>
                                    </div>

                                    <ul class="list-unstyled mb-0 mt-3 text-warning h5">
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="tiny-slider client-testi">
                            <div class="card border-0 bg-white">
                                <div class="card-body p-4 rounded shadow m-2">
                                    <i class="mdi mdi-format-quote-open fs-1 text-primary"></i>

                                    <p class="text-muted fst-italic mb-0">" It seems that only fragments of the
                                        original text remain in the Lorem Ipsum texts used today. One may speculate that
                                        over the course of time certain letters were added or deleted at various
                                        positions within the text. "</p>

                                    <div class="d-flex mt-4">
                                        <img src="images/client/03.jpg"
                                            class="avatar avatar-md-sm rounded-circle shadow-md" alt="">
                                        <div class="flex-1 ms-3">
                                            <h6 class="mb-0">Barbara Carpenter</h6>
                                            <small class="text-muted">Product Manager</small>
                                        </div>
                                    </div>

                                    <ul class="list-unstyled mb-0 mt-3 text-warning h5">
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="tiny-slider client-testi">
                            <div class="card border-0 bg-white">
                                <div class="card-body p-4 rounded shadow m-2">
                                    <i class="mdi mdi-format-quote-open fs-1 text-primary"></i>

                                    <p class="text-muted fst-italic mb-0">" It seems that only fragments of the
                                        original text remain in the Lorem Ipsum texts used today. One may speculate that
                                        over the course of time certain letters were added or deleted at various
                                        positions within the text. "</p>

                                    <div class="d-flex mt-4">
                                        <img src="images/client/04.jpg"
                                            class="avatar avatar-md-sm rounded-circle shadow-md" alt="">
                                        <div class="flex-1 ms-3">
                                            <h6 class="mb-0">Jesus Day</h6>
                                            <small class="text-muted">Product Manager</small>
                                        </div>
                                    </div>

                                    <ul class="list-unstyled mb-0 mt-3 text-warning h5">
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="tiny-slider client-testi">
                            <div class="card border-0 bg-white">
                                <div class="card-body p-4 rounded shadow m-2">
                                    <i class="mdi mdi-format-quote-open fs-1 text-primary"></i>

                                    <p class="text-muted fst-italic mb-0">" It seems that only fragments of the
                                        original text remain in the Lorem Ipsum texts used today. One may speculate that
                                        over the course of time certain letters were added or deleted at various
                                        positions within the text. "</p>

                                    <div class="d-flex mt-4">
                                        <img src="images/client/05.jpg"
                                            class="avatar avatar-md-sm rounded-circle shadow-md" alt="">
                                        <div class="flex-1 ms-3">
                                            <h6 class="mb-0">Jennifer Smith</h6>
                                            <small class="text-muted">Product Manager</small>
                                        </div>
                                    </div>

                                    <ul class="list-unstyled mb-0 mt-3 text-warning h5">
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="tiny-slider client-testi">
                            <div class="card border-0 bg-white">
                                <div class="card-body p-4 rounded shadow m-2">
                                    <i class="mdi mdi-format-quote-open fs-1 text-primary"></i>

                                    <p class="text-muted fst-italic mb-0">" It seems that only fragments of the
                                        original text remain in the Lorem Ipsum texts used today. One may speculate that
                                        over the course of time certain letters were added or deleted at various
                                        positions within the text. "</p>

                                    <div class="d-flex mt-4">
                                        <img src="images/client/06.jpg"
                                            class="avatar avatar-md-sm rounded-circle shadow-md" alt="">
                                        <div class="flex-1 ms-3">
                                            <h6 class="mb-0">Michael Wilkes</h6>
                                            <small class="text-muted">Product Manager</small>
                                        </div>
                                    </div>

                                    <ul class="list-unstyled mb-0 mt-3 text-warning h5">
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                        <li class="list-inline-item"><i class="mdi mdi-star"></i></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->
        </div>
        <!--end container-->

        <!-- Partners start -->
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-lg-2 col-md-2 col-6 py-2">
                    <div class="text-center">
                        <img src="images/brands/3.png" class="avatar avatar-md-md w-auto" alt="">
                    </div>
                </div>
                <!--end col-->

                <div class="col-lg-2 col-md-2 col-6 py-2">
                    <div class="text-center">
                        <img src="images/brands/5.png" class="avatar avatar-md-md w-auto" alt="">
                    </div>
                </div>
                <!--end col-->

                <div class="col-lg-2 col-md-2 col-6 py-2">
                    <div class="text-center">
                        <img src="images/brands/6.png" class="avatar avatar-md-md w-auto" alt="">
                    </div>
                </div>
                <!--end col-->

                <div class="col-lg-2 col-md-2 col-6 py-2">
                    <div class="text-center">
                        <img src="images/brands/8.png" class="avatar avatar-md-md w-auto" alt="">
                    </div>
                </div>
                <!--end col-->

                <div class="col-lg-2 col-md-2 col-6 py-2">
                    <div class="text-center">
                        <img src="images/brands/19.png" class="avatar avatar-md-md w-auto" alt="">
                    </div>
                </div>
                <!--end col-->

                <div class="col-lg-2 col-md-2 col-6 py-2">
                    <div class="text-center">
                        <img src="images/brands/20.png" class="avatar avatar-md-md w-auto" alt="">
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->
        </div>
        <!--end container-->
        <!-- Partners End -->

        <div class="container mt-100 mt-60">
            <div class="row justify-content-center">
                <div class="col">
                    <div class="section-title text-center mb-4 pb-2">
                        <h6 class="text-primary fw-normal mb-2">Our Minds</h6>
                        <h4 class="title mb-4">Management Team</h4>
                        <p class="text-muted para-desc mb-0 mx-auto">Start working with Fronter that can provide
                            everything you need to generate awareness, drive traffic, connect.</p>
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->

            <div class="row">
                <div class="col-lg-3 col-md-4 col-12 mt-4 pt-2">
                    <div class="card team team-primary text-center">
                        <div
                            class="card-img team-image d-inline-block mx-auto rounded-pill avatar avatar-ex-large overflow-hidden">
                            <img src="images/client/04.jpg" class="img-fluid" alt="">
                            <div class="card-overlay avatar avatar-ex-large rounded-pill"></div>

                            <ul class="list-unstyled team-social mb-0">
                                <li class="list-inline-item"><a href="javascript:void(0)"
                                        class="btn btn-sm btn-pills btn-icon"><i data-feather="facebook"
                                            class="fea icon-sm fea-social"></i></a></li>
                                <li class="list-inline-item"><a href="javascript:void(0)"
                                        class="btn btn-sm btn-pills btn-icon"><i data-feather="instagram"
                                            class="fea icon-sm fea-social"></i></a></li>
                                <li class="list-inline-item"><a href="javascript:void(0)"
                                        class="btn btn-sm btn-pills btn-icon"><i data-feather="twitter"
                                            class="fea icon-sm fea-social"></i></a></li>
                            </ul>
                            <!--end icon-->
                        </div>

                        <div class="content mt-3">
                            <a href="page-team-detail.html" class="text-dark h5 mb-0 title">Jack John</a>
                            <h6 class="text-muted mb-0 fw-normal">Designer</h6>
                        </div>
                    </div>
                </div>
                <!--end col-->

                <div class="col-lg-3 col-md-4 col-12 mt-4 pt-2">
                    <div class="card team team-primary text-center">
                        <div
                            class="card-img team-image d-inline-block mx-auto rounded-pill avatar avatar-ex-large overflow-hidden">
                            <img src="images/client/05.jpg" class="img-fluid" alt="">
                            <div class="card-overlay avatar avatar-ex-large rounded-pill"></div>

                            <ul class="list-unstyled team-social mb-0">
                                <li class="list-inline-item"><a href="javascript:void(0)"
                                        class="btn btn-sm btn-pills btn-icon"><i data-feather="facebook"
                                            class="fea icon-sm fea-social"></i></a></li>
                                <li class="list-inline-item"><a href="javascript:void(0)"
                                        class="btn btn-sm btn-pills btn-icon"><i data-feather="instagram"
                                            class="fea icon-sm fea-social"></i></a></li>
                                <li class="list-inline-item"><a href="javascript:void(0)"
                                        class="btn btn-sm btn-pills btn-icon"><i data-feather="twitter"
                                            class="fea icon-sm fea-social"></i></a></li>
                            </ul>
                            <!--end icon-->
                        </div>

                        <div class="content mt-3">
                            <a href="page-team-detail.html" class="text-dark h5 mb-0 title">Krista John</a>
                            <h6 class="text-muted mb-0 fw-normal">Designer</h6>
                        </div>
                    </div>
                </div>
                <!--end col-->

                <div class="col-lg-3 col-md-4 col-12 mt-4 pt-2">
                    <div class="card team team-primary text-center">
                        <div
                            class="card-img team-image d-inline-block mx-auto rounded-pill avatar avatar-ex-large overflow-hidden">
                            <img src="images/client/06.jpg" class="img-fluid" alt="">
                            <div class="card-overlay avatar avatar-ex-large rounded-pill"></div>

                            <ul class="list-unstyled team-social mb-0">
                                <li class="list-inline-item"><a href="javascript:void(0)"
                                        class="btn btn-sm btn-pills btn-icon"><i data-feather="facebook"
                                            class="fea icon-sm fea-social"></i></a></li>
                                <li class="list-inline-item"><a href="javascript:void(0)"
                                        class="btn btn-sm btn-pills btn-icon"><i data-feather="instagram"
                                            class="fea icon-sm fea-social"></i></a></li>
                                <li class="list-inline-item"><a href="javascript:void(0)"
                                        class="btn btn-sm btn-pills btn-icon"><i data-feather="twitter"
                                            class="fea icon-sm fea-social"></i></a></li>
                            </ul>
                            <!--end icon-->
                        </div>

                        <div class="content mt-3">
                            <a href="page-team-detail.html" class="text-dark h5 mb-0 title">Roger Jackson</a>
                            <h6 class="text-muted mb-0 fw-normal">Designer</h6>
                        </div>
                    </div>
                </div>
                <!--end col-->

                <div class="col-lg-3 col-md-4 col-12 mt-4 pt-2">
                    <div class="card team team-primary text-center">
                        <div
                            class="card-img team-image d-inline-block mx-auto rounded-pill avatar avatar-ex-large overflow-hidden">
                            <img src="images/client/07.jpg" class="img-fluid" alt="">
                            <div class="card-overlay avatar avatar-ex-large rounded-pill"></div>

                            <ul class="list-unstyled team-social mb-0">
                                <li class="list-inline-item"><a href="javascript:void(0)"
                                        class="btn btn-sm btn-pills btn-icon"><i data-feather="facebook"
                                            class="fea icon-sm fea-social"></i></a></li>
                                <li class="list-inline-item"><a href="javascript:void(0)"
                                        class="btn btn-sm btn-pills btn-icon"><i data-feather="instagram"
                                            class="fea icon-sm fea-social"></i></a></li>
                                <li class="list-inline-item"><a href="javascript:void(0)"
                                        class="btn btn-sm btn-pills btn-icon"><i data-feather="twitter"
                                            class="fea icon-sm fea-social"></i></a></li>
                            </ul>
                            <!--end icon-->
                        </div>

                        <div class="content mt-3">
                            <a href="page-team-detail.html" class="text-dark h5 mb-0 title">Johnny English</a>
                            <h6 class="text-muted mb-0 fw-normal">Designer</h6>
                        </div>
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->
        </div>
        <!--end container-->
    </section>
    <!--end section-->
    <!-- End -->

    <!-- Start news -->
    <section class="section bg-light" id="news">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col">
                    <div class="section-title text-center mb-4 pb-2">
                        <h4 class="title mb-4">Our Blogs or News</h4>
                        <p class="text-muted para-desc mb-0 mx-auto">Start working with Fronter that can provide
                            everything you need to generate awareness, drive traffic, connect.</p>
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->

            <div class="row">
                <div class="col-lg-4 col-md-6 mt-4 pt-2">
                    <div class="card blog blog-primary shadow rounded overflow-hidden border-0">
                        <div class="card-img image position-relative overflow-hidden">
                            <div class="position-relative overflow-hidden">
                                <img src="images/blog/1.jpg" class="img-fluid" alt="">
                                <div class="card-overlay"></div>
                            </div>

                            <div class="blog-tag">
                                <a href="blog-detail.html" class="badge bg-light text-dark">Corporate</a>
                            </div>

                            <div class="read-more">
                                <a href="blog-detail.html" class="text-white title-dark-50">Read More <i
                                        class="uil uil-arrow-right align-middle"></i></a>
                            </div>
                        </div>

                        <div class="card-body content p-0">
                            <div class="p-4">
                                <a href="blog-detail.html" class="h5 title text-dark d-block mb-0">Building Your
                                    Corporate Identity from Fronter</a>
                                <p class="text-muted mt-2 mb-0">The most well-known dummy text is the 'Lorem Ipsum', in
                                    the 16th century.</p>
                            </div>

                            <div class="post-meta d-flex justify-content-between p-4 border-top">
                                <ul class="list-unstyled mb-0">
                                    <li class="list-inline-item me-2 mb-0"><a href="javascript:void(0)"
                                            class="text-muted like"><i class="uil uil-heart me-1"></i>33</a></li>
                                    <li class="list-inline-item"><a href="javascript:void(0)"
                                            class="text-muted comments"><i class="uil uil-comment me-1"></i>08</a>
                                    </li>
                                </ul>
                                <a href="javascript:void(0)" class="text-muted readmore"><i
                                        class="uil uil-calender"></i> 20th Dec 2021</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end col-->

                <div class="col-lg-4 col-md-6 mt-4 pt-2">
                    <div class="card blog blog-primary shadow rounded overflow-hidden border-0">
                        <div class="card-img image position-relative overflow-hidden">
                            <div class="position-relative overflow-hidden">
                                <img src="images/blog/2.jpg" class="img-fluid" alt="">
                                <div class="card-overlay"></div>
                            </div>

                            <div class="blog-tag">
                                <a href="blog-detail.html" class="badge bg-light text-dark">Branding</a>
                            </div>

                            <div class="read-more">
                                <a href="blog-detail.html" class="text-white title-dark-50">Read More <i
                                        class="uil uil-arrow-right align-middle"></i></a>
                            </div>
                        </div>

                        <div class="card-body content p-0">
                            <div class="p-4">
                                <a href="blog-detail.html" class="h5 title text-dark d-block mb-0">The Dark Side of
                                    Overnight Success</a>
                                <p class="text-muted mt-2 mb-0">The most well-known dummy text is the 'Lorem Ipsum', in
                                    the 16th century.</p>
                            </div>

                            <div class="post-meta d-flex justify-content-between p-4 border-top">
                                <ul class="list-unstyled mb-0">
                                    <li class="list-inline-item me-2 mb-0"><a href="javascript:void(0)"
                                            class="text-muted like"><i class="uil uil-heart me-1"></i>33</a></li>
                                    <li class="list-inline-item"><a href="javascript:void(0)"
                                            class="text-muted comments"><i class="uil uil-comment me-1"></i>08</a>
                                    </li>
                                </ul>
                                <a href="javascript:void(0)" class="text-muted readmore"><i
                                        class="uil uil-calender"></i> 20th Dec 2021</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end col-->

                <div class="col-lg-4 col-md-6 mt-4 pt-2">
                    <div class="card blog blog-primary shadow rounded overflow-hidden border-0">
                        <div class="card-img image position-relative overflow-hidden">
                            <div class="position-relative overflow-hidden">
                                <img src="images/blog/3.jpg" class="img-fluid" alt="">
                                <div class="card-overlay"></div>
                            </div>

                            <div class="blog-tag">
                                <a href="blog-detail.html" class="badge bg-light text-dark">Technology</a>
                            </div>

                            <div class="read-more">
                                <a href="blog-detail.html" class="text-white title-dark-50">Read More <i
                                        class="uil uil-arrow-right align-middle"></i></a>
                            </div>
                        </div>

                        <div class="card-body content p-0">
                            <div class="p-4">
                                <a href="blog-detail.html" class="h5 title text-dark d-block mb-0">The Right Hand of
                                    Business IT World</a>
                                <p class="text-muted mt-2 mb-0">The most well-known dummy text is the 'Lorem Ipsum', in
                                    the 16th century.</p>
                            </div>

                            <div class="post-meta d-flex justify-content-between p-4 border-top">
                                <ul class="list-unstyled mb-0">
                                    <li class="list-inline-item me-2 mb-0"><a href="javascript:void(0)"
                                            class="text-muted like"><i class="uil uil-heart me-1"></i>33</a></li>
                                    <li class="list-inline-item"><a href="javascript:void(0)"
                                            class="text-muted comments"><i class="uil uil-comment me-1"></i>08</a>
                                    </li>
                                </ul>
                                <a href="javascript:void(0)" class="text-muted readmore"><i
                                        class="uil uil-calender"></i> 20th Dec 2021</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->
        </div>
        <!--end container-->
    </section>
    <!--end section-->
    <!-- End news -->

    <!-- Start -->
    <section class="section" id="contact">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="section-title mb-4">
                        <h4 class="title mb-4">Contact Us</h4>
                        <p class="text-muted para-desc mb-0">Start working with Fronter that can provide everything you
                            need to generate awareness, drive traffic, connect.</p>
                    </div>

                    <div class="row">
                        <div class="col-12 mt-4">
                            <div class="d-flex features feature-primary">
                                <div class="feature-icon text-center">
                                    <i class="uil uil-phone rounded h4"></i>
                                </div>
                                <div class="flex-1 ms-4">
                                    <h6 class="mb-1">Phone</h6>
                                    <a href="tel:+152534-468-854" class="link">+152 534-468-854</a>
                                </div>
                            </div>
                        </div><!-- End Col -->

                        <div class="col-12 mt-4">
                            <div class="d-flex features feature-primary">
                                <div class="feature-icon text-center">
                                    <i class="uil uil-envelope rounded h4"></i>
                                </div>
                                <div class="flex-1 ms-4">
                                    <h6 class="mb-1">Email</h6>
                                    <a href="mailto:contact@example.com" class="link">contact@example.com</a>
                                </div>
                            </div>
                        </div><!-- End Col -->

                        <div class="col-12 mt-4">
                            <div class="d-flex features feature-primary">
                                <div class="feature-icon text-center">
                                    <i class="uil uil-map-marker rounded h4"></i>
                                </div>
                                <div class="flex-1 ms-4">
                                    <h6 class="mb-1">Location</h6>
                                    <a href="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d39206.002432144705!2d-95.4973981212445!3d29.709510002925988!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8640c16de81f3ca5%3A0xf43e0b60ae539ac9!2sGerald+D.+Hines+Waterwall+Park!5e0!3m2!1sen!2sin!4v1566305861440!5m2!1sen!2sin"
                                        class="lightbox text-primary" data-type="iframe" data-group="iframe"
                                        data-width="1024px" data-height="576px">View on Google map</a>
                                </div>
                            </div>
                        </div><!-- End Col -->
                    </div>
                    <!--end row-->
                </div>
                <!--end col-->

                <div class="col-lg-8 col-md-6 col-12 mt-4 pt-2 mt-sm-0 pt-sm-0">
                    <div class="p-4 rounded shadow">
                        <form method="post" name="myForm" onsubmit="return validateForm()">
                            <p class="mb-0" id="error-msg"></p>
                            <div id="simple-msg"></div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Your Name <span
                                                class="text-danger">*</span></label>
                                        <input name="name" id="name" type="text" class="form-control"
                                            placeholder="Name :">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Your Email <span
                                                class="text-danger">*</span></label>
                                        <input name="email" id="email" type="email" class="form-control"
                                            placeholder="Email :">
                                    </div>
                                </div>
                                <!--end col-->

                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Subject</label>
                                        <input name="subject" id="subject" class="form-control"
                                            placeholder="Subject :">
                                    </div>
                                </div>
                                <!--end col-->

                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Comments <span
                                                class="text-danger">*</span></label>
                                        <textarea name="comments" id="comments" rows="4" class="form-control"
                                            placeholder="Message :"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-grid">
                                        <button type="submit" id="submit" name="send" class="btn btn-primary">Send
                                            Message</button>
                                    </div>
                                </div>
                                <!--end col-->
                            </div>
                            <!--end row-->
                        </form>
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->
        </div>
        <!--end container-->
    </section>
    <!--end section-->
    <!-- End -->

    <!-- Start Footer -->
    <footer class="bg-footer footer-bar">
        <div class="footer-py-30">
            <div class="container text-center">
                <div class="row align-items-center">
                    <div class="col-sm-3">
                        <div class="text-sm-start">
                            <a href="#" class="logo-footer">
                                <img src="images/logo-light.png" alt="">
                            </a>
                        </div>
                    </div>

                    <div class="col-sm-6 mt-4 mt-sm-0 pt-2 pt-sm-0">
                        <div class="text-center">
                            <script>
                                document.write(new Date().getFullYear())
                            </script> Fronter. Design with <i
                                    class="mdi mdi-heart text-danger"></i> by <a href="https://shreethemes.in/"
                                    target="_blank" class="text-reset">Shreethemes</a>.</p>
                        </div>
                    </div>

                    <div class="col-sm-3 mt-4 mt-sm-0 pt-2 pt-sm-0">
                        <ul class="list-unstyled social-icon foot-social-icon text-sm-end mb-0">
                            <li class="list-inline-item mb-0"><a href="javascript:void(0)" class="rounded"><svg
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="feather feather-facebook fea icon-sm fea-social">
                                        <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z">
                                        </path>
                                    </svg></a></li>
                            <li class="list-inline-item mb-0"><a href="javascript:void(0)" class="rounded"><svg
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="feather feather-instagram fea icon-sm fea-social">
                                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                                    </svg></a></li>
                            <li class="list-inline-item mb-0"><a href="javascript:void(0)" class="rounded"><svg
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="feather feather-twitter fea icon-sm fea-social">
                                        <path
                                            d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z">
                                        </path>
                                    </svg></a></li>
                            <li class="list-inline-item mb-0"><a href="javascript:void(0)" class="rounded"><svg
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="feather feather-linkedin fea icon-sm fea-social">
                                        <path
                                            d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z">
                                        </path>
                                        <rect x="2" y="9" width="4" height="12"></rect>
                                        <circle cx="4" cy="4" r="2"></circle>
                                    </svg></a></li>
                        </ul>
                        <!--end icon-->
                    </div>
                    <!--end col-->
                </div>
                <!--end row-->
            </div>
            <!--end container-->
        </div>
    </footer>
    <!--end footer-->
    <!-- End Footer -->

    <!-- Back to top -->
    <a href="#" onclick="topFunction()" id="back-to-top" class="back-to-top rounded-pill fs-5"><i
            data-feather="arrow-up" class="fea icon-sm icons align-middle"></i></a>
    <!-- Back to top -->

    <!-- Style switcher -->
    <div id="style-switcher" class="bg-light border p-3 pt-2 pb-2" onclick="toggleSwitcher()">
        <div class="content">
            <h6 class="title text-center">Select Your Color</h6>
            <ul class="pattern">
                <li class="list-inline-item">
                    <a class="color1" href="javascript: void(0);" onclick="setColor('default')"></a>
                </li>
                <li class="list-inline-item">
                    <a class="color2" href="javascript: void(0);" onclick="setColor('yellow')"></a>
                </li>
                <li class="list-inline-item">
                    <a class="color3" href="javascript: void(0);" onclick="setColor('purple')"></a>
                </li>
                <li class="list-inline-item">
                    <a class="color4" href="javascript: void(0);" onclick="setColor('red')"></a>
                </li>
                <li class="list-inline-item">
                    <a class="color5" href="javascript: void(0);" onclick="setColor('green')"></a>
                </li>
                <li class="list-inline-item">
                    <a class="color6" href="javascript: void(0);" onclick="setColor('orange')"></a>
                </li>
                <li class="list-inline-item">
                    <a class="color7" href="javascript: void(0);" onclick="setColor('rose')"></a>
                </li>
            </ul>

            <h6 class="title text-center pt-3 mb-0 border-top">Theme Option</h6>
            <ul class="text-center list-unstyled mb-0">
                <li class="d-grid"><a href="javascript:void(0)"
                        class="btn btn-sm btn-block btn-dark dark-version t-dark mt-2"
                        onclick="setTheme('style-dark')">Dark</a></li>
                <li class="d-grid"><a href="javascript:void(0)"
                        class="btn btn-sm btn-block btn-dark light-version t-light mt-2"
                        onclick="setTheme('style')">Light</a></li>
            </ul>
        </div>
        <div class="bottom p-0">
            <a href="javascript: void(0);" class="settings bg-white title-bg-dark shadow d-block"><i
                    class="mdi mdi-cog ms-1 mdi-24px position-absolute mdi-spin text-primary"></i></a>
        </div>
    </div>
    <!-- end Style switcher -->

    <!-- JAVASCRIPTS -->
    <script src="{{ asset('assets/fronter/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/fronter/js/gumshoe.polyfills.min.js') }}"></script>
    <!-- Tiny slider -->
    <script src="{{ asset('assets/fronter/js/tiny-slider.js') }}"></script>
    <script src="{{ asset('assets/fronter/js/easy_background.js') }} "></script>
    <!-- parallax -->
    <script src="{{ asset('assets/fronter/js/parallax.js') }}"></script>
    <!-- contact -->
    <script src="{{ asset('assets/fronter/js/contact.js') }}"></script>
    <!-- Tobii -->
    <script src="{{ asset('assets/fronter/js/tobii.min.js') }}"></script>
    <!-- Icons -->
    <script src="{{ asset('assets/fronter/js/feather.min.js') }}"></script>
    <!-- Custom -->
    <script src="{{ asset('assets/fronter/js/plugins.init.js') }}"></script>
    <script src="{{ asset('assets/fronter/js/app.js') }}"></script>

    <script>
        easy_background("#overlay", {
            slide: ["images/startup/01.png", "images/startup/02.png", "images/startup/03.png"],
            delay: [4000, 4000, 4000]
        });
    </script>
</body>

</html>
