@extends('layouts.master')

@section('title')
    Create New Role
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Roles
        @endslot
        @slot('title')
            Create New Role
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

                    {{ Form::open(['url' => route('roles.store'), 'method' => 'POST', 'class' => 'form-horizontal needs-validation', 'novalidate']) }}
                    {{ Form::token() }}

                    <div class="row">
                        <div class="offset-2 col-lg-8">
                            <div class="mb-3">
                                <label>Name <span class="red">*</span></label>
                                {{ Form::text('name', old('name'), ['class="form-control"', 'placeholder="Write a role name..."', 'required']) }}
                                <span class="invalid-feedback">{{ $errors->first('name') }}</span>

                                <div class="invalid-feedback">
                                    Role name required
                                </div>
                            </div>
                        </div>
                        <!--./col-lg-12 -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="offset-2 col-lg-8">
                            <div class="mb-3">
                                <label>Description <span class="red">*</span></label>
                                {{ Form::textarea('description', old('description'), ['class="form-control"', 'placeholder="Write role description..."', 'rows="3"', 'required']) }}
                                <span class="invalid-feedback">{{ $errors->first('description') }}</span>

                                <div class="invalid-feedback">
                                    Role description required
                                </div>
                            </div>
                        </div>
                        <!--./col-lg-12 -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="offset-2 col-lg-8">
                            <div class="mb-3 text-sm-end d-none d-sm-block">
                                <a href="{{ url('roles') }}" class="btn btn-danger btn-xs text-medium">Cancel</a>
                                <button type="submit" class="btn btn-primary btn-xs text-medium">Submit</button>
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
