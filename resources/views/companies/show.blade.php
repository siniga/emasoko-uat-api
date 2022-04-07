@extends('layouts.master')

@section('title')
    Companies
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Companies
        @endslot
        @slot('title')
            Company Information
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">

                    @if (session()->get('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div><br />
                    @elseif(session()->get('danger'))
                        <div class="alert alert-danger">
                            {{ session()->get('danger') }}
                        </div><br />
                    @endif

                    <h5 class="text-capitalize">Company Information</h5>
                    <hr />

                    <div class="text-sm-end d-none d-sm-block">
                        <a id="assess-company-information" title="Company Assessment" class="btn btn-info text-medium btn-sm"
                            company-id="{{ $company->id }}">
                            <span class="text-capitalize">
                                <i class="fa fa-check"></i> Company Assessment</span>
                        </a>&nbsp;&nbsp;
                    </div>

                    <table class="table table-stripped table-bordered mb-4">
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

                            <th>Status</th>
                            <td colspan="3">
                                @if ($company->status == 1)
                                    <span class="badge bg-info">Completed Registration</span>
                                @elseif($company->status == 2)
                                    <span class="badge bg-info">Assessment Process</span>
                                @elseif($company->status == 3)
                                    <span class="badge bg-success">Approved</span>
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4">
                                <b>Company Address</b>
                            </td>
                        </tr>

                        <tr>
                            <th>Email</th>
                            <td>{{ $company->email ?? '' }}</td>

                            <th>Phone</th>
                            <td>{{ $company->phone }}</td>
                        </tr>

                        <tr>
                            <th>Postal Address</th>
                            <td>{{ $company->postal_address ?? '' }}</td>

                            <th>Physical Address</th>
                            <td>{{ $company->physical_address }}</td>
                        </tr>
                    </table>

                    <h5 class="text-capitalize">Company Documents</h5>
                    <hr />

                    @if ($company->partnerDocuments->isNotEmpty())
                        <table class="table table-nowrap align-middle table-bordered mb-4">
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

                    <h5 class="text-capitalize">Company Collection Accounts</h5>
                    <hr />

                    @if ($company->partnerAccounts->isNotEmpty())
                        <table class="table table-nowrap align-middle table-bordered mb-4">
                            <thead>
                                <tr>
                                    <th width="3%">#</th>
                                    <th width="20%">Account Type</th>
                                    <th width="20%">Bank/MNO</th>
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
                        <div class="alert">No any collection account at the moment!</div>
                    @endif

                    <h5 class="text-capitalize">Company Authorized Representative</h5>
                    <hr />

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
                <!--./card-body -->
            </div>
            <!--./card-->
        </div>
        <!--./col -->
    </div>
    <!-- end row -->

    <div id="offcanvas-wrp"></div>
    <!--./offcanvas -->
@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            //close offcanvas wrp
            $('#offcanvas-wrp').hide();

            //close canvas
            $(document).on('click', '#close-offcanvas', function(event) {
                $('#offcanvas-wrp').animate({
                    width: "hide"
                });
                //reload page
                location.reload();
            });

            //add rate card
            $(document).on('click', '#assess-company-information', function(event) {
                event.preventDefault();

                $('#offcanvas-wrp').animate({
                    width: "show"
                });

                //company-id
                var companyId = $(this).attr('company-id');

                //ajax request
                var jqXHR = $.ajax({
                    type: "GET",
                    url: "/companies/" + companyId + "/assessment/?page=companyInformation",
                }).done(function(data) {
                    $('#offcanvas-wrp').html(data);

                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('Failed to update progress');
                    if (console && console.log) {
                        console.log("Loading Ajax: " + textStatus + ", " + errorThrown);
                    }
                });
            });

            //company documents
            $(document).on('click', '#company-documents', function(event) {
                event.preventDefault();

                $('#offcanvas-wrp').animate({
                    width: "show"
                });

                //company-id
                var companyId = $(this).attr('company-id');

                //ajax request
                var jqXHR = $.ajax({
                    type: "GET",
                    url: "/companies/" + companyId + "/assessment/?page=companyDocuments",
                }).done(function(data) {
                    $('#offcanvas-wrp').html(data);

                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('Failed to update progress');
                    if (console && console.log) {
                        console.log("Loading Ajax: " + textStatus + ", " + errorThrown);
                    }
                });
            });

            //collection account assessment
            $(document).on('click', '#collection-accounts', function(event) {
                event.preventDefault();

                $('#offcanvas-wrp').animate({
                    width: "show"
                });

                //company-id
                var companyId = $(this).attr('company-id');

                //ajax request
                var jqXHR = $.ajax({
                    type: "GET",
                    url: "/companies/" + companyId + "/assessment/?page=collectionAccount",
                }).done(function(data) {
                    $('#offcanvas-wrp').html(data);

                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('Failed to update progress');
                    if (console && console.log) {
                        console.log("Loading Ajax: " + textStatus + ", " + errorThrown);
                    }
                });
            });

            //collection account assessment
            $(document).on('click', '#authorized-representative', function(event) {
                event.preventDefault();

                $('#offcanvas-wrp').animate({
                    width: "show"
                });

                //company-id
                var companyId = $(this).attr('company-id');

                //ajax request
                var jqXHR = $.ajax({
                    type: "GET",
                    url: "/companies/" + companyId + "/assessment/?page=authorizedRespesentative",
                }).done(function(data) {
                    $('#offcanvas-wrp').html(data);

                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('Failed to update progress');
                    if (console && console.log) {
                        console.log("Loading Ajax: " + textStatus + ", " + errorThrown);
                    }
                });
            });

            //confirm assessment
            $(document).on('click', '#confirm-assessment', function(event) {
                event.preventDefault();

                $('#offcanvas-wrp').animate({
                    width: "show"
                });

                //company-id
                var companyId = $(this).attr('company-id');

                //ajax request
                var jqXHR = $.ajax({
                    type: "GET",
                    url: "/companies/" + companyId + "/assessment/?page=ConfirmAssessment",
                }).done(function(data) {
                    $('#offcanvas-wrp').html(data);

                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('Failed to update progress');
                    if (console && console.log) {
                        console.log("Loading Ajax: " + textStatus + ", " + errorThrown);
                    }
                });
            });

        });
    </script>
@endsection
