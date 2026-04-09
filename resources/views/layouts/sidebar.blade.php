<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">

    <div data-simplebar class="h-100">
        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                {{-- <li class="menu-title" key="t-menu">@lang('translation.Menu')</li> --}}
                @php
                    $mandateApprovalRoutes = [
                        'payment-mandate.approvals',
                        'payment-mandate.request-cancellation',
                        'payment-mandate.approve-cancellation',
                        'payment-mandate.request-deletion',
                        'payment-mandate.approve-deletion',
                        'payment-mandate.request-archive',
                        'payment-mandate.approve-archive',
                        'payment-mandate.request-closing',
                        'payment-mandate.approve-closing',
                    ];
                    $dashboardOpen = Route::is('dashboard') || request()->is('dashboard');
                    $providersOpen =
                        Route::is(
                            'companies.lists',
                            'companies.registration',
                            'companies.register-documents',
                            'companies.register-accounts',
                            'companies.register-staff',
                            'companies.register-authorized-person',
                            'companies.preview',
                            'companies.details',
                            'companies.edit',
                            'companies.collection-account',
                        ) ||
                        request()->is('companies') ||
                        request()->is('companies/*');
                    $customerAccountsOpen =
                        Route::is(
                            'customer-accounts.lists',
                            'customer-accounts.index',
                            'customer-accounts.delete',
                            'customer-accounts.create',
                            'customer-accounts.edit',
                            'customer-accounts.show',
                        ) ||
                        request()->is('customer-accounts') ||
                        request()->is('customer-accounts/*');
                    $createMandateOpen = Route::is(
                        'payment-mandate.create-account',
                        'payment-mandate.create-deduction',
                        'payment-mandate.preview',
                    );
                    $paymentMandateOpen =
                        Route::is(
                            'payment-mandate.lists',
                            'payment-mandate.show',
                            'payment-mandate.edit',
                            'payment-mandate.delete',
                        );
                    $mandateApprovalsOpen = Route::is(...$mandateApprovalRoutes);
                    $mandateApprovalCount = \App\Models\DebitOrderRequest::where('status', 'PENDING')
                        ->whereIn('request_type', ['CANCELLATION', 'DELETION', 'ARCHIVE', 'CLOSING'])
                        ->count();
                    $collectionRoutesOpen =
                        Route::is('collections.*') || request()->is('collections') || request()->is('collections/*');
                    $reportsOpen = Route::is('reports.lists') || request()->is('reports') || request()->is('reports/*');
                    $usersOpen =
                        Route::is('users.lists', 'users.create', 'users.edit', 'users.show', 'users.change-status') ||
                        request()->is('users') ||
                        request()->is('users/*');
                    $usersOtpOpen =
                        Route::is('users.view-otp') || request()->is('users/view-otp') || request()->is('users/otp');
                    $paymentChannelsOpen =
                        Route::is('payment-channels.index', 'payment-channels.create', 'payment-channels.edit') ||
                        request()->is('payment-channels') ||
                        request()->is('payment-channels/*');
                @endphp

                <li class="{{ $dashboardOpen ? 'mm-active' : '' }}">
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
                    <li class="{{ $providersOpen ? 'mm-active' : '' }}">
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
                        <li class="{{ $customerAccountsOpen ? 'mm-active' : '' }}">
                            <a href="{{ route('customer-accounts.lists') }}" class="waves-effect">
                                <i class='bx bxs-user-badge'></i>
                                <span key="t-layouts">Customer Accounts</span>
                            </a>
                        </li>
                    @endif
                @endif

                <li class="{{ $createMandateOpen ? 'mm-active' : '' }}">
                    <a href="{{ route('payment-mandate.create-account') }}" class="waves-effect">
                        <i class="bx bx-user-plus"></i>
                        <span key="t-layouts">Create Mandate</span>
                    </a>
                </li>

                <li class="{{ $paymentMandateOpen ? 'mm-active' : '' }}">
                    <a href="{{ route('payment-mandate.lists') }}" class="waves-effect">
                        <i class="bx bxs-wallet-alt"></i>
                        <span key="t-layouts">Payment Mandate</span>
                    </a>
                </li>

                @if (Auth::user()->hasRole(['admin']))
                    <li class="{{ $mandateApprovalsOpen ? 'mm-active' : '' }}">
                        <a href="{{ route('payment-mandate.approvals') }}" class="waves-effect">
                            <i class='bx bx-list-check'></i>
                            <span>Approvals</span>
                            @if ($mandateApprovalCount > 0)
                                <span class="badge bg-danger">{{ number_format($mandateApprovalCount) }}</span>
                            @endif
                        </a>
                    </li>
                @endif

                <li class="{{ $collectionRoutesOpen ? 'mm-active' : '' }}">
                    <a href="{{ route('collections.batches') }}" class="waves-effect">
                        <i class="bx bx-wallet"></i>
                        <span key="t-layouts">Collection Batches</span>
                    </a>
                </li>

                <li class="{{ $reportsOpen ? 'mm-active' : '' }}">
                    <a href="{{ route('reports.lists') }}" class="waves-effect">
                        <i class='bx bxs-report'></i>
                        <span key="t-layouts">Reports</span>
                    </a>
                </li>

                @if (Auth::user()->hasRole(['admin']))
                    <li class="{{ $paymentChannelsOpen ? 'mm-active' : '' }}">
                        <a href="{{ route('payment-channels.index') }}" class="waves-effect">
                            <i class='bx bx-transfer-alt'></i>
                            <span>Payment Channels</span>
                        </a>
                    </li>
                @endif

                @if (Auth::user()->hasRole(['admin']) || Auth::user()->hasRole(['Partner']))
                    <li class="menu-title" key="t-apps">Authentication</li>

                    <li class="{{ $usersOpen ? 'mm-active' : '' }}">
                        <a href="{{ route('users.lists') }}" class="waves-effect">
                            <i class="bx bx-user-plus"></i>
                            <span key="t-chat">Users</span>
                        </a>
                    </li>

                    <li class="{{ $usersOtpOpen ? 'mm-active' : '' }}">
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
