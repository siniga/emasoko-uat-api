<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu bg-primary">

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

                @if (Auth::user()->hasRole('admin'))
                    <li>
                        <a href="{{ route('companies.index') }}" class="waves-effect">
                            <i class="bx bx-building"></i>
                            <span key="t-layouts">Companies</span>

                            @php
                                $notify = get_company_notification();
                            @endphp

                            @if ($notify != null)
                                <span class="badge bg-danger">{{ $notify }}</span>
                            @endif
                        </a>
                    </li>
                @endif

                @if (!Auth::user()->hasRole('admin'))
                    <li>
                        <a href="{{ route('payment-mandate.create-account') }}" class="waves-effect">
                            <i class="bx bx-user-plus"></i>
                            <span key="t-layouts">Create Payment Mandate</span>
                        </a>
                    </li>
                @endif

                <li>
                    <a href="{{ route('payment-mandate.lists') }}" class="waves-effect">
                        <i class="bx bx-user"></i>
                        <span key="t-layouts">Payment Mandate</span>
                    </a>
                </li>

                {{-- @if (!Auth::user()->hasRole('admin'))
                    <li>
                        <a href="{{ route('tasks.lists') }}" class="waves-effect">
                            <i class="bx bx-collection"></i>
                            <span key="t-layouts">My Usage</span>
                            @php
                                $notify = get_pending_debit_orders();
                            @endphp

                            @if ($notify != null)
                                <span class="badge bg-danger">{{ $notify }}</span>
                            @endif
                        </a>
                    </li>
                @endif --}}

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
