@extends('layouts.master')

@section('title')
    Edit User
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/admin-directory.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="sp-list-shell admin-directory-shell" x-data="userRoleForm({ roleId: '{{ old('role_id', $current_role->id ?? '') }}', isApiUser: {{ old('is_api_user', $user->is_api_user ?? false) ? 'true' : 'false' }} })" x-init="init()">
        @component('components.breadcrumb')
            @slot('li_1')
                Users
            @endslot
            @slot('title')
                Edit User
            @endslot
        @endcomponent


        <div class="row">
            <div class="col-lg-9 col-md-10 col-sm-12">
                <div class="card sp-list-card">
                    <div class="card-body">
                        {{ Form::open(['url' => route('users.update', $user->id), 'method' => 'PUT', 'class' => 'form-horizontal']) }}
                        {{ Form::token() }}

                        <div class="admin-directory-section mb-4">
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
                                        {{ Form::text('email', old('email', $user->email), ['class' => 'form-control', 'placeholder' => 'Write an email address...', 'required']) }}
                                        <span class="text-danger">{{ $errors->first('email') }}</span>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="mb-0">
                                        <label>Phone <span class="red">*</span></label>
                                        {{ Form::text('phone', old('phone', $user->phone), ['class' => 'form-control', 'placeholder' => 'Write a phone number...', 'required']) }}
                                        <span class="text-danger">{{ $errors->first('phone') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="admin-directory-section mb-4">
                            <div class="admin-directory-section__title">Access Setup</div>
                            <div class="admin-directory-section__hint">Adjust role, provider scope, company scope, and API access as needed.</div>

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label>Main Role <span class="red">*</span></label>
                                        @php $options = ['' => '-- Select --']; @endphp
                                        @foreach ($roles as $role)
                                            @php $options[$role->id] = $role->description; @endphp
                                        @endforeach
                                        {{ Form::select('role_id', $options, old('role_id', $current_role->id ?? ''), ['class' => 'form-control', 'id' => 'role_id', 'x-model' => 'roleId', '@change' => 'syncRoleView()']) }}
                                        <span class="text-danger">{{ $errors->first('role_id') }}</span>
                                    </div>
                                </div>

                                <div class="col-lg-6" x-show="showCompanyView" x-cloak>
                                    <div class="mb-3">
                                        <label>eMakato Partner</label>
                                        @php $options = ['' => '-- Select --']; @endphp
                                        @foreach ($companies as $company)
                                            @php $options[$company->id] = $company->company_name; @endphp
                                        @endforeach
                                        {{ Form::select('company_id', $options, old('company_id', $company_user->company_id ?? ''), ['class' => 'form-control', 'id' => 'company_id']) }}
                                        <span class="text-danger">{{ $errors->first('company_id') }}</span>
                                    </div>
                                </div>

                                <div class="col-lg-6" x-show="showProviderView" x-cloak>
                                    <div class="mb-3">
                                        <label>Service Provider</label>
                                        @php $options = []; @endphp
                                        @foreach ($providers as $provider)
                                            @php $options[$provider->id] = $provider->business_name; @endphp
                                        @endforeach
                                        {{ Form::select('provider_ids[]', $options, old('provider_ids', $assigned_partners ?? null), ['class' => 'form-control', 'id' => 'provider_id', 'data-placeholder' => 'Choose...']) }}
                                        <span class="text-danger">{{ $errors->first('provider_ids') }}</span>
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="mb-0">
                                        <label>Is API User?</label><br>
                                        <label class="form-check-label">
                                            {{ Form::checkbox('is_api_user', 1, old('is_api_user', $user->is_api_user ?? false), ['class' => 'form-check-input', 'x-model' => 'isApiUser']) }} Yes
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row" x-show="isApiUser" x-cloak>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label>Username <span class="red">*</span></label>
                                        {{ Form::text('username', old('username', $user->username ?? ''), ['class' => 'form-control', 'placeholder' => 'Write API username...']) }}
                                        <span class="text-danger">{{ $errors->first('username') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="admin-directory-section">
                            <div class="admin-directory-section__title">Password Reset</div>
                            <div class="admin-directory-section__hint">Leave these blank if you do not want to reset the password.</div>

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label>Password</label>
                                        {{ Form::password('password', ['class' => 'form-control', 'placeholder' => 'Write a password...']) }}
                                        <span class="text-danger">{{ $errors->first('password') }}</span>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label>Confirm Password</label>
                                        {{ Form::password('password_confirm', ['class' => 'form-control', 'placeholder' => 'Confirm password...']) }}
                                        <span class="text-danger">{{ $errors->first('password_confirm') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('users.lists') }}" class="btn btn-outline-danger">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update User</button>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        function userRoleForm(config = {}) {
            return {
                roleId: config.roleId || '',
                isApiUser: config.isApiUser || false,
                showProviderView: false,
                showCompanyView: false,
                init() {
                    this.syncRoleView();
                },
                syncRoleView() {
                    this.showProviderView = this.roleId == 2 || this.roleId == 4;
                    this.showCompanyView = this.roleId == 6;
                }
            }
        }
    </script>
@endsection
