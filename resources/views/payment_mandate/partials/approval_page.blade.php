<div class="pm-approval-shell">
    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            Mandate Approvals
        @endslot
    @endcomponent

    @if (isset($approvalTabs) && count($approvalTabs))
        <div class="pm-approval-tabs">
            @foreach ($approvalTabs as $tabKey => $tab)
                <a href="{{ route('payment-mandate.approvals', ['tab' => $tabKey]) }}"
                    class="pm-approval-tab {{ ($activeTab ?? null) === $tabKey ? 'pm-approval-tab--active' : '' }}">
                    <span>{{ $tab['tabLabel'] }}</span>
                    <span class="pm-approval-tab__badge {{ ($activeTab ?? null) === $tabKey ? 'pm-approval-tab__badge--active' : '' }}">
                        {{ number_format($tab['count'] ?? 0) }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif

    <div class="card pm-approval-panel">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    @if (session()->get('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @elseif(session()->get('warning'))
                        <div class="alert alert-warning">{{ session()->get('warning') }}</div>
                    @elseif(session()->get('danger'))
                        <div class="alert alert-danger">{{ session()->get('danger') }}</div>
                    @endif
                </div>
            </div>

            @if (isset($dd_requests) && $dd_requests->isNotEmpty())
                {!! Form::open(['url' => route($actionRoute), 'method' => 'POST', 'class' => 'js-approval-form']) !!}
                {{ Form::token() }}

                <div class="pm-approval-toolbar">
                    <div>
                        <div class="pm-approval-toolbar__title">{{ $tableTitle }}</div>
                        <p class="pm-approval-toolbar__hint">{{ $tableHint }}</p>
                    </div>

                    <button type="submit" class="btn btn-success btn-sm waves-effect waves-light js-approval-submit"
                        data-request-label="{{ $requestLabel }}"
                        data-button-label="{{ $buttonLabel }}"
                        name="{{ $actionName }}" value="{{ $actionValue }}">
                        <i class='bx {{ $buttonIcon }}'></i> {{ $buttonLabel }}
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered dt-responsive nowrap w-100" id="dynamicTable">
                        <thead>
                            <tr>
                                <th width="3%"><input type="checkbox" id="select_all" /></th>
                                <th width="20%">Account Name</th>
                                <th>Bank Name/MNO</th>
                                <th>Account Number</th>
                                <th>Mandate Reference</th>
                                <th>Primary Phone</th>
                                <th>{{ $requestLabel }}</th>
                                <th>Created Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dd_requests as $values)
                                @php
                                    $debitOrder = $values->debitOrder;
                                    $customerAccount = optional($debitOrder)->customerAccount;
                                    $customer = optional($customerAccount)->customer;
                                    $account = optional($customerAccount)->account;
                                    $isSelectable = !is_null($debitOrder);
                                    $accountName = $customerAccount->account_name ?? $customerAccount->phone ?? 'NIL';
                                    $maskedAccountNumber = $customerAccount->account_number ?? $customerAccount->phone ?? 'NIL';
                                    $phone = $customerAccount->phone ?? 'NIL';
                                    $reference = optional($debitOrder)->reference ?? 'NIL';
                                @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox" class="checkbox" id="request_id" name="request_id[]"
                                            value="{{ $values->id }}" @disabled(!$isSelectable)>
                                    </td>
                                    <td>
                                        {{ strtoupper($accountName) }}<br>
                                        ID: {{ $customer->id_number ?? 'NIL' }}
                                        @unless($isSelectable)
                                            <br><span class="badge badge-soft-secondary mt-1">Mandate record missing</span>
                                        @endunless
                                    </td>
                                    <td>{{ $account->name ?? 'NIL' }}</td>
                                    <td>
                                        {{ $maskedAccountNumber }}
                                    </td>
                                    <td>{{ $reference }}</td>
                                    <td>{{ $phone }}</td>
                                    <td>
                                        @if ($values->status == 'PENDING')
                                            <span class="badge badge-soft-warning">Pending</span>
                                        @elseif($values->status == 'APPROVED')
                                            <span class="badge bg-success">Approved</span>
                                        @elseif($values->status == 'REJECTED')
                                            <span class="badge badge-soft-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td>{{ date('d-m-Y H:i', strtotime($values->created_at)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-end p-3">
                        @if (method_exists($dd_requests, 'links'))
                            {{ $dd_requests->links() }}
                        @endif
                    </div>
                </div>

                {{ Form::close() }}
            @else
                <div class="alert alert-danger mb-0">{{ $emptyMessage }}</div>
            @endif
        </div>
    </div>
</div>
