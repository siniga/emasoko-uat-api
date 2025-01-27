@extends('layouts.master')

<style>
    .table th {
        background-color: #E1F5FE !important;
    }
</style>

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
                                {{-- <a href="{{ route('companies.create-invoice', $company->uuid) }}" title="Send Invoice"
                                    class="btn btn-success btn-sm">
                                    <i class='fa fa-file-pdf'></i> Send Invoice
                                </a> --}}

                                <template x-if="status==100 || status==403 || status==500">
                                    <a href="{{ route('companies.registration', $company->uuid) }}" title="View"
                                        class="btn btn-warning btn-sm">
                                        <i class='fa fa-pen'></i> Update Information
                                    </a>
                                </template>

                                <template x-if="status==0 || status==100 || status==403">
                                    <button title="Reject Registration" class="btn btn-danger btn-sm"
                                        x-on:click="openRejectModal();" x-transition x-cloak>
                                        <i class="fa fa-times"></i> Reject Registration
                                    </button>
                                </template>

                                <template
                                    x-if="status==100 || status==200">
                                    <button x-html="btnApprove" title="Approve Registration" class="btn btn-success btn-sm"
                                        x-on:click="approveRegistration('{{ $company->uuid }}')"></button>
                                </template>

                                <template x-if="status ==201 || status==403 || status==500">
                                    <button x-html="btnStatus" title="Get Status" class="btn btn-outline-success btn-sm"
                                        x-on:click="fetchRegistrationStatus('{{ $company->uuid }}')"></button>
                                </template>
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
                                <th>Corporate Reference</th>
                                <td>{{ $company->reference }}</td>

                                <th>Corporate ShortCode</th>
                                <td>
                                    {{ $company->short_code }}
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
                                <th>Registration Status</th>
                                <td id="company-status">
                                    <template x-if="status==0">
                                        <span class="badge bg-warning text-uppercase">Registration Process</span>
                                    </template>

                                    <template x-if="status==100">
                                        <span class="badge bg-warning text-uppercase">Pending Review</span>
                                    </template>

                                    <template x-if="status==200">
                                        <span class="badge bg-info text-uppercase">Pending Approval</span>
                                    </template>

                                    <template x-if="status==201">
                                        <span class="badge bg-info text-uppercase">Pending Verification</span>
                                    </template>

                                    <template x-if="status==202">
                                        <span class="badge bg-success text-uppercase">Company Verified</span>
                                    </template>

                                    <template x-if="status==403">
                                        <span class="badge bg-danger text-uppercase">Failed</span>
                                    </template>

                                    <template x-if="status==500">
                                        <span class="badge bg-danger text-uppercase">Rejected</span>
                                    </template>
                                </td>

                                <th>Created On</th>
                                <td>{{ date('d,M-Y', strtotime($company->created_at)) }}</td>
                            </tr>

                            <tr>
                                <th width="25%">Remarks</th>
                                <td colspan="3">
                                    <span x-text="remarks"></span>
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
                                        <th width="25%">Attachment</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($company->partnerDocuments as $val)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $val->name }}</td>
                                            <td>
                                                @if ($val->attachment != null)
                                                    {{ $val->attachment }}
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($val->attachment != null)
                                                    <a href="{{ asset('assets/uploads/docs/' . $val->attachment) }}"
                                                        download><i class='bx bx-cloud-download bx-xs'></i></a>
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
                                        <th width="12%">Account Type</th>
                                        <th width="20%">Bank/MNO</th>
                                        <th width="25%">Account Name</th>
                                        <th width="10%">Account No.</th>
                                        <th width="8%">Customer No.</th>
                                        <th width="15%">Corporate Limit</th>
                                        <th width="10%">Currency</th>
                                        <th width="15%">Status</th>
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
                                            <td>{{ $val->customer_code ?? '' }}</td>
                                            <td>{{ number_format($val->corporate_limit) ?? '' }}</td>
                                            <td>{{ $val->currency->short_code ?? '' }}</td>
                                            <td>
                                                @if($val->status == 1)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif    
                                            </td>
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
        {{-- <div id="offcanvas-wrp" x-show="sidebarOpen" x-transition>
            <div id="detail-div"></div>
        </div> --}}
        
        @include('companies.modals.reject_modal')
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
                remarks: "{{ $company->remarks }}",
                stages: [],

                formData: {
                    'uuid': "{{ $company->uuid }}",
                    'action': "",
                    'remarks': "",
                },
                formLoading: false,
                formMessage: "",
                btnApprove: '<i class="fa fa-check"></i> Approve Registration',
                buttonSubmit: "Submit",
                buttonConfirm: "Confirm",
                confirm_btn: false,

                //status
                btnStatus: '<i class="fa fa-check"></i> Get Status',

                //fetch url info
                fetchData(uuid) {
                    fetch("/companies/assessment/" + uuid)
                        .then(response => response.text())
                        .then(data => {
                            $('#detail-div').html(data)
                        });
                },

                //open reject modal
                openRejectModal() {
                    $('#rejectModal').modal('show');
                },

                //approval company registration
                approveRegistration() {
                    this.formLoading = true;
                    this.btnApprove = '<i class="fas fa-spinner fa-spin"></i> Approving Registration....';

                    //format form 
                    this.formData.action = "APPROVE";
                    this.formData.remarks = "Company registration approved";

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

                            $('#message_wrp').html(
                                "<div class='alert alert-success rounded-sm'>" + responseJson.message + "</div>"
                            );
                        }).catch(() => {
                            //display message
                            $('#message_wrp').html(
                                "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>"
                            );

                        }).finally(() => {
                            this.formLoading = false;
                            this.btnApprove = '<i class="fa fa-check"></i> Approve Registration';
                        });
                },

                //reject company registration
                rejectRegistration() {
                    this.formLoading = true;
                    this.btnSubmit = 'Reject Registration....';

                    //format form 
                    this.formData.action = "REJECT";

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
                            this.status = responseJson.status
                            this.remarks = responseJson.remarks

                            $('#message_wrp_1').html(
                                "<div class='alert alert-success rounded-sm'>" + responseJson.message + "</div>"
                            );
                        }).catch(() => {
                            //display message
                            $('#message_wrp').html(
                                "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>"
                            );

                        }).finally(() => {
                            this.formLoading = false;
                            this.btnSubmit = 'Submit';
                        });
                },

                //approval company registration
                fetchRegistrationStatus() {
                    this.formLoading = true;
                    this.btnStatus = '<i class="fas fa-spinner fa-spin"></i> Get Registration status....';

                    //format form 
                    this.formData.action = "APPROVE";
                    this.formData.remarks = "Company registration approved";

                    fetch("/companies/fetch-corporate-status", {
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
                            this.status = responseJson.http_response_code;
                            this.remarks = responseJson.remarks;

                            $('#message_wrp').html(
                                "<div class='alert alert-success rounded-sm'>" + responseJson.success_msg + "</div>"
                            );
                        }).catch(() => {
                            //display message
                            $('#message_wrp').html(
                                "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>"
                            );

                        }).finally(() => {
                            this.formLoading = false;
                            this.btnStatus = '<i class="fa fa-check"></i> Get Status';
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
            }
        }
    </script>
@endsection
