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
                                <a href="#step-2" type="button" class="btn btn-secondary btn-circle"
                                    disabled="disabled">2</a>
                                <span class="text-primary">Company Documents</span>
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
                                <a href="#step-5" type="button" class="btn btn-default btn-circle"
                                    disabled="disabled">5</a>
                                <span>Credentials</span>
                            </div>
                        </div>
                    </div>


                    @if (session()->get('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div><br />
                    @elseif(session()->get('danger'))
                        <div class="alert alert-danger">
                            {{ session()->get('danger') }}
                        </div><br />
                    @endif

                    {{ Form::open(['url' => route('companies.store-documents'), 'class' => 'form-horizontal needs-validation', 'enctype' => 'multipart/form-data', 'novalidate']) }}
                    @method('POST')
                    {{ Form::token() }}

                    <input type="hidden" name="partner_id" value="{{ $company->id }}">
                    <input type="hidden" name="partner_uuid" value="{{ $company->uuid }}">

                    <!-- Company Documents -->
                    <section data-step="2">
                        <div class="row">
                            <div class="col-lg-12">
                                <h5 class="text-capitalize">Company Documents</h5>
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
                                                                <a href="{{ asset('assets/uploads/docs') }}">{{ $val->key }}</a>
                                                                
                                                                <input type="file" name="{{ $val->key }}" id="formFile"
                                                                class="form-control" accept="application/pdf" />
                                                            @else    
                                                            <input type="file" name="{{ $val->key }}" id="formFile"
                                                                class="form-control" accept="application/pdf"
                                                                @if ($val->required == 1) required @endif />
                                                            @endif

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
                                    <a href="{{ route('companies.registration', $company->uuid) }}"
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
    <!-- end row -->
@endsection

@section('script')
    <script type="text/javascript"></script>
@endsection
