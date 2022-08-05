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

                    {{ Form::open(['url' => route('roles.store'), 'method' => 'POST', 'class' => 'form-horizontal']) }}
                    {{ Form::token() }}

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label>Name <span class="red">*</span></label>
                                {{ Form::text('name', old('name'), ['class="form-control"', 'placeholder="Write a role name..."']) }}
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            </div>
                        </div>
                        <!--./col-lg-12 -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label>Description <span class="red">*</span></label>
                                {{ Form::textarea('description', old('description'), ['class="form-control"', 'placeholder="Write role description..."', 'rows="3"']) }}
                                <span class="text-danger">{{ $errors->first('description') }}</span>
                            </div>
                        </div>
                        <!--./col-lg-12 -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary btn-xs text-medium">Create</button>
                                <a href="{{ url('roles') }}" class="btn btn-danger btn-xs text-medium">Cancel</a>
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
