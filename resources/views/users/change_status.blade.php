@extends('layouts.master')

@section('title')
    Change User Status
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/admin-directory.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="sp-list-shell admin-directory-shell">
        @component('components.breadcrumb')
            @slot('li_1')
                Users
            @endslot
            @slot('title')
                Change User Status
            @endslot
        @endcomponent

        <div class="row">
            <div class="col-lg-8 col-md-10 col-sm-12">
                <div class="card sp-list-card admin-directory-card">
                    <div class="card-body">
                        {{ Form::open(['url' => route('users.store-status', $user->id), 'method' => 'PUT', 'class' => 'form-horizontal']) }}
                        {{ Form::token() }}

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label>Name</label>
                                    {{ Form::text('name', old('name', $user->name), ['class' => 'form-control', 'readonly']) }}
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label>Email</label>
                                    {{ Form::text('email', old('email', $user->email), ['class' => 'form-control', 'readonly']) }}
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label>Phone</label>
                                    {{ Form::text('phone', old('phone', $user->phone), ['class' => 'form-control', 'readonly']) }}
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label>Status <span class="red">*</span></label>
                                    @php $_options = ['' => '-- Select --', 1 => 'Active', 0 => 'Inactive']; @endphp
                                    {{ Form::select('status', $_options, old('status', $user->active), ['class' => 'form-control', 'required']) }}
                                    <span class="text-danger">{{ $errors->first('status') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="{{ route('users.lists') }}" class="btn btn-outline-danger">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
