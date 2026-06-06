@extends('layouts.master')

@section('title')
    Create New Role
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
                Create New Role
            @endslot
        @endcomponent

        <div class="sp-list-filter admin-directory-hero mb-4">
            <div class="admin-directory-hero__eyebrow">Role Management</div>
            <div class="admin-directory-hero__title">Create New Role</div>
            <p class="admin-directory-hero__text">Define a clean role name and description that fits the permission model used across the platform.</p>
        </div>

        <div class="row">
            <div class="col-lg-7 col-md-9 col-sm-12">
                <div class="card sp-list-card admin-directory-card">
                    <div class="card-body">
                        {{ Form::open(['url' => route('roles.store'), 'method' => 'POST', 'class' => 'form-horizontal needs-validation', 'novalidate']) }}
                        {{ Form::token() }}

                        <div class="admin-directory-section mb-4">
                            <div class="admin-directory-section__title">Role Details</div>
                            <div class="admin-directory-section__hint">Capture the core information used to identify and explain this role.</div>

                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label>Name <span class="red">*</span></label>
                                        {{ Form::text('name', old('name'), ['class' => 'form-control', 'placeholder' => 'Write a role name...', 'required']) }}
                                        <span class="text-danger">{{ $errors->first('name') }}</span>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label>Description <span class="red">*</span></label>
                                        {{ Form::textarea('description', old('description'), ['class' => 'form-control', 'placeholder' => 'Write role description...', 'rows' => 3, 'required']) }}
                                        <span class="text-danger">{{ $errors->first('description') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('roles.index') }}" class="btn btn-light border">Cancel</a>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
