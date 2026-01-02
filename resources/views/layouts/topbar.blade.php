<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <!-- LOGO -->
            <div class="navbar-brand-box">
                <a href="#" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ URL::asset('assets/images/emakato_logo_white.png') }}" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ URL::asset('assets/images/emakato_logo_white.png') }}" alt=""
                            height="17">
                    </span>
                </a>

                <a href="#" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="{{ URL::asset('assets/images/emakato_logo_colored.png') }}" alt=""
                            height="12">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ URL::asset('assets/images/emakato_logo_colored.png') }}" alt=""
                            height="32">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>

            <!-- App Search-->
            @if (Route::is('dashboard'))
                <form action="{{ $form_action }}" method="POST" class="app-search d-none d-lg-block" id="channel-form">
                    @csrf
                    <div class="position-relative">
                        <div class="form-group mb-0">
                            <select class="form-select" id="channel-select" name="channel">
                                <option value="">-- All Channels --</option>
                                <option value="NMB" {{ request('channel') === 'NMB' ? 'selected' : '' }}>NMB</option>
                                <option value="CRDB" {{ request('channel') === 'CRDB' ? 'selected' : '' }}>CRDB</option>
                            </select>
                        </div>
                    </div>
                </form>

                <script>
                    document.getElementById('channel-select').addEventListener('change', function() {
                        this.form.submit();
                    });
                </script>
            @endif
        </div>

        <div class="d-flex">
            <div class="dropdown d-inline-block d-lg-none ms-2">
                <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-search-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="mdi mdi-magnify"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                    aria-labelledby="page-header-search-dropdown">

                    <form class="p-3">
                        <div class="form-group m-0">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="@lang('translation.Search')"
                                    aria-label="Search input">

                                <button class="btn btn-primary" type="submit"><i class="mdi mdi-magnify"></i></button>s
                            </div>
                        </div>
                    </form>
                </div>
            </div>


            <div class="dropdown d-none d-lg-inline-block ms-1">
                <button type="button" class="btn header-item noti-icon waves-effect" data-toggle="fullscreen">
                    <i class="bx bx-fullscreen"></i>
                </button>
            </div>

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item noti-icon waves-effect"
                    id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false">
                    <i class="bx bx-bell bx-tada"></i>
                    <span class="badge bg-danger rounded-pill"></span>
                </button>
            </div>

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    {{-- <img class="rounded-circle header-profile-user" src="{{ isset(Auth::user()->avatar) ? asset(Auth::user()->avatar) : asset('/assets/images/users/avatar99.png') }}"
                    alt="Header Avatar"> --}}
                    <span class="d-none d-xl-inline-block ms-1" key="t-henry">
                        {{ ucfirst(Auth::user()->name) }}
                    </span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <span class="dropdown-item font-weight-600">Personal Information</span>
                    <!-- item-->
                    <span class="dropdown-item">
                        {{ ucfirst(Auth::user()->name) }}
                    </span>

                    <a class="dropdown-item text-primary" href="#">My Profile</a>

                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="{{ route('auth.logout') }}">Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>
