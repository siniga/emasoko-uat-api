@extends('layouts.master')

@section('title')
    Service Providers
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Service Provider Information</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('companies.lists') }}">Service Providers</a></li>
                        <li class="breadcrumb-item active">Provider Information</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

    <div x-data="initData()">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <!-- success message -->
                        <div id="message_wrp"></div>

                        <div class="d-flex justify-content-between mb-2">
                            <div></div>

                            <div class="text-sm-end d-none d-sm-block">
                                <button title="Assessment Process" class="btn btn-primary btn-sm"
                                    x-on:click="sidebarOpen = true, fetchData('{{ $company->uuid }}'), fetchAssessment('{{ $company->uuid }}', 'Company_Information');"
                                    x-transition>
                                    <i class="fa fa-check"></i> Assessment
                                </button>
                            </div>
                        </div>

                        <table class="table table-stripped table-bordered mb-4">
                            <tr>
                                <td colspan="4" class="font-weight-600 text-uppercase">
                                    Service Provider Information
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
                                <td colspan="3" id="company-status">
                                    <template x-if="status==0">
                                        <span class="badge bg-warning">Registration Process</span>
                                    </template>

                                    <template x-if="status==1">
                                        <span class="badge bg-info">Completed Registration</span>
                                    </template>

                                    <template x-if="status==2">
                                        <span class="badge bg-info">Assessment Process</span>
                                    </template>

                                    <template x-if="status==3">
                                        <span class="badge bg-success">Assessment Approved</span>
                                    </template>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="4">
                                    <span class="font-weight-600 font-size-12">Service Provider Address</span>
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

                        @if ($company->partnerDocuments->isNotEmpty())
                            <table class="table table-nowrap align-middle table-bordered mb-4">
                                <thead>
                                    <tr>
                                        <td colspan="3" class="font-weight-600 text-uppercase">
                                            Service Provider Documents
                                        </td>
                                    </tr>

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
                                                        href="{{ asset('assets/uploads/docs/' . $val->attachment) }}">
                                                        {{ $val->attachment }}
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-danger">No any document at the moment!</div>
                        @endif

                        @if ($company->partnerAccounts->isNotEmpty())
                            <table class="table table-nowrap align-middle table-bordered mb-4">
                                <thead>
                                    <tr>
                                        <td colspan="4" class="font-weight-600 text-uppercase">
                                            Service Provider Collection Accounts
                                        </td>
                                    </tr>

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
                            <div class="alert alert-danger">No any collection account at the moment!</div>
                        @endif

                        @if ($company->partnerStaff->isNotEmpty())
                            <table class="table table-nowrap align-middle table-bordered">
                                <thead>
                                    <tr>
                                        <td colspan="4" class="font-weight-600 text-uppercase">
                                            Authorized Representative
                                        </td>
                                    </tr>

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
                            <div class="alert alert-danger">No any staff at the moment!</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!--offcanvas wrp -->
        <div id="offcanvas-wrp" x-show="sidebarOpen" x-transition>
            <div id="detail-div"></div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        //initiate data
        function initData() {
            return {
                sidebarOpen: false,
                eModal: "Company_Information",
                status: "{{ $company->status }}",
                stages: [],

                formData: {
                    'uuid': "{{ $company->uuid }}",
                    'stage': "",
                    "criteria": "",
                    'remarks': "",
                },
                formLoading: false,
                formMessage: "",
                buttonSubmit: "Submit",
                buttonConfirm: "Confirm",
                confirm_btn: false,

                //fetch url info
                fetchData(uuid) {
                    fetch("/companies/assessment/" + uuid)
                        .then(response => response.text())
                        .then(data => {
                            $('#detail-div').html(data)
                        });
                },

                //show assessment verdict
                fetchAssessment(uuid, stage) {
                    fetch("/companies/assessment-approval/?uuid=" + uuid + "&stage=" + stage)
                        .then(response => response.json())
                        .then(response => {
                            console.log(response);

                            //change status of the assessment    
                            this.eModal = response.data.stage;
                            this.formData.stage = response.data.stage;
                            this.formData.criteria = response.data.criteria;
                            this.formData.remarks = response.data.remarks;

                        });
                },

                //show assessment verdict
                confirmation(uuid) {
                    this.eModal = "Confirm_Assessment";
                    this.formData.stage = "Confirm_Assessment";

                    fetch("/companies/assessment-stages/?uuid=" + uuid)
                        .then(response => response.json())
                        .then(response => {
                            console.log(response);
                            this.stages = response.data
                        });
                },

                //store information assessment
                submitInformationForm() {
                    this.formMessage = "";
                    this.formLoading = false;
                    this.buttonSubmit = "Submitting...";

                    fetch("/companies/store-assessment", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify(this.formData),
                        }).then(response => response.json())
                        .then(responseJson => {
                            console.log(responseJson.criteria);

                            //display message
                            if (responseJson.criteria == 'approved') {
                                $('.message_wrp').html(
                                    "<div class='alert alert-success rounded-sm'>Service Provider Information approved.</div>"
                                );
                            } else if (responseJson.criteria == 'rejected') {
                                $('.message_wrp').html(
                                    "<div class='alert alert-danger rounded-sm'>Service Provider Information rejected.</div>"
                                );
                            }
                        }).catch(() => {
                            //display message
                            $('.message_wrp').html(
                                "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>"
                            );

                        }).finally(() => {
                            this.formLoading = false;
                            this.buttonSubmit = "Submit";
                        });
                },

                //confirm assessment
                confirmationForm() {
                    this.formMessage = "";
                    this.formLoading = false;
                    this.buttonConfirm = "Confirming...";

                    fetch("/companies/confirm-assessment", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify(this.formData),
                        }).then(response => response.json())
                        .then(responseJson => {
                            console.log(responseJson);
                            this.status = responseJson.status

                            $('.message_wrp').html(
                                "<div class='alert alert-success rounded-sm'>Service Provider assessment complete.</div>"
                            );
                        }).catch(() => {
                            //display message
                            $('.message_wrp').html(
                                "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>"
                            );

                        }).finally(() => {
                            this.formLoading = false;
                            this.buttonConfirm = "Confirm";
                        });
                },
            }
        }
    </script>
@endsection
