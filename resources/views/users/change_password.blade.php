@extends('layouts.master')

@section('title')
    Change Password
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
                Change Password
            @endslot
        @endcomponent

        <div class="row">
            <div class="col-lg-8 col-md-10 col-sm-12">
                <div class="card sp-list-card">
                    <div class="card-body">
                        <div class="admin-directory-section mb-4">
                            <div class="admin-directory-section__title">Security Update</div>
                            <div class="admin-directory-section__hint">Use your current password to confirm the change, then
                                set a new password with at least 8 characters.</div>
                        </div>

                        {{ Form::open(['url' => route('users.update-password'), 'method' => 'PUT', 'class' => 'form-horizontal']) }}
                        {{ Form::token() }}

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label>Account</label>
                                    <input type="text" class="form-control" value="{{ $user->email }}" readonly>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label>Current Password <span class="red">*</span></label>
                                    {{ Form::password('current_password', ['class' => 'form-control', 'placeholder' => 'Enter current password...', 'required']) }}
                                    <span class="text-danger">{{ $errors->first('current_password') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label>New Password <span class="red">*</span></label>
                                    {{ Form::password('password', ['class' => 'form-control', 'placeholder' => 'Write a new password...', 'required']) }}
                                    <span class="text-danger">{{ $errors->first('password') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="mb-0">
                                    <label>Confirm New Password <span class="red">*</span></label>
                                    {{ Form::password('password_confirmation', ['class' => 'form-control', 'placeholder' => 'Confirm new password...', 'required']) }}
                                    <span class="text-danger">{{ $errors->first('password_confirmation') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('users.profile') }}" class="btn btn-outline-danger">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
