@extends('layouts.master')

@section('title')
    Edit User
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Users
        @endslot
        @slot('title')
           Edit User
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8 col-md-8 col-sm-8 col-12">
            <div class="card">
                <div class="card-body">
                    @if (session()->get('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div><br />
                    @endif

                    {{ Form::open(['url' => route('users.update', $user->id), 'method' => 'PUT', 'class' => 'form-horizontal']) }}
                    {{ Form::token() }}

                    <div class="row">
                        <div class="col-lg-6 col-12">
                            <div class="mb-3">
                                <label>Fullname <span class="red">*</span></label>
                                {{ Form::text('name', old('name', $user->name), ['class="form-control"', 'placeholder="Write a full name..."', 'required']) }}
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12">
                            <div class="mb-3">
                                <label>Email <span class="red">*</span></label>
                                {{ Form::text('email', old('email', $user->email), ['class="form-control"', 'placeholder="Write a email address..."', 'required']) }}
                                <span class="text-danger">{{ $errors->first('email') }}</span>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12">
                            <div class="mb-3">
                                <label>Phone<span class="red">*</span></label>
                                {{ Form::text('phone', old('phone', $user->phone), ['class="form-control"', 'placeholder="Write a phone..."', 'required']) }}
                                <span class="text-danger">{{ $errors->first('phone') }}</span>
                            </div>
                        </div>
                    </div>
                    <!--./row -->

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h6 class="text-uppercase font-weight-600">Login Information</h6>
                            <hr />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-md-6">
                            <div class="mb-3">
                                <label>Main Role <span class="red">*</span></label>
                                @php $options = []; @endphp
                                @foreach ($roles as $role)
                                    @php
                                        $options[$role->id] = $role->description;
                                    @endphp
                                @endforeach
                                @php
                                    $options = ['' => '-- Select --'] + $options;
                                @endphp
                                {{ Form::select('role_id', $options, old('role_id', $current_role->id ?? ''), ['class' => 'form-control', 'id' => 'role_id']) }}
                                <span class="text-danger">{{ $errors->first('role_id') }}</span>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-6">
                            <div class="mb-3">
                                <label>eMakato Partner</label>
                                @php $options = []; @endphp
                                @foreach ($companies as $company)
                                    @php
                                        $options[$company->id] = $company->company_name;
                                    @endphp
                                @endforeach
                                @php
                                    $options = ['' => '-- Select --'] + $options;
                                @endphp
                                {{ Form::select('company_id', $options, old('company_id', $company_user->id ?? ''), ['class' => 'form-control', 'id' => 'company_id']) }}
                                <span class="text-danger">{{ $errors->first('company_id') }}</span>
                            </div>
                        </div>

                        <div class="col-lg-12 col-md-12">
                            <div class="mb-3">
                                <label>Service Provider</label>
                                @php $options = []; @endphp
                                @foreach ($providers as $provider)
                                    @php
                                        $options[$provider->id] = $provider->business_name;
                                    @endphp
                                @endforeach
                                {{ Form::select('provider_ids[]', $options, old('provider_ids', $assigned_partners ?? null), ['class' => 'select2 form-control select2-multiple', 'id' => 'provider_id', 'multiple' => 'multiple', 'data-placeholder' => 'Choose...']) }}
                                <span class="text-danger">{{ $errors->first('provider_ids') }}</span>
                            </div>
                        </div>  
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label>Password</label>
                                {{ Form::password('password', ['placeholder="Write a password..."', 'class="form-control"']) }}
                                <span class="text-danger">{{ $errors->first('password') }}</span>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label>Confirm Password </label>
                                {{ Form::password('password_confirm', ['placeholder="Confirm password..."', 'class="form-control"']) }}
                                <span class="text-danger">{{ $errors->first('password_confirm') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mb-3 text-sm-end d-none d-sm-block">
                                <a href="{{ route('users.index') }}"
                                    class="btn btn-danger text-medium">Cancel</a>
                                <button type="submit" class="btn btn-primary text-medium">Update</button>
                            </div>
                        </div>
                        <!--./col-lg-12 -->
                    </div>
                    <!--./row -->
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
    <!--./row -->
@endsection
