@extends('layouts.master')

@section('title')
    Users
@endsection

<style>
    .us-shell {
        --us-primary: #153567;
        --us-border: #dce6f5;
        --us-muted: #6b7c93;
    }

    .us-filter {
        border: 1px solid #e7edf6;
        border-radius: 16px;
        background: #fbfcfe;
        padding: 1.2rem;
        box-shadow: 0 16px 40px rgba(29, 53, 87, 0.08);
    }

    .us-filter__title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--us-primary);
        margin-bottom: 0.2rem;
    }

    .us-filter__hint {
        color: var(--us-muted);
        font-size: 0.88rem;
        margin-bottom: 1rem;
    }

    .us-summary {
        border: 1px solid var(--us-border);
        border-radius: 16px;
        background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        padding: 1rem;
        height: 100%;
    }

    .us-summary__label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--us-muted);
        margin-bottom: 0.35rem;
    }

    .us-summary__value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--us-primary);
    }

    .us-table {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #dfe8f4;
    }

    .us-table thead th {
        background: #edf4ff;
        color: var(--us-primary);
        font-weight: 700;
        border-color: #dce7fa;
    }

    .us-row-actions .dropdown-toggle {
        border: 0;
        background: transparent;
        color: var(--us-primary);
        border-radius: 999px;
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .us-row-actions .dropdown-toggle:hover,
    .us-row-actions .dropdown-toggle:focus {
        background: #edf4ff;
        color: var(--us-primary);
        box-shadow: none;
    }

    .us-row-actions .dropdown-toggle::after {
        display: none;
    }

    .us-row-actions .dropdown-menu {
        min-width: 10rem;
        border: 1px solid #dfe8f4;
        box-shadow: 0 16px 30px rgba(21, 53, 103, 0.12);
        border-radius: 12px;
        padding: 0.35rem 0;
    }

    .us-row-actions .dropdown-item {
        font-size: 0.875rem;
        padding: 0.55rem 0.9rem;
    }
</style>

@section('content')
    <div class="us-shell">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Users</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Users</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="us-filter mb-4">
                    <div class="us-filter__title">Filter Users</div>
                    <div class="us-filter__hint">Narrow the user portfolio by date, provider, account status, or keyword.</div>

                    {!! Form::open(['url' => route('users.lists'), 'method' => 'POST', 'class' => 'form-horizontal']) !!}
                    {{ Form::token() }}

                    <div class="row">
                        <div class="col-lg-3">
                            <div class="mb-3">
                                {{ Form::date('start_at', old('start_at', $post_data['start_at']), ['class' => 'form-control']) }}
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="mb-3">
                                {{ Form::date('end_at', old('end_at', $post_data['end_at']), ['class' => 'form-control']) }}
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="mb-3">
                                @php $options = ['' => '-- Service Provider --']; @endphp
                                @foreach ($providers as $val)
                                    @php $options[$val->id] = $val->business_name; @endphp
                                @endforeach
                                {{ Form::select('partner_id', $options, old('partner_id', $post_data['partner_id']), ['class' => 'form-control', 'id' => 'partner_id']) }}
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="mb-3">
                                @php
                                    $options = ['' => '-- Status --', 1 => 'Active', 0 => 'Inactive'];
                                @endphp
                                {{ Form::select('status', $options, old('status', $post_data['status']), ['class' => 'form-control']) }}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-9">
                            <div class="mb-3">
                                {{ Form::text('keyword', old('keyword', $post_data['keyword']), ['class' => 'form-control', 'placeholder' => 'Search by name, email, or phone...']) }}
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="mb-3 text-sm-end d-none d-sm-block">
                                <button type="submit" name="filter" class="btn btn-secondary">
                                    <i class="bx bx-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-2">
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                            <div>
                                <div class="us-filter__title mb-1">Filtered Summary</div>
                                <div class="us-filter__hint mb-0">These totals follow the filters currently applied above.</div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="us-summary">
                                    <div class="us-summary__label">Total Users</div>
                                    <div class="us-summary__value">{{ number_format($listStats['total']) }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="us-summary">
                                    <div class="us-summary__label">Active</div>
                                    <div class="us-summary__value text-success">{{ number_format($listStats['active']) }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="us-summary">
                                    <div class="us-summary__label">Inactive</div>
                                    <div class="us-summary__value text-danger">{{ number_format($listStats['inactive']) }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="us-summary">
                                    <div class="us-summary__label">API Users</div>
                                    <div class="us-summary__value text-info">{{ number_format($listStats['api_users']) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{ Form::close() }}
                </div>

                <div class="card">
                    <div class="card-body">
                        @if (session()->get('success'))
                            <div class="alert alert-success">{{ session()->get('success') }}</div>
                        @endif

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <span class="font-weight-500">No. of users:
                                    {{ number_format(method_exists($users, 'total') ? $users->total() : count($users)) }}</span>
                            </div>

                            <div class="col-md-6">
                                <div class="text-sm-end d-none d-sm-block">
                                    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fa fa-plus"></i> Register New
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            @if (isset($users) && $users)
                                <div class="table-responsive us-table">
                                    <table class="table table-hover table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th width="3%">#</th>
                                                <th width="22%">User</th>
                                                <th width="16%">Phone</th>
                                                <th width="18%">Role</th>
                                                <th width="16%">Provider</th>
                                                <th width="10%">Created</th>
                                                <th width="8%">Status</th>
                                                <th style="width: 40px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $serial = 1; @endphp
                                            @foreach ($users as $values)
                                                @php
                                                    $primaryPartner = optional(optional($values->partners)->where('authorize', 1)->first())->partner;
                                                @endphp
                                                <tr>
                                                    <td>{{ $serial }}</td>
                                                    <td>
                                                        {{ $values->name }}<br>
                                                        <span class="text-muted">{{ $values->email }}</span>
                                                    </td>
                                                    <td>{{ $values->phone }}</td>
                                                    <td>
                                                        {{ optional($values->roles->first())->description ?? 'NIL' }}
                                                        @if ($values->is_api_user)
                                                            <br><span class="badge badge-soft-info mt-1">API User</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $primaryPartner->business_name ?? 'NIL' }}</td>
                                                    <td>{{ date('d-m-Y', strtotime($values->created_at)) }}</td>
                                                    <td>
                                                        @if ($values->active == 1)
                                                            <span class="badge badge-soft-success">Active</span>
                                                        @else
                                                            <span class="badge badge-soft-danger">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="dropdown us-row-actions">
                                                            <button class="btn dropdown-toggle" type="button"
                                                                data-bs-toggle="dropdown" aria-expanded="false"
                                                                title="User Actions">
                                                                <i class="bx bx-dots-vertical-rounded fs-5"></i>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-end">
                                                                <a href="{{ route('users.edit', $values->id) }}"
                                                                    class="dropdown-item">Edit</a>
                                                                <a href="{{ route('users.change-status', $values->id) }}"
                                                                    class="dropdown-item">Change Status</a>
                                                                <a href="{{ route('users.delete', $values->id) }}"
                                                                    class="dropdown-item text-danger delete">Delete</a>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @php $serial++; @endphp
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-end mt-3">
                                    @if (method_exists($users, 'links'))
                                        {{ $users->links() }}
                                    @endif
                                </div>
                            @else
                                <div class="alert alert-danger">No user registered.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
