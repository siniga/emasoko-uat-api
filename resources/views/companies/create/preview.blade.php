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

    <div class="row" x-data="initData()">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
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
                                <a href="#step-2" type="button" class="btn btn-success btn-circle text-white"
                                    disabled="disabled">4</a>
                                <span class="text-success">Authorized Representative</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-5" type="button" class="btn btn-success btn-circle text-white"
                                    disabled="disabled">5</a>
                                <span class="text-success">Credentials</span>
                            </div>
                        </div>
                    </div>


                    <form @submit.prevent="">
                        <input type="hidden" name="id" x-model="formData.id">
                        <input type="hidden" name="uuid" x-model="formData.uuid">

                        <section>
                            <div class="row">
                                <div class="col-lg-12">
                                    <table class="table table-stripped table-bordered">
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

                                            <th>Contacts</th>
                                            <td>
                                                Email: {{ $company->email }}<br />
                                                Phone: {{ $company->phone }}
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
                            </div>

                            <div class="row">
                                <div class="col-lg-12">
                                    @if ($company->partnerDocuments->isNotEmpty())
                                        <table class="table table-nowrap align-middle table-bordered">
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
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12">
                                    @if ($company->partnerAccounts->isNotEmpty())
                                        <table class="table table-nowrap align-middle table-bordered">
                                            <thead>
                                                <tr>
                                                    <td colspan="4" class="font-weight-600 text-uppercase">
                                                        Service Provider Collection Accounts
                                                    </td>
                                                </tr>

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
                                        <div class="alert alert-danger">No any account at the moment!</div>
                                    @endif
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12">
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

                            <div class="row">
                                <div class="col-lg-12">
                                    <table class="table table-stripped table-bordered">
                                        <tr>
                                            <td colspan="4" class="font-weight-600 text-uppercase">
                                                Service Provider Credentials
                                            </td>
                                        </tr>

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
                            </div>

                            <div id="message_wrp"></div>

                            <template x-if="complete==0">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3 text-sm-end d-none d-sm-block">
                                            <a href="{{ route('companies.cancel-registration', $company->uuid) }}"
                                                class="btn btn-danger cancel text-medium">Cancel</a>

                                            <a href="{{ route('companies.registration', $company->uuid) }}"
                                                class="btn btn-info text-medium">Edit</a>

                                            <button type="submit" name="save" class="btn btn-secondary"
                                                x-text="buttonSave" :disabled="saveLoading" x-on:click="savingForm();">
                                            </button>

                                            <button type="submit" name="submit" class="btn btn-success"
                                                x-text="buttonSubmit" :disabled="submitLoading"
                                                x-on:click="submitForm();"></button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </section>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->
@endsection

@section('script')
    <script type="text/javascript">
        //initiate data
        function initData() {
            return {
                formData: {
                    id: "{{ $company->id }}",
                    uuid: "{{ $company->uuid }}",
                },
                complete: "{{ $company->complete }}",
                formMessage: "",

                saveLoading: false,
                buttonSave: "Save",

                submitLoading: false,
                buttonSubmit: "Submit",

                //submit form
                submitForm() {
                    this.formMessage = "";
                    this.submitLoading = false;
                    this.buttonSubmit = "Submitting...";

                    fetch("/companies/confirm-registration", {
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
                            //change complete status
                            this.complete = 1;

                            //display message
                            $('#message_wrp').html(
                                "<div class='alert alert-success rounded-sm'>Service provider registered, Waiting for assessment and approval process.</div>"
                            );
                        }).catch(() => {
                            this.formMessage = "Something went wrong.";
                        }).finally(() => {
                            this.submitLoading = false;
                            this.buttonSubmit = "Submit";
                        });
                },

                //saving Form
                savingForm() {
                    this.formMessage = "";
                    this.saveLoading = false;
                    this.buttonSave = "Saving...";

                    fetch("/companies/saving-registration", {
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
                            //change complete status
                            this.complete = 0;

                            //display message
                            $('#message_wrp').html(
                                "<div class='alert alert-success rounded-sm'>Service provider information saved.</div>"
                            );

                            //set timer
                            setTimeout(function() {
                                window.location.href = "/companies/staff/" + response.uuid;
                            }, 1000);
                        }).catch(() => {
                            this.formMessage = "Something went wrong.";
                        }).finally(() => {
                            this.saveLoading = false;
                            this.buttonSave = "Save";
                        });
                }
            }
        }
    </script>
@endsection
