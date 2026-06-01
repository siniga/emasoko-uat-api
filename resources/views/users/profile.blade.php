@extends('layouts.master')

@section('title')
    My Profile
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/admin-directory.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="sp-list-shell admin-directory-shell">
        @component('components.breadcrumb')
            @slot('li_1')
                Account
            @endslot
            @slot('title')
                My Profile
            @endslot
        @endcomponent

        @php
            $primaryRole = $user->roles->first();
            $providerCount = $user->partners->where('authorize', 1)->count();
            $companyCount = $user->companies->where('authorize', 1)->count();
        @endphp

        <div class="row">
            <div class="col-lg-9 col-md-10 col-sm-12">
                <div class="card sp-list-card admin-directory-card">
                    <div class="card-body">
                        <div class="admin-directory-section mb-4">
                            <div class="admin-directory-section__title">Account Details</div>
                            <div class="admin-directory-section__hint">Update your personal information here. Role and access scope are managed separately.</div>
                        </div>

                        {{ Form::open(['url' => route('users.update-profile'), 'method' => 'PUT', 'class' => 'form-horizontal']) }}
                        {{ Form::token() }}

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label>Full Name <span class="red">*</span></label>
                                    {{ Form::text('name', old('name', $user->name), ['class' => 'form-control', 'placeholder' => 'Write a full name...', 'required']) }}
                                    <span class="text-danger">{{ $errors->first('name') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label>Email <span class="red">*</span></label>
                                    {{ Form::email('email', old('email', $user->email), ['class' => 'form-control', 'placeholder' => 'Write an email address...', 'required']) }}
                                    <span class="text-danger">{{ $errors->first('email') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label>Phone <span class="red">*</span></label>
                                    {{ Form::text('phone', old('phone', $user->phone), ['class' => 'form-control', 'placeholder' => 'Write a phone number...', 'required']) }}
                                    <span class="text-danger">{{ $errors->first('phone') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label>Main Role</label>
                                    <input type="text" class="form-control" value="{{ $primaryRole->description ?? 'Not assigned' }}" readonly>
                                </div>
                            </div>

                            @if ($user->is_api_user)
                                <div class="col-lg-6">
                                    <div class="mb-0">
                                        <label>API Username <span class="red">*</span></label>
                                        {{ Form::text('username', old('username', $user->username), ['class' => 'form-control', 'placeholder' => 'Write API username...', 'required']) }}
                                        <span class="text-danger">{{ $errors->first('username') }}</span>
                                    </div>
                                </div>
                                <input type="hidden" name="is_api_user" value="1">
                            @endif
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-danger">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
