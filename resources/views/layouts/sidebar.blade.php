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
                        <span key="t-dashboards">@lang('translation.Dashboards')</span>
                    </a>
                </li>

                @if (Auth::user()->hasRole('admin'))
                    <li>
                        <a href="{{ route('companies.index') }}" class="waves-effect">
                            <i class="bx bx-building"></i>
                            <span key="t-layouts">Companies</span>
                        </a>
                    </li>
                @endif

                <li>
                    <a href="{{ route('customers.lists') }}" class="waves-effect">
                        <i class="bx bx-user"></i>
                        <span key="t-layouts">Payment Mandate</span>
                    </a>
                </li>

                @if (Auth::user()->hasRole('admin'))
                    <li class="menu-title" key="t-apps">Authentication</li>

                    <li>
                        <a href="{{ route('users.index') }}" class="waves-effect">
                            <i class="bx bx-user-plus"></i>
                            <span key="t-chat">Users</span>
                        </a>
                    </li>

                    <li>
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
