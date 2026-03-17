@extends('layouts.master')

@section('title')
    Change User Status
@endsection

<style>
    .user-form-shell {
        --uf-primary: #153567;
        --uf-muted: #6b7c93;
    }

    .user-form-hero {
        border: 1px solid rgba(21, 53, 103, 0.14);
        border-radius: 20px;
        padding: 1.35rem;
        background:
            radial-gradient(circle at top left, rgba(255, 255, 255, 0.28), transparent 45%),
            linear-gradient(135deg, #153567 0%, #1f4a88 100%);
        color: #fff;
        box-shadow: 0 22px 44px rgba(15, 40, 79, 0.18);
    }

    .user-form-hero__eyebrow {
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        opacity: 0.8;
        margin-bottom: 0.45rem;
    }

    .user-form-hero__title {
        font-size: 1.55rem;
        font-weight: 700;
        margin-bottom: 0.45rem;
    }

    .user-form-hero__text {
        margin-bottom: 0;
        color: rgba(255, 255, 255, 0.84);
        max-width: 760px;
    }

    .user-form-card {
        border: 1px solid #e3ebf6;
        border-radius: 18px;
        box-shadow: 0 18px 42px rgba(29, 53, 87, 0.08);
    }
</style>

@section('content')
    <div class="user-form-shell">
        @component('components.breadcrumb')
            @slot('li_1')
                Users
            @endslot
            @slot('title')
                Change User Status
            @endslot
        @endcomponent

        <div class="user-form-hero mb-4">
            <div class="user-form-hero__eyebrow">User Management</div>
            <div class="user-form-hero__title">Change User Status</div>
            <p class="user-form-hero__text">Activate or deactivate a user account while keeping the user profile visible for operational review.</p>
        </div>

        <div class="row">
            <div class="col-lg-8 col-md-10 col-sm-12">
                <div class="card user-form-card">
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
                            <a href="{{ route('users.index') }}" class="btn btn-light border">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
