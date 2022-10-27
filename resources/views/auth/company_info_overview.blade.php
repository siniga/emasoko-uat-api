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
                                        <a href="#step-2" type="button" class="btn btn-success btn-circle"
                                            disabled="disabled">2</a>
                                        <span>Company Documents</span>
                                    </div>

                                    <div class="stepwizard-step">
                                        <a href="#step-2" type="button" class="btn btn-success btn-circle"
                                            disabled="disabled">3</a>
                                        <span>Collection Accounts</span>
                                    </div>

                                    <div class="stepwizard-step">
                                        <a href="#step-2" type="button" class="btn btn-success btn-circle"
                                            disabled="disabled">4</a>
                                        <span>Authorized Representative</span>
                                    </div>

                                    <div class="stepwizard-step">
                                        <a href="#step-2" type="button" class="btn btn-success btn-circle"
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

                            <form action="#" id="registrationForm" enctype="multipart/form-data" autocomplete="off">
                                <input type="hidden" name="partner_id" value="{{ $company->id }}">
                                <input type="hidden" name="partner_uuid" value="{{ $company->uuid }}">

                                <!-- Company Documents -->
                                <section data-step="6">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <h5 class="text-capitalize">Company Information</h5>
                                            <hr />
                                        </div>
                                        <!--./col -->
                                    </div>
                                    <!--./row -->

                                    <div class="row mt-2 mb-2">
                                        <div class="col-md-12">
                                            <div class="text-center">
                                                <!--./output -->
                                                <div id="output" class="alert alert-success" style="display: none;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!--./row -->

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div id="track-app" style="display: none;">
                                                <a href="{{ route('company.track-registration') }}"
                                                    title="Track Application" class="btn btn-sm btn-outline-primary">
                                                    <span class="text-capitalize">
                                                        <i class="fa fa-plus"></i> Track Application</span>
                                                </a>
                                            </div>

                                            <table class="table table-stripped table-bordered">
                                                <tr>
                                                    <td colspan="4">
                                                        <b>Company Information</b>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th width=25%>Business Name</th>
                                                    <td width=25%>
                                                        <span>{{ $company->business_name }}</span>
                                                    </td>

                                                    <th>Business Category</th>
                                                    <td>
                                                        <span>{{ $company->partnerCategory->name ?? '' }}</span>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>TIN Number</th>
                                                    <td>{{ $company->tin_number }}</td>

                                                    <th>Year of Incorporation</th>
                                                    <td>
                                                        {{ $company->year_of_incorporation }}
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>Number of Staff</th>
                                                    <td>{{ $company->number_of_staff }}</td>

                                                    <th>Contacts</th>
                                                    <td>
                                                        Email: {{ $company->email }}<br />
                                                        Phone: {{ $company->phone }}
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td colspan="4">
                                                        <b>Company Location</b>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>Region</th>
                                                    <td>{{ $company->region->name ?? '' }}</td>

                                                    <th>Physical Address</th>
                                                    <td>
                                                        Email: {{ $company->address }}
                                                    </td>
                                                </tr>

                                            </table>
                                        </div>
                                        <!--./col -->
                                    </div>
                                    <!--./row-->

                                    <div class="row mt-3">
                                        <div class="col-lg-12">
                                            <h5 class="text-capitalize">Company Documents</h5>
                                            <hr />
                                        </div>
                                        <!--./col -->
                                    </div>
                                    <!--./row -->

                                    <div class="row">
                                        <div class="col-lg-12">
                                            @if ($company->partnerDocuments->isNotEmpty())
                                                <table class="table table-nowrap align-middle table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th width="3%">#</th>
                                                            <th width="60%">Name</th>
                                                            <th width="30%">Attachment</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        @foreach ($company->partnerDocuments as $val)
                                                            <tr>
                                                                <td>{{ $loop->iteration }}</td>
                                                                <td>{{ $val->name }}</td>
                                                                <td>
                                                                    @if ($val->attachment != null)
                                                                        <a download=""
                                                                            href="{{ asset('assets/uploads/docs' . $val->attachment) }}">
                                                                            {{ $val->attachment }}
                                                                        </a>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <div class="alert">No any document at the moment!</div>
                                            @endif
                                        </div>
                                        <!--./col -->
                                    </div>
                                    <!--./row -->

                                    <div class="row mt-2">
                                        <div class="col-lg-12">
                                            <h5 class="text-capitalize">Company Collection Accounts</h5>
                                            <hr />
                                        </div>
                                        <!--./col -->
                                    </div>
                                    <!--./row -->

                                    <div class="row">
                                        <div class="col-lg-12">
                                            @if ($company->partnerAccounts->isNotEmpty())
                                                <table class="table table-nowrap align-middle table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th width="3%">#</th>
                                                            <th width="20%">Account Type</th>
                                                            <th width="20%">Bank/Institutions</th>
                                                            <th width="25%">Account Name</th>
                                                            <th width="20%">Account Number</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        @foreach ($company->partnerAccounts as $val)
                                                            <tr>
                                                                <td>{{ $loop->iteration }}</td>
                                                                <td>{{ $val->accountType->name ?? '' }}</td>
                                                                <td>{{ $val->account->name ?? '' }}</td>
                                                                <td>{{ $val->account_name ?? '' }}</td>
                                                                <td>{{ $val->account_number ?? '' }}</td>

                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <div class="alert">No any account at the moment!</div>
                                            @endif
                                        </div>
                                        <!--./col -->
                                    </div>
                                    <!--./row -->

                                    <div class="row mt-2">
                                        <div class="col-lg-12">
                                            <h5 class="text-capitalize">Authorized Representative</h5>
                                            <hr />
                                        </div>
                                        <!--./col -->
                                    </div>
                                    <!--./row -->

                                    <div class="row">
                                        <div class="col-lg-12">
                                            @if ($company->partnerStaff->isNotEmpty())
                                                <table class="table table-nowrap align-middle table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th width="3%">#</th>
                                                            <th width="20%">Staff Name</th>
                                                            <th width="20%">NIDA Number</th>
                                                            <th width="25%">Email</th>
                                                            <th width="20%">Phone</th>
                                                            <th width="20%">Title</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        @foreach ($company->partnerStaff as $val)
                                                            <tr>
                                                                <td>{{ $loop->iteration }}</td>
                                                                <td>{{ $val->name ?? '' }}</td>
                                                                <td>{{ $val->NIDA_number ?? '' }}</td>
                                                                <td>{{ $val->email ?? '' }}</td>
                                                                <td>{{ $val->phone ?? '' }}</td>
                                                                <td>{{ $val->title ?? '' }}</td>

                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <div class="alert">No any staff at the moment!</div>
                                            @endif
                                        </div>
                                        <!--./col -->
                                    </div>
                                    <!--./row -->

                                    <div class="row mt-2">
                                        <div class="col-lg-12">
                                            <h5 class="text-capitalize">Company Credentials</h5>
                                            <hr />
                                        </div>
                                        <!--./col -->
                                    </div>
                                    <!--./row -->

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <table class="table table-stripped table-bordered">
                                                <tr>
                                                    <th width=25%>Company Name</th>
                                                    <td width=25%>
                                                        <span>{{ $company->partnerAuthorizeUser->user->name ?? '' }}</span>
                                                    </td>

                                                    <th>Email/Username</th>
                                                    <td>{{ $company->partnerAuthorizeUser->user->email ?? '' }}
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <!--./col -->
                                    </div>
                                    <!--./row-->

                                    @if ($company->complete == 0)
                                        <div class="row mt-2" id="btn-section" style="display: block;">
                                            <div class="col-md-12">
                                                <div class="mb-3 text-sm-end d-none d-sm-block">
                                                    <a href="{{ url('cancel-company-registration/' . $company->uuid) }}"
                                                        class="btn btn-danger text-medium">Cancel</a>

                                                    <a href="{{ url('register/' . $company->uuid) }}"
                                                        class="btn btn-info text-medium">Edit</a>

                                                    <button type="submit" id="btnSave" name="save"
                                                        class="btn btn-secondary">
                                                        Save <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
                                                    </button>

                                                    <button type="submit" id="btnSubmit" name="submit"
                                                        class="btn btn-success">
                                                        Save & Submit <i
                                                            class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <!--./col -->
                                        </div>
                                        <!--./row -->
                                    @endif
                                </section>
                            </form>
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

    <!-- subscribeModal -->
    <div class="modal fade" id="subscribeModal" tabindex="-1" aria-labelledby="subscribeModalLabel" aria-hidden="true"
        data-keyboard="false" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="avatar-md mx-auto mb-4">
                            <div class="avatar-title bg-light rounded-circle text-success h1">
                                <i class="mdi mdi-check"></i>
                            </div>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-xl-10">
                                <h4 class="text-success">Congratulations</h4>
                                <p class="text-muted font-size-14 mb-4" id="success-msg"></p>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end modal -->
@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            //validate form and submit
            $("#registrationForm").validate({
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
                    //$("#btnSubmit").prop("disabled", true);

                    //post data 
                    $.ajax({
                        type: "POST",
                        url: "{{ url('confirm-company-registration') }}",
                        data: data,
                        processData: false,
                        contentType: false,
                        cache: false,
                        timeout: 800000,
                        success: function(data) {
                            //$("#btnSubmit").prop("disabled", false);

                            // Deactivate Loading Spinner
                            $('.loading-spinner').toggleClass('active');

                            if (data.action == 'save') {
                                //hide and show sections
                                $('#btn-section').css('display', '');
                                $("#success-msg").text(data.success_msg);
                                $('#track-app').css('display', '');
                            } else {
                                //hide and show sections
                                $('#btn-section').css('display', 'none');
                                $("#success-msg").text(data.success_msg);
                                $('#track-app').css('display', '');
                            }
                            $('#subscribeModal').modal('show');
                        },
                        error: function(e) {
                            $("#btnSubmit").prop("disabled", false);
                            $('#output').css('display', '');
                            $("#output").text(e.responseText);
                        }
                    });
                    return false;
                },
            });
        });
    </script>
@endsection
