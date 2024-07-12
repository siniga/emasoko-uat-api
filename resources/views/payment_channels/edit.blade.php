@extends('layouts.master')

@section('title')
    Update Payment Channel
@endsection 

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            Update Payment Channel
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

                    {{ Form::open(['url' => route('payment-channels.update', $account->id), 'method' => 'PUT', 'class' => 'form-horizontal needs-validation', 'novalidate']) }}
                    {{ Form::token() }}

                    <div class="row">
                        <div class="col-lg-4 col-12">
                            <div class="mb-3">
                                <label for="basicpill-namecard-input">Payment Channel <span
                                        class="text-danger">*</span></label>
                                @php
                                    $account_type_options = [];
                                    $options = [];
                                @endphp
                                @foreach ($account_types as $val)
                                    @php
                                        $options[$val->id] = $val->name;
                                    @endphp
                                @endforeach
                                @php
                                    $account_type_options = $options;
                                    $options = ['' => 'Select'] + $options;
                                @endphp

                                {{ Form::select('account_type_id', $options, old('account_type_id', $account->account_type_id), ['class' => 'form-control', 'required']) }}
                                <div class="invalid-feedback">
                                    This field required
                                </div>
                            </div>
                        </div>
                        <!--./col -->

                        <div class="col-lg-4 col-12">
                            <div class="mb-3">
                                <label>Payment Channel Name <span class="red">*</span></label>
                                {{ Form::text('name', old('name', $account->name), ['class="form-control"', 'placeholder="Write a channel name..."', 'required']) }}
                                <span class="invalid-feedback">{{ $errors->first('name') }}</span>

                                <div class="invalid-feedback">
                                    Payment channel name required
                                </div>
                            </div>
                        </div>
                        <!--./col-lg-12 -->

                        <div class="col-lg-4 col-12">
                            <div class="mb-3">
                                <label>Status <span class="red">*</span></label>
                                @php
                                    $_options = [
                                        '' => '-- Select --',
                                        1 => 'Active',
                                        0 => 'Inactive',
                                    ];
                                @endphp
                                {{ Form::select('status', $_options, old('status', $account->active), ['class' => 'form-control', 'required']) }}
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
                        <div class="col-lg-12 col-12">
                            <div class="mb-3">
                                <label>Description <span class="red">*</span></label>
                                {{ Form::textarea('description', old('description', $account->description), ['class="form-control"', 'placeholder="Write payment channel description..."', 'rows="3"', 'required']) }}
                                <span class="invalid-feedback">{{ $errors->first('description') }}</span>

                                <div class="invalid-feedback">
                                    Description required
                                </div>
                            </div>
                        </div>
                        <!--./col-lg-12 -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="col-lg-12 col-12">
                            <div class="mb-3 text-sm-end d-none d-sm-block">
                                <a href="{{ url('payment-channels') }}" class="btn btn-danger btn-xs text-medium">Cancel</a>
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
