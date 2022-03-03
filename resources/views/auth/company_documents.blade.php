@extends('layouts.master-without-nav')

@section('title')
    @lang('translation.Register')
@endsection
@section('css')
    <link href="{{ URL::asset('/assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.css') }}" rel="stylesheet"
        type="text/css">
@endsection
@section('body')

    <body>
    @endsection

    @section('content')
        <div class="account-pages my-5 pt-sm-5">
            <div class="container">
                <div class="row justify-content-center mt-4">
                    <div class="col-lg-9 col-md-9">
                        <!-- Header Start -->
                        <div class="header">
                            <div class="row">
                                <div class="col-md-1"></div>
                                <!--./col-md-1 -->
        
                                <div class="col-md-2">
                                    <div id="top-img">
                                        <a href="{{ route('login') }}">
                                            <img src="{{ asset('/assets/images/lainalogo.png') }}" />
                                        </a>
                                    </div>
                                    <!--./img -->
                                </div>
                                <!--./col-md-2 -->
                                <div class="col-md-8">
                                    <div id="top-content">
                                        <h1>eMakato</h1>
                                        <h3>Company Registration</h3>
                                    </div>
                                    <!--./top-content -->
                                </div>
                                <!--./col-md-8 -->
                                <div class="col-md-1"></div>
                                <!--./col-md-1 -->
                            </div>
                            <!--./row -->
                        </div><!-- Header End -->
                    </div>
                    <!--./col -->
                </div>
                <!--./row -->

                <div class="row justify-content-center">
                    <div class="col-md-12 col-lg-12 col-xl-12">
                        <div class="card overflow-hidden">
                            <div class="card-body pt-4">
                                <form action="#" id="registrationForm" enctype="multipart/form-data" autocomplete="off">
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

                                                            @php
                                                                $arr_attachments = [
                                                                    'business_licence' => 'Business Licence',
                                                                    'certificate_of_incorporation' => 'Certificate of incorporation',
                                                                    'TIN_certificate' => 'TIN Certificate',
                                                                    'brela_certificate' => 'BRELA certificate',
                                                                    'audited_financial_statement' => 'Audited financial statement',
                                                                    'introduction_letter' => 'Introduction letter introducing director as a custodian of the account.',
                                                                    'annual_return' => 'Annual return',
                                                                ];
                                                            @endphp

                                                            <tbody>
                                                                @foreach ($arr_attachments as $k => $val)
                                                                    <tr>
                                                                        <td>{{ $val }}</td>
                                                                        <td>
                                                                            @if (in_array($k, $arr_documents))
                                                                                <a href="{{ asset('assets/uploads/docs') }}">{{ $k }}</a><br/>
                                                                            @endif
                                                                            {{ Form::file($k, old($k), ['class="form-control"', 'id="formFile"']) }}
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
                                </form>
                            </div>
                            <!--./card-body -->
                        </div>
                        <!--./card -->

                        <div class="mt-2 text-center">
                            <div>
                                <p>
                                <script>
                                    document.write(new Date().getFullYear())
                                </script> eMakato. Developed by Laina Finance
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
        <script src="{{ URL::asset('/assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>

        <!--jquery validation -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js"></script>

        <script type="text/javascript">
            $(document).ready(function() {
                //validate form and submit
                $("#registrationForm").validate({
                    rules: {
                        business_licence: {
                            required: true
                        },
                        certificate_of_incorporation: {
                            required: true
                        },
                        TIN_certificate: {
                            required: true
                        }
                    },
                    messages: {
                        business_licence: {
                            required: "Business Licence required"
                        },
                        certificate_of_incorporation: {
                            required: "Certificate of incorporation required"
                        },
                        TIN_certificate: {
                            required: "TIN certificate required"
                        }
                    },
                    submitHandler: function(form, e) {
                        e.preventDefault();

                        // Activate the loading spinner
                        $('.loading-spinner').toggleClass('active');

                        // Get form
                        var form = $(form)[0];

                        // FormData object 
                        var data = new FormData(form);

                        //append token
                        data.append("_token", "{{ csrf_token() }}");

                        // disabled the submit button
                        $("#btnSubmit").prop("disabled", true);

                        //post data 
                        $.ajax({
                            type: "POST",
                            url: "{{ url('store-company-documents') }}",
                            data: data,
                            processData: false,
                            contentType: false,
                            cache: false,
                            timeout: 800000,
                            success: function(data) {
                                $("#btnSubmit").prop("disabled", false);

                                // Deactivate Loading Spinner
                                $('.loading-spinner').toggleClass('active');

                                //uuid
                                var uuid = data.uuid

                                //redirect
                                window.location.href = "/company-accounts/" + uuid
                            },
                            error: function(e) {
                                $("#btnSubmit").prop("disabled", false);
                            }
                        });
                        return false;
                    },
                });
            });
        </script>
    @endsection
