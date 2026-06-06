@extends('layouts.master')

@section('title')
    Register Service Provider
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/companies-create.css') }}" rel="stylesheet" type="text/css" />
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

    @include('companies.create.partials.hero', [
        'currentStep' => 2,
        'heading' => 'Upload supporting documents',
        'subtitle' => 'Add the compliance documents required to move this service provider to account setup and final review.',
        'company' => $company,
    ])

    <div class="row">
        <div class="col-lg-12">
            <div class="card sp-card">
                <div class="card-body sp-card__body">
                    <div class="form-message sp-form-message"></div>

                    <form action="#" id="companyForm" enctype="multipart/form-data" autocomplete="off">
                        <input type="hidden" name="partner_id" value="{{ $company->id }}">
                        <input type="hidden" name="partner_uuid" value="{{ $company->uuid }}">

                        <!-- Company Documents -->
                        <section data-step="2">
                            <div class="sp-section">
                                <div class="sp-section__title">Document Checklist</div>
                                <div class="sp-section__hint">Required files must be uploaded as PDF and should not exceed 5MB each.</div>

                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <div class="table-responsive">
                                            <table class="table table-nowrap align-middle table-bordered mb-0">
                                                <thead>
                                                    <tr>
                                                        <th width="70%">Attachments</th>
                                                        <th width="20%" class="text-danger">Note: Only PDF documents
                                                            required (Max 5MB)</th>
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
                                                                    <a href="{{ asset('assets/uploads/docs/' . $val->name) }}">{{ $val->key }}</a>

                                                                    <input type="file" name="{{ $val->key }}"
                                                                        id="formFile" class="form-control"
                                                                        accept="application/pdf" />
                                                                @else
                                                                    <input type="file" name="{{ $val->key }}"
                                                                        id="formFile" class="form-control"
                                                                        accept="application/pdf"
                                                                        @if ($val->required == 1) required @endif />
                                                                @endif
                                                                <span class="formError"></span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="sp-actionbar">
                                <div class="sp-actionbar__meta"></div>
                                <div class="d-flex gap-2">
                                        <a href="{{ route('companies.registration', $company->uuid) }}"
                                            class="btn btn-outline-secondary text-medium">Previous</a>

                                        <button type="submit" id="submitButton" class="btn btn-primary">Save and Continue</button>
                                </div>
                            </div>
                            </div>
                        </section>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->
@endsection

@section('script')
    <!--jquery validation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            //declare form message 
            $('.form-message').html("");

            //validate form
            $("#companyForm").validate({
                submitHandler: function(form, e) {
                    e.preventDefault();

                    //change submit button text
                    $('#submitButton').text('Submitting...');

                    //form data
                    const formData = new FormData(form);

                    //append token
                    formData.append("_token", "{{ csrf_token() }}");

                    //post data 
                    $.ajax({
                        type: "POST",
                        url: "/companies/store-documents",
                        data: formData,
                        processData: false,
                        contentType: false,
                        cache: false,
                        timeout: 800000,
                        success: function(response) {
                            //change submit button text
                            $('#submitButton').text('Next');

                            //display message
                            $('.form-message').html(
                                "<div class='alert alert-success py-2.5 px-2'>Service Provider documents uploaded</div>"
                            );

                            //set timer
                            setTimeout(function() {
                                window.location.href = "/companies/accounts/" +
                                    response.uuid;
                            }, 300);
                        },
                        error: function(error) {
                            //display errors
                            if (jQuery.type(error.responseJSON.errors) == "object") {
                                $('.form-message').html('');
                                $.each(error.responseJSON.errors, function(key, value) {
                                    $('.form-message').append('<div class="alert alert-danger">' + value + '</div>');
                                });
                            } else {
                                $('.form-message').html('<div class="alert alert-danger">' + error.responseJSON.errors + '</div>');
                            }

                            //change submit button
                            $('#submitButton').text('Next');
                        }
                    });

                    return false;
                }
            });
        });
    </script>
@endsection
