@extends('layouts.master')

@section('title')
    Roles
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
                Roles
            @endslot
        @endcomponent

        <div class="sp-list-filter admin-directory-filter mb-4">
            <div class="border-top-0">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="detail-summary shadow h-96 py-2">
                            <div class="detail-summary__label text-muted">Total Roles</div>
                            <div class="detail-summary__value text-primary">{{ number_format($listStats['total'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-summary shadow h-96 py-2">
                            <div class="detail-summary__label text-muted">Configured</div>
                            <div class="detail-summary__value text-success">{{ number_format($listStats['configured'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-summary shadow h-96 py-2">
                            <div class="detail-summary__label text-muted">Without Permissions</div>
                            <div class="detail-summary__value text-warning">{{ number_format($listStats['without_permissions'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-summary shadow h-96 py-2">
                            <div class="detail-summary__label text-muted">Assigned Methods</div>
                            <div class="detail-summary__value text-secondary">{{ number_format($listStats['assigned_methods'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card sp-list-card admin-directory-card">
            <div class="card-body">
                @if (session()->get('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-6">
                        <span class="font-weight-500">No. of roles: {{ number_format(count($roles ?? [])) }}</span>
                    </div>
                    <div class="col-md-6">
                        <div class="text-sm-end d-none d-sm-block">
                            <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> Create New
                            </a>
                        </div>
                    </div>
                </div>

                @if (isset($roles) && $roles)
                    <div class="table-responsive sp-list-table admin-directory-table">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th width="3%">#</th>
                                    <th width="20%">Name</th>
                                    <th width="65%">Description</th>
                                    <th style="width: 40px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $serial = 1; @endphp
                                @foreach ($roles as $values)
                                    <tr>
                                        <td>{{ $serial }}</td>
                                        <td>{{ $values->name }}</td>
                                        <td>{{ $values->description }}</td>
                                        <td class="text-center">
                                            <div class="dropdown admin-directory-actions">
                                                <button class="btn dropdown-toggle" type="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false"
                                                    title="Role Actions">
                                                    <i class="bx bx-dots-vertical-rounded fs-5"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a href="{{ route('roles.edit', $values->id) }}" class="dropdown-item">Edit</a>
                                                    <a href="{{ route('roles.assign-perms', $values->id) }}" class="dropdown-item">Assign Permission</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @php $serial++; @endphp
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-danger">No role created.</div>
                @endif
            </div>
        </div>
    </div>
@endsection
