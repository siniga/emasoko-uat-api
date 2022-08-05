@extends('layouts.master')

@section('title')
    Register User
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Users
        @endslot
        @slot('title')
           Register User
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

                    {{ Form::open(['url' => route('users.store'), 'method' => 'POST', 'class' => 'form-horizontal']) }}
                    {{ Form::token() }}

                    <div class="row">
                        <div class="col-lg-6 col-12">
                            <div class="mb-3">
                                <label>Fullname <span class="red">*</span></label>
                                {{ Form::text('name', old('name'), ['class="form-control"', 'placeholder="Write a full name..."']) }}
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            </div>
                        </div>
                        <!--./col-lg-4 -->

                        <div class="col-lg-3 col-12">
                            <div class="mb-3">
                                <label>Email <span class="red">*</span></label>
                                {{ Form::text('email', old('email'), ['class="form-control"', 'placeholder="Write a email address..."']) }}
                                <span class="text-danger">{{ $errors->first('email') }}</span>
                            </div>
                        </div>
                        <!--./col-lg-4 -->

                        <div class="col-lg-3 col-12">
                            <div class="mb-3">
                                <label>Phone <span class="red">*</span></label>
                                {{ Form::text('phone', old('phone'), ['class="form-control"', 'placeholder="Write a phone..."']) }}
                                <span class="text-danger">{{ $errors->first('phone') }}</span>
                            </div>
                        </div>
                        <!--./col-lg-4 -->
                    </div>
                    <!--./row -->

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h6 class="text-uppercase font-weight-600">Login Information</h6>
                            <hr />
                        </div>
                        <!--./col-md-12 -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label>Roles <span class="red">*</span></label>
                                <table>
                                    <tr>
                                        @php $serial = 0; @endphp
                                        @foreach ($roles as $role)
                                            @if ($serial % 6 == 0)
                                    </tr>
                                    <tr>
                                        @endif
                                        <td>
                                            {{ Form::checkbox('role_ids[]', $role->id, old('role_ids[]')) }}
                                            <label>{{ $role->description }}</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        </td>
                                        @php $serial++; @endphp
                                        @endforeach
                                    </tr>
                                </table>
                                <span class="text-danger">{{ $errors->first('role_ids') }}</span>
                            </div>
                        </div>
                        <!--./col-lg-12 -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label>Password <span class="red">*</span></label>
                                {{ Form::password('password', ['placeholder="Write a password..."', 'class="form-control"']) }}
                                <span class="text-danger">{{ $errors->first('password') }}</span>
                            </div>
                        </div>
                        <!--./col-lg-6 -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label>Confirm Password <span class="red">*</span> </label>
                                {{ Form::password('password_confirm', ['placeholder="Confirm password..."', 'class="form-control"']) }}
                                <span class="text-danger">{{ $errors->first('password_confirm') }}</span>
                            </div>
                        </div>
                        <!--./col-lg-6 -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <button type="submit" class="btn btn-secondary text-medium">Register</button>
                                <a href="{{ route('users.index') }}"
                                    class="btn btn-danger text-medium">Cancel</a>
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
