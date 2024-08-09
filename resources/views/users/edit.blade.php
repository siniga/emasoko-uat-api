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

                    {{ Form::open(['url' => route('users.update', $user->id), 'method' => 'PUT', 'class' => 'form-horizontal needs-validation', 'novalidate']) }}
                    {{ Form::token() }}

                    <div class="row">
                        <div class="col-lg-6 col-12">
                            <div class="mb-3">
                                <label>Fullname <span class="red">*</span></label>
                                {{ Form::text('name', old('name', $user->name), ['class="form-control"', 'placeholder="Write a full name..."', 'required']) }}
                                <span class="invalid-feedback">{{ $errors->first('name') }}</span>

                                <div class="invalid-feedback">
                                    Name required
                                </div>
                            </div>
                        </div>
                        <!--./col-lg-4 -->

                        <div class="col-lg-6 col-12">
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

                        <div class="col-lg-6 col-12">
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
                                        <?php $serial = 0; ?>
                                        @foreach ($roles as $role)
                                            @if ($serial % 6 == 0)
                                    </tr>
                                    <tr>
                                        @endif

                                        @php
                                            $rID = $role->id;
                                            $checked = null;
                                            $item = null;
                                            
                                            foreach ($current_roles as $grp) {
                                                if ($rID == $grp->role_id) {
                                                    $checked = ' checked="checked"';
                                                }
                                            }
                                        @endphp
                                        <td>
                                            <input type="checkbox" name="role_ids[]" value="{{ $role->id }}"
                                                {{ $checked }}>
                                            <label>{{ $role->description }}</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        </td>
                                        <?php $serial++; ?>
                                        @endforeach
                                    </tr>
                                </table>
                                <span class="invalid-feedback">{{ $errors->first('role_ids') }}</span>
                            </div>
                        </div>
                        <!--./col-lg-12 -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label>Password</label>
                                {{ Form::password('password', ['placeholder="Write a password..."', 'class="form-control"']) }}
                                <span class="invalid-feedback">{{ $errors->first('password') }}</span>
                            </div>
                        </div>
                        <!--./col-lg-6 -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label>Confirm Password </label>
                                {{ Form::password('password_confirm', ['placeholder="Confirm password..."', 'class="form-control"']) }}
                                <span class="invalid-feedback">{{ $errors->first('password_confirm') }}</span>
                            </div>
                        </div>
                        <!--./col-lg-6 -->
                    </div>
                    <!--./row -->

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
