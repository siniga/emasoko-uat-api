@extends('layouts.master')

@section('title')
    Update Role
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Roles
        @endslot
        @slot('title')
            Update Role
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

                    {{ Form::open(['url' => route('roles.update', $role->id), 'method' => 'PUT', 'class' => 'form-horizontal needs-validation', 'novalidate']) }}
                    {{ Form::token() }}

                    <div class="row">
                        <div class="offset-2 col-lg-8">
                            <div class="mb-3">
                                <label>Name <span class="red">*</span></label>
                                {{ Form::text('name', old('name', $role->name), ['class="form-control"', 'placeholder="Write a role name..."', 'required']) }}
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            </div>
                        </div>
                        <!--./col-lg-12 -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="offset-2 col-lg-8">
                            <div class="mb-3">
                                <label>Description <span class="red">*</span></label>
                                {{ Form::textarea('description', old('description', $role->description), ['class="form-control"', 'placeholder="Write role description..."', 'rows="3"', 'required']) }}
                                <span class="text-danger">{{ $errors->first('description') }}</span>
                            </div>
                        </div>
                        <!--./col-lg-12 -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="offset-2 col-lg-8">
                            <div class="mb-3 text-sm-end d-none d-sm-block">
                                <a href="{{ url('roles') }}" class="btn btn-danger btn-xs text-medium">Cancel</a>
                                <button type="submit" class="btn btn-primary btn-xs text-medium">Update</button>
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
