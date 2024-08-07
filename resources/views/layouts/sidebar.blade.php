<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">

    <div data-simplebar class="h-100">
        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title" key="t-menu">@lang('translation.Menu')</li>

                <li>
                    <a href="{{ route('dashboard') }}" class="waves-effect">
                        <i class="bx bx-home"></i>
                        <span key="t-dashboards">Dashboard</span>
                    </a>
                </li>

                @if (Auth::user()->hasRole(['admin']))
                    <li class="{{ Route::has('companies') ? "mm-active":""}}">
                        <a href="{{ route('companies.lists') }}" class="waves-effect ">
                            <i class="bx bx-building"></i>
                            <span key="t-layouts">Service Providers</span>

                            @php
                                $no_of_notification = get_company_notification();
                            @endphp

                            @if ($no_of_notification != null)
                                <span class="badge bg-danger">{{ $no_of_notification }}</span>
                            @endif
                        </a>
                    </li>
           

                    <li class="{{ Route::has('customer-accounts') ? "mm-active":""}}">
                        <a href="{{ route('customer-accounts.lists') }}" class="waves-effect">
                            <i class='bx bxs-user-badge'></i>
                            <span key="t-layouts">Customer Accounts</span>
                        </a>
                    </li>
                @endif

                <li class="{{ Route::is('payment-mandate.create-account') ? "mm-active":""}}">
                    <a href="{{ route('payment-mandate.create-account') }}" class="waves-effect">
                        <i class="bx bx-user-plus"></i>
                        <span key="t-layouts">Create Mandate</span>
                    </a>
                </li>

                <li class="{{ Route::is('payment-mandate.lists') ? "mm-active":""}}">
                    <a href="{{ route('payment-mandate.lists') }}" class="waves-effect">
                        <i class="bx bxs-wallet-alt"></i>
                        <span key="t-layouts">Payment Mandate</span>
                    </a>
                </li>

                @if (Auth::user()->hasRole(['admin']))
                    <li class="menu-title" key="t-apps">Setup</li>

                    <li class="{{ Route::is('payment-channels.index') ? "mm-active":""}}">
                        <a href="{{ route('payment-channels.index') }}" class="waves-effect">
                            <i class="bx bx-copy"></i>
                            <span key="t-chat">Payment Channel</span>
                        </a>
                    </li>
                @endif


                @if (Auth::user()->hasRole(['admin']))
                    <li class="menu-title" key="t-apps">Authentication</li>

                    <li class="{{ Route::is('users.index') ? "mm-active":""}}">
                        <a href="{{ route('users.index') }}" class="waves-effect">
                            <i class="bx bx-user-plus"></i>
                            <span key="t-chat">Users</span>
                        </a>
                    </li>

                    <li class="{{ Route::is('roles.index') ? "mm-active":""}}">
                        <a href="{{ route('roles.index') }}" class="waves-effect">
                            <i class="bx bx-user-pin"></i>
                            <span key="t-chat">Roles</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
