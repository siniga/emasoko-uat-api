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
                                    <section data-step="6">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <h5 class="text-capitalize">Company Information

                                                </h5>
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
                                                <h5 class="text-capitalize">Company Accounts</h5>
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
                                                                <th width="20%">Account</th>
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
                                                <h5 class="text-capitalize">Company Staff</h5>
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
                                                <h5 class="text-capitalize">Company Authorized Person</h5>
                                                <hr />
                                            </div>
                                            <!--./col -->
                                        </div>
                                        <!--./row -->

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <table class="table table-stripped table-bordered">
                                                    <tr>
                                                        <th width=25%>Person Name</th>
                                                        <td width=25%>
                                                            <span>{{ $company->partnerAuthorizeUser->user->name ?? '' }}</span>
                                                        </td>

                                                        <th>NIDA Number</th>
                                                        <td>
                                                            <span>{{ $company->partnerAuthorizeUser->user->NIDA_number ?? '' }}</span>
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <th>Email</th>
                                                        <td>{{ $company->partnerAuthorizeUser->user->email ?? '' }}
                                                        </td>

                                                        <th>Phone</th>
                                                        <td>
                                                            {{ $company->partnerAuthorizeUser->user->phone ?? '' }}
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
                                                        <a href="{{ url('company-authorized-person/' . $company->uuid) }}"
                                                            class="btn btn-danger text-medium">Cancel</a>

                                                        <a href="{{ url('company-authorized-person/' . $company->uuid) }}"
                                                            class="btn btn-info text-medium">Previous</a>

                                                        <button type="submit" id="btnSubmit" class="btn btn-success">
                                                            Submit <i
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

    <!-- subscribeModal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="avatar-md mx-auto mb-4">
                            <div class="avatar-title bg-light rounded-circle text-primary h1">
                                <i class="mdi mdi-email-open"></i>
                            </div>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-xl-10">
                                <h4 class="text-success">Congrats !</h4>
                                <p class="text-muted font-size-14 mb-4">Company registration complete, Please wait for
                                    approval!</p>

                                <p class="text-muted font-size-14 mb-4">You can track your application by clicking
                                    <b>Track Application</b> link
                                </p>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end modal -->

    @section('script')
        <script src="{{ URL::asset('/assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>

        <!--jquery validation -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js"></script>

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
                        $("#btnSubmit").prop("disabled", true);

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
                                $("#btnSubmit").prop("disabled", false);

                                // Deactivate Loading Spinner
                                $('.loading-spinner').toggleClass('active');

                                //hide and show sections
                                $('#btn-section').css('display', 'none');
                                $('#output').css('display', '');
                                $("#output").text(data.success_msg);
                                $('#track-app').css('display', '');
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
