<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <!-- LOGO -->
            <div class="navbar-brand-box bg-primary">
                <a href="index" class="logo logo-dark">
                    <span class="logo-sm text-large">
                        e-Makato
                        {{-- <img src="{{ URL::asset('/assets/images/logo.svg') }}" alt="" height="22"> --}}
                    </span>
                    <span class="logo-lg text-large">
                        e-Makato
                        {{-- <img src="{{ URL::asset('/assets/images/logo-dark.png') }}" alt="" height="17"> --}}
                    </span>
                </a>

                <a href="index" class="logo logo-light">
                    <span class="logo-sm text-large">
                        e-Makato
                        {{-- <img src="{{ URL::asset('/assets/images/logo-light.svg') }}" alt="" height="22"> --}}
                    </span>
                    <span class="logo-lg text-large">
                        e-Makato
                        {{-- <img src="{{ URL::asset('/assets/images/logo-light.png') }}" alt="" height="19"> --}}
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>

           <!-- App Search-->
           <form class="app-search d-none d-lg-block">
            <div class="position-relative">
                <input type="text" class="form-control" placeholder="@lang('translation.Search')">
                <span class="bx bx-search-alt"></span>
            </div>
        </form>
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
                            <input type="text" class="form-control" placeholder="@lang('translation.Search')" aria-label="Search input">
                            
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

        {{-- <div class="dropdown d-inline-block">
            <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-notifications-dropdown"
                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="bx bx-bell bx-tada"></i>
                <span class="badge bg-danger rounded-pill"></span>
            </button>  
        </div> --}}

        <div class="dropdown d-inline-block">
            <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                {{-- <img class="rounded-circle header-profile-user" src="{{ isset(Auth::user()->avatar) ? asset(Auth::user()->avatar) : asset('/assets/images/users/avatar99.png') }}"
                    alt="Header Avatar"> --}}
                <span class="d-none d-xl-inline-block ms-1" key="t-henry">
                    {{ucfirst(Auth::user()->name)}}<br/>
                    {{Auth::user()->email }}<br/>
                </span>
                <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                <!-- item-->
                <a class="dropdown-item" href="contacts-profile"><i class="bx bx-user font-size-16 align-middle me-1"></i> <span key="t-profile">@lang('translation.Profile')</span></a>
                {{-- <a class="dropdown-item" href="#"><i class="bx bx-wallet font-size-16 align-middle me-1"></i> <span key="t-my-wallet">@lang('translation.My_Wallet')</span></a> --}}
                {{-- <a class="dropdown-item d-block" href="#" data-bs-toggle="modal" data-bs-target=".change-password"><span class="badge bg-success float-end">11</span><i class="bx bx-wrench font-size-16 align-middle me-1"></i> <span key="t-settings">@lang('translation.Settings')</span></a> --}}
                {{-- <a class="dropdown-item" href="#"><i class="bx bx-lock-open font-size-16 align-middle me-1"></i> <span key="t-lock-screen">@lang('translation.Lock_screen')</span></a> --}}
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="{{ route('logout') }}">Logout</a>
            </div>
        </div>        
    </div>
</div>
</header>
