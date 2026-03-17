<style>
    .pm-approval-shell {
        --pm-primary: #153567;
        --pm-border: #d9e4f4;
        --pm-muted: #6b7c93;
    }

    .pm-approval-hero {
        border: 1px solid rgba(21, 53, 103, 0.14);
        border-radius: 20px;
        padding: 1.35rem;
        background:
            radial-gradient(circle at top left, rgba(255, 255, 255, 0.28), transparent 45%),
            linear-gradient(135deg, #153567 0%, #1f4a88 100%);
        color: #fff;
        box-shadow: 0 22px 44px rgba(15, 40, 79, 0.18);
    }

    .pm-approval-hero__eyebrow {
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        opacity: 0.8;
        margin-bottom: 0.45rem;
    }

    .pm-approval-hero__title {
        font-size: 1.55rem;
        font-weight: 700;
        margin-bottom: 0.45rem;
    }

    .pm-approval-hero__text {
        margin-bottom: 0;
        max-width: 780px;
        color: rgba(255, 255, 255, 0.82);
    }

    .pm-approval-panel {
        border: 1px solid #e3ebf6;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 18px 42px rgba(29, 53, 87, 0.08);
    }

    .pm-approval-note {
        border: 1px solid #dbe7f7;
        border-radius: 14px;
        background: #f8fbff;
        padding: 1rem 1.1rem;
    }

    .pm-approval-note__title {
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--pm-primary);
        margin-bottom: 0.35rem;
    }

    .pm-approval-note__text {
        color: var(--pm-muted);
        margin-bottom: 0;
        font-size: 0.9rem;
    }

    .pm-approval-stat {
        border: 1px solid var(--pm-border);
        border-radius: 14px;
        background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        padding: 1rem;
        height: 100%;
    }

    .pm-approval-stat__label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--pm-muted);
        margin-bottom: 0.35rem;
    }

    .pm-approval-stat__value {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--pm-primary);
    }

    .pm-approval-table {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #dfe8f4;
    }

    .pm-approval-table thead th {
        background: #edf4ff;
        color: var(--pm-primary);
        font-weight: 700;
        border-color: #dce7fa;
        vertical-align: middle;
    }

    .pm-approval-toolbar {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .pm-approval-toolbar__title {
        color: var(--pm-primary);
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 0.2rem;
    }

    .pm-approval-toolbar__hint {
        color: var(--pm-muted);
        font-size: 0.88rem;
        margin-bottom: 0;
    }
</style>

<div class="pm-approval-shell">
    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            {{ $pageTitle }}
        @endslot
    @endcomponent

    <div class="pm-approval-hero mb-4">
        <div class="pm-approval-hero__eyebrow">Mandate Workflow Approval</div>
        <div class="pm-approval-hero__title">{{ $pageTitle }}</div>
        <p class="pm-approval-hero__text">{{ $pageSubtitle }}</p>
    </div>

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
                <div class="row g-3 mb-4">
                    <div class="col-lg-8">
                        <div class="pm-approval-note">
                            <div class="pm-approval-note__title">Approval Guidance</div>
                            <p class="pm-approval-note__text">{{ $guidanceText }}</p>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="pm-approval-stat">
                            <div class="pm-approval-stat__label">Pending Requests</div>
                            <div class="pm-approval-stat__value">{{ number_format($dd_requests->count()) }}</div>
                        </div>
                    </div>
                </div>

                {!! Form::open(['url' => route($actionRoute), 'method' => 'POST']) !!}
                {{ Form::token() }}

                <div class="pm-approval-toolbar">
                    <div>
                        <div class="pm-approval-toolbar__title">{{ $tableTitle }}</div>
                        <p class="pm-approval-toolbar__hint">{{ $tableHint }}</p>
                    </div>

                    <button type="submit" class="btn btn-success btn-sm waves-effect waves-light"
                        name="{{ $actionName }}" value="{{ $actionValue }}">
                        <i class='bx {{ $buttonIcon }}'></i> {{ $buttonLabel }}
                    </button>
                </div>

                <div class="table-responsive pm-approval-table">
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
