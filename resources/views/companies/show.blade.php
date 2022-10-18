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

                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <h5 class="text-capitalize">Company Information</h5>
                        </div>

                        <div class="text-sm-end d-none d-sm-block">
                            <a href="#" title="Company Assessment" data-bs-toggle="modal"
                                data-bs-target=".bs-example-modal-lg" class="btn btn-outline-primary text-medium btn-sm"
                                company-id="{{ $company->id }}">
                                <i class="fa fa-check"></i> Company Assessment
                            </a>&nbsp;

                            @if ($company_info_approval->status == 'approved')
                                <button class="btn btn-success btn-sm">Pass <i class="fa fa-check"></i></button>
                            @endif
                        </div>
                    </div>

                    <table class="table table-stripped table-bordered mb-4">
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
                                @if ($company->status == 0)
                                    <span class="badge bg-warning">Registration Process</span>
                                @elseif ($company->status == 1)
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
                                <span class="font-weight-600 font-size-12">Company Address</span>
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

                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <h5 class="text-capitalize">Company Documents</h5>
                        </div>

                        <div class="text-sm-end d-none d-sm-block">
                            @if ($company_docs_approval->status == 'approved')
                                <button class="btn btn-success btn-sm">Pass <i class="fa fa-check"></i></button>
                            @endif
                        </div>
                    </div>

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

                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <h5 class="text-capitalize">Company Collection Accounts</h5>
                        </div>

                        <div class="text-sm-end d-none d-sm-block">
                            @if ($company_accounts_approval->status == 'approved')
                                <button class="btn btn-success btn-sm">Pass <i class="fa fa-check"></i></button>
                            @endif
                        </div>
                    </div>

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

                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <h5 class="text-capitalize">Company Authorized Representative</h5>
                        </div>

                        <div class="text-sm-end d-none d-sm-block">
                            @if ($company_staff_approval->status == 'approved')
                                <button class="btn btn-success btn-sm">Pass <i class="fa fa-check"></i></button>
                            @endif
                        </div>
                    </div>

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

    <!--  Extra Large modal example -->
    <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myExtraLargeModalLabel">Company Assessment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div
                        class="d-flex flex-column justify-content-start flex-md-row justify-content-md-between p-2 border-bottom">
                        <div class="align-self-center">
                            <button type="button" id="assess-company-information"
                                class="btn @if ($company_info_approval->status == 'approved') btn-success @else btn-outline-dark @endif waves-effect text-medium btn-sm"
                                company-id="{{ $company->id }}"> Company
                                Information
                            </button>

                            <button type="button" id="company-documents"
                                class="btn @if ($company_docs_approval->status == 'approved') btn-success @else btn-outline-dark @endif waves-effect text-medium btn-sm"
                                company-id="{{ $company->id }}">Company Documents
                            </button>

                            <button type="button" id="collection-accounts"
                                class="btn @if ($company_accounts_approval->status == 'approved') btn-success @else btn-outline-dark @endif waves-effect text-medium btn-sm"
                                company-id="{{ $company->id }}">Collection Accounts
                            </button>

                            <button type="button" id="authorized-representative"
                                class="btn @if ($company_staff_approval->status == 'approved') btn-success @else btn-outline-dark @endif waves-effect text-medium btn-sm"
                                company-id="{{ $company->id }}">Authorized Representative
                            </button>

                            <button type="button" id="confirm-assessment"
                                class="btn @if ($company->status == 3) btn-success @else btn-outline-dark @endif waves-effect text-medium btn-sm"
                                company-id="{{ $company->id }}">Confirmation
                            </button>
                        </div>
                    </div>
                    <!--./buttons -->

                    <div class="mt-2" id="modal-contents"></div>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            //company information assessment without click

            //company information assessment
            $('#assess-company-information').on("click", function(e) {
                e.preventDefault();

                //company-id
                var companyId = $(this).attr('company-id');

                //ajax request
                var jqXHR = $.ajax({
                    type: "GET",
                    url: "/companies/" + companyId + "/assessment/?page=companyInformation",
                }).done(function(data) {
                    $('#modal-contents').html(data);
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('Failed to update progress');
                    if (console && console.log) {
                        console.log("Loading Ajax: " + textStatus + ", " + errorThrown);
                    }
                });
            });

            //company documents
            $('#company-documents').on("click", function(e) {
                e.preventDefault();

                //company-id
                var companyId = $(this).attr('company-id');
                //ajax request
                var jqXHR = $.ajax({
                    type: "GET",
                    url: "/companies/" + companyId + "/assessment/?page=companyDocuments",
                }).done(function(data) {
                    $('#modal-contents').html(data);
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('Failed to update progress');
                    if (console && console.log) {
                        console.log("Loading Ajax: " + textStatus + ", " + errorThrown);
                    }
                });

            });

            //collection accounts
            $('#collection-accounts').on("click", function(e) {
                e.preventDefault();

                //company-id
                var companyId = $(this).attr('company-id');
                //ajax request
                var jqXHR = $.ajax({
                    type: "GET",
                    url: "/companies/" + companyId + "/assessment/?page=collectionAccount",
                }).done(function(data) {
                    $('#modal-contents').html(data);
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('Failed to update progress');
                    if (console && console.log) {
                        console.log("Loading Ajax: " + textStatus + ", " + errorThrown);
                    }
                });
            });

            //authorized personnels
            $('#authorized-representative').on("click", function(e) {
                e.preventDefault();

                //company-id
                var companyId = $(this).attr('company-id');
                //ajax request
                var jqXHR = $.ajax({
                    type: "GET",
                    url: "/companies/" + companyId + "/assessment/?page=authorizedRespesentative",
                }).done(function(data) {
                    $('#modal-contents').html(data);
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('Failed to update progress');
                    if (console && console.log) {
                        console.log("Loading Ajax: " + textStatus + ", " + errorThrown);
                    }
                });
            });

            //confirmation
            $('#confirm-assessment').on("click", function(e) {
                e.preventDefault();

                //company-id
                var companyId = $(this).attr('company-id');
                //ajax request
                var jqXHR = $.ajax({
                    type: "GET",
                    url: "/companies/" + companyId + "/assessment/?page=ConfirmAssessment",
                }).done(function(data) {
                    $('#modal-contents').html(data);
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
