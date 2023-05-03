@extends('layouts.master')

@section('title')
    Register Service Provider
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">Register Service Provider</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('companies.lists') }}">Service Providers</a></li>
                    <li class="breadcrumb-item active">Register New</li>
                </ol>
            </div>

        </div>
    </div>
</div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body" x-data="initData()">
                    <!--./form wizard -->
                    <div class="stepwizard mb-4">
                        <div class="stepwizard-row setup-panel">

                            <div class="stepwizard-step">
                                <a href="#step-1" type="button" class="btn btn-success btn-circle text-white">1</a>
                                <span class="text-success">Company Information</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-success btn-circle text-white"
                                    disabled="disabled">2</a>
                                <span class="text-success">Company Documents</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-success btn-circle text-white"
                                    disabled="disabled">3</a>
                                <span class="text-success">Collection Accounts</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-success btn-circle text-white"
                                    disabled="disabled">4</a>
                                <span class="text-success">Authorized Representative</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-5" type="button" class="btn btn-secondary btn-circle"
                                    disabled="disabled">5</a>
                                <span class="text-primary">Credentials</span>
                            </div>
                        </div>
                    </div>


                    @if (session()->get('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div>
                    @elseif(session()->get('danger'))
                        <div class="alert alert-danger">
                            {{ session()->get('danger') }}
                        </div>
                    @endif


                    {{ Form::open(['url' => route('companies.store-authorized-person'), 'class' => 'form-horizontal needs-validation', 'enctype' => 'multipart/form-data', 'novalidate']) }}
                    @method('POST')
                    {{ Form::token() }}

                    <input type="hidden" name="partner_id" value="{{ $company->id }}">
                    <input type="hidden" name="partner_uuid" value="{{ $company->uuid }}">

                    <!-- Company Documents -->
                    <section data-step="5">
                        <div class="row">
                            <div class="col-lg-12">
                                <h5 class="text-capitalize"> Login Credentials</h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label for="email">Business Name <span class="text-danger">*</span></label>
                                    {{ Form::text('name', old('name', $company->business_name), ['class="form-control"', 'placeholder="Write business name..."', 'required']) }}
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label for="email">Company Email <span class="text-danger">*</span></label>
                                    {{ Form::email('email', old('email', $company->email), ['class="form-control"', 'placeholder="Write email..."', 'required']) }}
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label for="phone_number">Password <span class="text-danger">*</span></label>
                                    {{ Form::password('password', ['placeholder="Write a password..."', 'class="form-control"', 'id="password"', 'required']) }}
                                    <span class="text-danger"></span>
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label for="category">Repeat Password <span class="text-danger">*</span></label>
                                    {{ Form::password('confirm_password', ['placeholder="Confirm password..."', 'class="form-control"', 'required']) }}
                                    <span class="text-danger"></span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3 text-sm-end d-none d-sm-block">
                                    <a href="{{ route('companies.register-staff', $company->uuid) }}"
                                        class="btn btn-info text-medium">Previous</a>
                                    <button type="submit" class="btn btn-primary">Next</button>
                                </div>
                            </div>
                        </div>
                    </section>
                    {{ Form::close() }}

                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript"></script>
@endsection
