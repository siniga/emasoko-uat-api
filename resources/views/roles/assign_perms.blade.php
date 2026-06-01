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
                Roles
            @endslot
            @slot('title')
                Assign Permissions
            @endslot
        @endcomponent

        <div class="sp-list-filter admin-directory-hero mb-4">
            <div class="admin-directory-hero__eyebrow">Role Management</div>
            <div class="admin-directory-hero__title">Assign Permissions</div>
            <p class="admin-directory-hero__text">Choose the permissions that belong to <strong>{{ $role->name }}</strong> and keep the role scope aligned with its operational use.</p>
        </div>

        <div class="card sp-list-card admin-directory-card">
            <div class="card-body">
                {{ Form::open(['url' => route('roles.store-perms', $role->id), 'method' => 'POST', 'class' => 'form-horizontal']) }}
                {{ Form::token() }}
                <input type="hidden" name="role_id" value="{{ $role->id }}">

                @foreach ($perms as $key => $value)
                    <div class="admin-directory-section mb-4">
                        <div class="admin-directory-section__title">{{ $key }}</div>
                        <div class="admin-directory-section__hint">Select the actions this role should be allowed to perform in {{ strtolower($key) }}.</div>
                        <input type="hidden" name="classes[]" value="{{ $key }}">

                        <div class="admin-directory-chip-grid">
                            @foreach ($value as $v)
                                <div class="admin-directory-chip">
                                    <label>
                                        <input type="checkbox" name="perms[]" value="{{ $v[1] }}"
                                            {{ in_array($v[1], $assigned_perms) ? 'checked' : '' }} />
                                        <span>{{ $v[2] }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('roles.index') }}" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
