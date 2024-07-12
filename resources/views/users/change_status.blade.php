@extends('layouts.master')

@section('title')
    Activate/Deactivate Account
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Users
        @endslot
        @slot('title')
            Activate/Deactivate Account
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    @if (session()->get('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div><br />
                    @endif

                    {{ Form::open(['url' => route('users.store-status', $user->id), 'method' => 'PUT', 'class' => 'form-horizontal needs-validation', 'novalidate']) }}
                    {{ Form::token() }}

                    <div class="row">
                        <div class="col-lg-3 col-12">
                            <div class="mb-3">
                                <label>Name <span class="red">*</span></label>
                                {{ Form::text('name', old('name', $user->name), ['class="form-control"', 'placeholder="Write a full name..."', 'required']) }}
                                <span class="invalid-feedback">{{ $errors->first('name') }}</span>

                                <div class="invalid-feedback">
                                    Name required
                                </div>
                            </div>
                        </div>
                        <!--./col-lg-4 -->

                        <div class="col-lg-3 col-12">
                            <div class="mb-3">
                                <label>Email <span class="red">*</span></label>
                                {{ Form::text('email', old('email', $user->email), ['class="form-control"', 'placeholder="Write a email address..."', 'required']) }}
                                <span class="invalid-feedback">{{ $errors->first('email') }}</span>

                                <div class="invalid-feedback">
                                    Email required
                                </div>
                            </div>
                        </div>
                        <!--./col-lg-4 -->

                        <div class="col-lg-3 col-12">
                            <div class="mb-3">
                                <label>Phone <span class="red">*</span></label>
                                {{ Form::text('phone', old('phone', $user->phone), ['class="form-control"', 'placeholder="Write a phone..."', 'required']) }}
                                <span class="invalid-feedback">{{ $errors->first('phone') }}</span>

                                <div class="invalid-feedback">
                                    Phone required
                                </div>
                            </div>
                        </div>
                        <!--./col-lg-4 -->

                        <div class="col-lg-3 col-12">
                            <div class="mb-3">
                                <label>Status <span class="red">*</span></label>
                                @php
                                    $_options = [
                                        '' => '-- Select --',
                                        1 => 'Active',
                                        0 => 'Inactive',
                                    ];
                                @endphp
                                {{ Form::select('status', $_options, old('status', $user->active), ['class' => 'form-control', 'required']) }}
                                <span class="invalid-feedback">{{ $errors->first('status') }}</span>

                                <div class="invalid-feedback">
                                    Status required
                                </div>
                            </div>
                            <!--./form-group -->
                        </div>
                        <!--./col -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mb-3 text-sm-end d-none d-sm-block">
                                <a href="{{ route('users.index') }}" class="btn btn-danger text-medium">Cancel</a>
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
