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

                @php
                    if (Request::segment(1) == 'companies') {
                        $sp_class = 'mm-active';
                    } else {
                        $sp_class = '';
                    }
                @endphp

                <li class="{{ $sp_class }}">
                    <a href="{{ route('companies.lists') }}" class="waves-effect">
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

                <li>
                    <a href="{{ route('customer-accounts.lists') }}" class="waves-effect">
                        <i class='bx bxs-user-badge'></i>
                        <span key="t-layouts">Customer Accounts</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('payment-mandate.create-account') }}" class="waves-effect">
                        <i class="bx bx-user-plus"></i>
                        <span key="t-layouts">Create Payment Mandate</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('payment-mandate.lists') }}" class="waves-effect">
                        <i class="bx bxs-wallet-alt"></i>
                        <span key="t-layouts">Payment Mandate</span>
                    </a>
                </li>

                <li class="menu-title" key="t-apps">Setup</li>

                <li>
                    <a href="{{ route('payment-channels.index') }}" class="waves-effect">
                        <i class="bx bx-copy"></i>
                        <span key="t-chat">Payment Channel</span>
                    </a>
                </li>

                {{-- <li>
                        <a href="#" class="waves-effect">
                            <i class="bx bx-user-plus text-white"></i>
                            <span key="t-chat">Debit</span>
                        </a>
                    </li> --}}


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
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
