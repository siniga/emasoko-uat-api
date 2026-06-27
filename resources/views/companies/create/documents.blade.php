@extends(($selfRegistration ?? false) ? 'layouts.self-registration' : 'layouts.master')

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

                        @unless($selfRegistration ?? false)
                        <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('companies.lists') }}">Service Providers</a></li>
                        <li class="breadcrumb-item active">Register New</li>
                    </ol>
                        </div>
                        @endunless

            </div>
        </div>
    </div>

    @include('companies.create.partials.hero', [
        'currentStep' => 2,
        'heading' => 'Upload supporting documents',
        'subtitle' => ($selfRegistration ?? false)
            ? 'Add the compliance documents required before providing your contact representative.'
            : 'Add the compliance documents required to move this service provider to account setup and final review.',
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
                                        <a href="{{ ($selfRegistration ?? false) ? route('self-registration.information', $company->uuid) : route('companies.registration', $company->uuid) }}"
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

                    const submitButton = $('#submitButton');
                    const defaultSubmitText = submitButton.text();
                    submitButton.prop('disabled', true).text('Submitting...');

                    //form data
                    const formData = new FormData(form);

                    //append token
                    formData.append("_token", "{{ csrf_token() }}");

                    //post data 
                    $.ajax({
                        type: "POST",
                        url: @json(($selfRegistration ?? false) ? route('self-registration.store-documents') : route('companies.store-documents')),
                        data: formData,
                        processData: false,
                        contentType: false,
                        cache: false,
                        timeout: 800000,
                        success: function(response) {
                            //display message
                            $('.form-message').html(
                                "<div class='alert alert-success py-2.5 px-2'>Service Provider documents uploaded</div>"
                            );

                            //set timer
                            setTimeout(function() {
                                window.location.href = @json(($selfRegistration ?? false) ? url('/service-provider/register') : url('/companies/accounts')) +
                                    "/" + response.uuid + @json(($selfRegistration ?? false) ? '/representative' : '');
                            }, 300);
                        },
                        error: function(error) {
                            const errors = error.responseJSON?.errors;
                            const message = error.responseJSON?.message || 'Failed to upload documents.';

                            if (jQuery.type(errors) == "object") {
                                $('.form-message').html('');
                                $.each(errors, function(key, value) {
                                    $('.form-message').append('<div class="alert alert-danger">' + value + '</div>');
                                });
                            } else {
                                $('.form-message').html('<div class="alert alert-danger">' + message + '</div>');
                            }
                        },
                        complete: function() {
                            submitButton.prop('disabled', false).text(defaultSubmitText);
                        }
                    });

                    return false;
                }
            });
        });
    </script>
@endsection
