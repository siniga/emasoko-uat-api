@extends('layouts.master')

@section('title')
    Users
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/admin-directory.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="sp-list-shell admin-directory-shell">
        @component('components.breadcrumb')
            @slot('li_1')
                Dashboard
            @endslot
            @slot('title')
                Users
            @endslot
        @endcomponent

        <div class="row">
            <div class="col-lg-12">
                <div class="sp-list-filter mb-3">
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
                                @php $options = ['' => '-- Status --', 1 => 'Active', 0 => 'Inactive']; @endphp
                                {{ Form::select('status', $options, old('status', $post_data['status']), ['class' => 'form-control']) }}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                {{ Form::text('keyword', old('keyword', $post_data['keyword']), ['class' => 'form-control', 'placeholder' => 'Search by name, email, or phone...']) }}
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3 text-sm-end d-none d-sm-block">
                                <button type="reset" name="reset" class="btn btn-outline-danger">
                                    <i class='bx bx-reset'></i> Clear
                                </button>

                                <button type="submit" name="filter" class="btn btn-secondary">
                                    <i class="bx bx-search"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                    {{ Form::close() }}
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="detail-summary shadow h-96 py-2">
                            <div class="detail-summary__label text-muted">Total Users</div>
                            <div class="detail-summary__value text-primary">{{ number_format($listStats['total']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-summary shadow h-96 py-2">
                            <div class="detail-summary__label text-muted">Active</div>
                            <div class="detail-summary__value text-success">{{ number_format($listStats['active']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-summary shadow h-96 py-2">
                            <div class="detail-summary__label text-muted">Inactive</div>
                            <div class="detail-summary__value text-danger">{{ number_format($listStats['inactive']) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-summary shadow h-96 py-2">
                            <div class="detail-summary__label text-muted">API Users</div>
                            <div class="detail-summary__value text-secondary">{{ number_format($listStats['api_users']) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card sp-list-card">
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

                        @if (isset($users) && $users)
                            <div class="table-responsive sp-list-table">
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
                                                $primaryPartner = optional(
                                                    optional($values->partners)->where('authorize', 1)->first(),
                                                )->partner;
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
                                                    <div class="dropdown sp-row-actions">
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
@endsection
