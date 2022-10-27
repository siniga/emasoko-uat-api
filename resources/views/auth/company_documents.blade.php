@extends('layouts.master-without-nav')

@section('title')
    @lang('translation.Register')
@endsection

@section('content')
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-12 col-lg-12 col-xl-12">
                    <div class="card overflow-hidden">
                        <div class="bg-primary bg-soft">
                            <div class="row">
                                <div class="col-12">
                                    <div class="text-center p-4">
                                        <h2 class="text-primary">eMakato</h2>
                                        <h3>Company Registration</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--./soft -->

                        <div class="card-body pt-4">
                            <div class="stepwizard mb-4">
                                <div class="stepwizard-row setup-panel">
                                    <div class="stepwizard-step">
                                        <a href="#step-1" type="button" class="btn btn-success btn-circle">1</a>
                                        <span class="text-success">Company Information</span>
                                    </div>

                                    <div class="stepwizard-step">
                                        <a href="#step-2" type="button" class="btn btn-secondary btn-circle"
                                            disabled="disabled">2</a>
                                        <span>Company Documents</span>
                                    </div>

                                    <div class="stepwizard-step">
                                        <a href="#step-2" type="button" class="btn btn-default btn-circle"
                                            disabled="disabled">3</a>
                                        <span>Collection Accounts</span>
                                    </div>

                                    <div class="stepwizard-step">
                                        <a href="#step-2" type="button" class="btn btn-default btn-circle"
                                            disabled="disabled">4</a>
                                        <span>Authorized Representative</span>
                                    </div>

                                    <div class="stepwizard-step">
                                        <a href="#step-2" type="button" class="btn btn-default btn-circle"
                                            disabled="disabled">5</a>
                                        <span>Credentials</span>
                                    </div>
                                </div>
                            </div>
                            <!--./form wizard -->

                            @if (session()->get('success'))
                                <div class="alert alert-success">
                                    {{ session()->get('success') }}
                                </div><br />
                            @elseif(session()->get('danger'))
                                <div class="alert alert-danger">
                                    {{ session()->get('danger') }}
                                </div><br />
                            @endif

                            {{ Form::open(['url' => route('company.store-documents'), 'class' => 'form-horizontal needs-validation', 'enctype' => 'multipart/form-data', 'novalidate']) }}
                            @method('POST')
                            {{ Form::token() }}

                            <input type="hidden" name="partner_id" value="{{ $company->id }}">
                            <input type="hidden" name="partner_uuid" value="{{ $company->uuid }}">

                            <!-- Company Documents -->
                            <section data-step="2">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <h5 class="text-capitalize">Company Documents</h5>
                                        <hr />
                                    </div>
                                    <!--./col -->
                                </div>
                                <!--./row -->

                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <div class="table-responsive">
                                                <table class="table table-nowrap align-middle table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th width="70%">Attachments</th>
                                                            <th width="20%">Action</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        @foreach ($categories as $val)
                                                            <tr>
                                                                <td>
                                                                    {{ $val->name }}
                                                                    @if ($val->required == 1)
                                                                        <span class="text-danger">*</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if (in_array($val->key, $arr_documents))
                                                                        <a
                                                                            href="{{ asset('assets/uploads/docs') }}">{{ $val->key }}</a><br />
                                                                    @endif
                                                                    <input type="file" name="{{ $val->key }}"
                                                                        id="formFile" class="form-control"
                                                                        accept="application/pdf"
                                                                        @if ($val->required == 1) required @endif />

                                                                    <div class="invalid-feedback">
                                                                        This file required
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <!--./table-responsive -->
                                        </div>
                                    </div>
                                    <!--./col -->
                                </div>
                                <!--./row -->

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3 text-sm-end d-none d-sm-block">
                                            <a href="{{ url('register/' . $company->uuid) }}"
                                                class="btn btn-info text-medium">Previous</a>
                                            <button type="submit" id="btnSubmit" class="btn btn-primary">
                                                Next <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <!--./col -->
                                </div>
                                <!--./row -->
                            </section>
                            {{ Form::close() }}
                        </div>
                        <!--./card-body -->
                    </div>
                    <!--./card -->

                    <div class="mt-2 text-center">
                        <div>
                            <p>Already have an account ? <a href="{{ url('login') }}" class="fw-medium text-primary">
                                    Login</a>
                            </p>

                            <p>
                                <script>
                                    document.write(new Date().getFullYear())
                                </script> eMakato. All rights reserved.
                            </p>
                        </div>
                    </div>
                    <!--./text-center -->
                </div>
                <!--./col-->
            </div>
            <!--./row -->
        </div>
        <!--./container -->
    </div>
    <!--./account-pages -->
@endsection
@section('script')
    <script type="text/javascript"></script>
@endsection
