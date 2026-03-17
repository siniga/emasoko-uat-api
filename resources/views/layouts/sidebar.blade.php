<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">

    <div data-simplebar class="h-100">
        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                {{-- <li class="menu-title" key="t-menu">@lang('translation.Menu')</li> --}}

                <li class="{{ Route::is('dashboard') ? 'mm-active' : '' }}">
                    <a href="{{ route('dashboard') }}" class="waves-effect">
                        <i class="bx bx-home"></i>
                        <span key="t-dashboards">Dashboard</span>
                    </a>
                </li>

                {{-- <li class="{{ Route::is('bi.dashboard') ? 'mm-active' : '' }}">
                    <a href="{{ route('bi.dashboard') }}" class="waves-effect">
                        <i class="bx bx-line-chart"></i>
                        <span>BI Dashboard</span>
                    </a>
                </li> --}}

                @if (Auth::user()->hasRole(['admin']) || Auth::user()->hasRole(['Partner']))
                    <li
                        class="{{ Route::is('companies.lists', 'companies.registration', 'companies.register-documents', 'companies.register-accounts', 'companies.register-staff', 'companies.register-authorized-person', 'companies.preview', 'companies.details', 'companies.edit', 'companies.collection-account') ? 'mm-active' : '' }}">
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

                    @if (Auth::user()->hasRole(['admin']))
                        <li
                            class="{{ Route::is('customer-accounts.lists', 'customer-accounts.index', 'customer-accounts.delete', 'customer-accounts.create', 'customer-accounts.edit', 'customer-accounts.show') ? 'mm-active' : '' }}">
                            <a href="{{ route('customer-accounts.lists') }}" class="waves-effect">
                                <i class='bx bxs-user-badge'></i>
                                <span key="t-layouts">Customer Accounts</span>
                            </a>
                        </li>
                    @endif
                @endif

                <li class="{{ Route::is('payment-mandate.create-account') ? 'mm-active' : '' }}">
                    <a href="{{ route('payment-mandate.create-account') }}" class="waves-effect">
                        <i class="bx bx-user-plus"></i>
                        <span key="t-layouts">Create Mandate</span>
                    </a>
                </li>

                <li class="{{ Route::is('payment-mandate.lists', 'payment-mandate.create-deduction', 'payment-mandate.preview', 'payment-mandate.show', 'payment-mandate.edit', 'payment-mandate.delete') ? "mm-active" : ""}}">
                    <a href="{{ route('payment-mandate.lists') }}" class="waves-effect">
                        <i class="bx bxs-wallet-alt"></i>
                        <span key="t-layouts">Payment Mandate</span>
                    </a>
                </li>

                @if (Auth::user()->hasRole(['admin']))
                    <li class="{{ Route::is('payment-mandate.request-cancellation', 'payment-mandate.request-closing') ? 'mm-active' : '' }}">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class='bx bx-list-check'></i>
                            <span>Mandates Approvals</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <li><a href="{{ route('payment-mandate.approve-cancellation') }}">Cancellation</a></li>
                            <li><a href="{{ route('payment-mandate.approve-deletion') }}">Deletion</a></li>
                            <li><a href="{{ route('payment-mandate.approve-archive') }}">Archive</a></li>
                            <li><a href="{{ route('payment-mandate.approve-closing') }}">Closing</a></li>
                        </ul>
                    </li>
                @endif

                <li class="{{ Route::is('collections.batches', 'collections.show') ? 'mm-active' : '' }}">
                    <a href="{{ route('collections.batches') }}" class="waves-effect">
                        <i class="bx bx-wallet"></i>
                        <span key="t-layouts">Collection Batches</span>
                    </a>
                </li>

                <li class="{{ Route::is('reports.lists') ? 'mm-active' : '' }}">
                    <a href="{{ route('reports.lists') }}" class="waves-effect">
                        <i class='bx bxs-report'></i>
                        <span key="t-layouts">Reports</span>
                    </a>
                </li>

                @if (Auth::user()->hasRole(['admin']) || Auth::user()->hasRole(['Partner']))
                    <li class="menu-title" key="t-apps">Authentication</li>

                    <li
                        class="{{ Route::is('users.lists', 'users.create', 'users.edit', 'users.show', 'users.change-status') ? 'mm-active' : '' }}">
                        <a href="{{ route('users.lists') }}" class="waves-effect">
                            <i class="bx bx-user-plus"></i>
                            <span key="t-chat">Users</span>
                        </a>
                    </li>

                    <li class="{{ Route::is('users.view-otp') ? 'mm-active' : '' }}">
                        <a href="{{ route('users.view-otp') }}" class="waves-effect">
                            <i class='bx bx-info-circle'></i>
                            <span key="t-chat">Users OTP</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
