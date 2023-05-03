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
                                <a href="#step-2" type="button" class="btn btn-secondary btn-circle"
                                    disabled="disabled">4</a>
                                <span class="text-primary">Authorized Representative</span>
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
                        </div>
                    @elseif(session()->get('danger'))
                        <div class="alert alert-danger">
                            {{ session()->get('danger') }}
                        </div>
                    @endif

                    {{ Form::open(['url' => route('companies.store-staff'), 'class' => 'form-horizontal needs-validation', 'enctype' => 'multipart/form-data', 'novalidate']) }}
                    @method('POST')
                    {{ Form::token() }}

                    <input type="hidden" name="partner_id" value="{{ $company->id }}">
                    <input type="hidden" name="partner_uuid" value="{{ $company->uuid }}">

                    <section data-step="4">
                        <div class="row">
                            <div class="col-lg-12">
                                <h5 class="text-capitalize">Company Authorized Representative</h5>
                            </div>
                        </div>

                        @if ($company->partnerStaff->isNotEmpty())
                            @foreach ($company->partnerStaff as $data)
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="basicpill-namecard-input">Staff Name <span
                                                    class="text-danger">*</span></label>
                                            {{ Form::text('staff_name[]', old('staff_name[]', $data->name), ['class="form-control"', 'placeholder="Staff name..."', 'required=""']) }}

                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="basicpill-namecard-input">NIDA Number <span
                                                    class="text-danger">*</span></label>
                                            {{ Form::text('nida_number[]', old('nida_number[]', $data->NIDA_number), ['class="form-control input-mask"', 'placeholder="NIDA number..."', 'data-inputmask' => "'mask': '99999999-99999-99999-99'", 'required=""']) }}

                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="basicpill-namecard-input">Staff Email <span
                                                    class="text-danger">*</span></label>
                                            {{ Form::email('staff_email[]', old('staff_email[]', $data->email), ['class="form-control invinputs"', 'placeholder="Staff email..."', 'required=""']) }}
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="basicpill-namecard-input">Staff Phone <span
                                                    class="text-danger">*</span></label>
                                            {{ Form::number('staff_phone[]', old('staff_phone[]', $data->phone), ['class="form-control"', 'placeholder="Staff phone..."', 'required=""']) }}
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="basicpill-namecard-input">Staff Title <span
                                                    class="text-danger">*</span></label>
                                            {{ Form::text('staff_title[]', old('staff_title[]', $data->title), ['class= "form-control"', 'required=""']) }}
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mt-4 pt-2 text-sm-end d-none d-sm-block">
                                            <button type="button" name="add" id="add_button"
                                                class="btn btn-info btn-sm text-medium">
                                                <i class="fa fa-plus"></i> Add another staff
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Staff Name <span
                                                class="text-danger">*</span></label>
                                        {{ Form::text('staff_name[]', old('staff_name[]'), ['class="form-control"', 'placeholder="Staff name..."', 'required=""']) }}

                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">NIDA Number <span
                                                class="text-danger">*</span></label>
                                        {{ Form::text('nida_number[]', old('nida_number[]'), ['class="form-control input-mask"', 'placeholder="NIDA number..."', 'data-inputmask' => "'mask': '99999999-99999-99999-99'", 'required=""']) }}
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Staff Email <span
                                                class="text-danger">*</span></label>
                                        {{ Form::email('staff_email[]', old('staff_email[]'), ['class="form-control invinputs"', 'placeholder="Staff email..."', 'required=""']) }}
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Staff Phone <span
                                                class="text-danger">*</span></label>
                                        {{ Form::number('staff_phone[]', old('staff_phone[]'), ['class="form-control invinputs"', 'placeholder="255xxxxxxxxx..."', 'required=""']) }}
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="basicpill-namecard-input">Staff Title <span
                                                class="text-danger">*</span></label>
                                        {{ Form::text('staff_title[]', old('staff_title[]'), ['class= "form-control"', 'placeholder="Write title..."', 'required=""']) }}
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4 pt-2 text-sm-end d-none d-sm-block">
                                        <button type="button" name="add" id="add_button"
                                            class="btn btn-info btn-sm text-medium">
                                            <i class="fa fa-plus"></i> Add another staff
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="field_wrapper"></div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3 text-sm-end d-none d-sm-block">
                                    <a href="{{ route('companies.register-accounts', $company->uuid) }}"
                                        class="btn btn-info text-medium">Previous</a>

                                    <button type="submit" id="btnSubmit" class="btn btn-primary">
                                        Next <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
                                    </button>
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
    <script type="text/javascript">
        $(document).ready(function() {
            //data
            //add more accounts
            $(document).ready(function() {
                let maxField = 5; //Input fields increment limitation
                let wrapper = $('.field_wrapper'); //Input field wrapper
                let fieldHTML =
                    '<div class="row row-div">' +
                    '<div class="col-lg-4">' +
                    '<div class="mb-3">' +
                    '<label>Staff Name <span class="text-danger">*</span></label>' +
                    '<input type="text" name="staff_name[]" class="form-control" placeholder="Write full name..." required />' +
                    '</div></div>';

                fieldHTML += '<div class="col-lg-4 col-md-4">' +
                    '<div class="mb-3">' +
                    '<label>NIDA Number <span class="text-danger">*</span></label>' +
                    '<input type="text" name="nida_number[]" class="form-control input-mask" placeholder="Write NIDA number..." data-inputmask="" required="" />' +
                    '</div></div>';

                fieldHTML += '<div class="col-lg-4 col-md-4">' +
                    '<div class="mb-3">' +
                    '<label>Staff Email <span class="text-danger">*</span></label>' +
                    '<input type="email" name="staff_email[]" class="form-control invinputs" placeholder="Write staff email..." required="" />' +
                    '</div></div>';

                fieldHTML += '<div class="col-lg-4 col-md-4">' +
                    '<div class="mb-3">' +
                    '<label>Staff Phone <span class="text-danger">*</span></label>' +
                    '<input type="text" name="staff_phone[]" class="form-control" placeholder="255xxxxxxxxx..." required="" />' +
                    '</div></div>';

                fieldHTML += '<div class="col-lg-4 col-md-4">' +
                    '<div class="mb-3">' +
                    '<label>Staff Title <span class="text-danger">*</span></label>' +
                    '<input type="text" name="staff_title[]" class="form-control" placeholder="Write title..." required />' +
                    '</div></div>';

                fieldHTML += '<div class="col-lg-4 col-md-4">' +
                    '<div class="mt-4 pt-2 text-sm-end d-none d-sm-block">' +
                    'Do you want to delete this staff information? <input type="checkbox" id="remove_button" />' +
                    '</div></div>';

                fieldHTML += '</div>';

                let x = 1; //Initial field counter is 1
                //Once add button is clicked
                $('#add_button').click(function() {
                    //Check maximum number of input fields
                    if (x < maxField) {
                        x++; //Increment field counter
                        $(wrapper).append(fieldHTML); //Add field html
                    }
                });

                //Once remove button is clicked
                $(wrapper).on('click', '#remove_button', function(e) {
                    e.preventDefault();
                    $(this).closest('.row-div').remove();
                    x--; //Decrement field counter
                });
            });

        });
    </script>
@endsection
