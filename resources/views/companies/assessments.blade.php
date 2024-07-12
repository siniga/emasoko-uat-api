<div class="d-flex justify-content-between py-2 mb-2">
    <div>
        <button type="button" class="btn waves-effect text-medium btn-sm"
            :class="{ 'btn-primary': formData.stage === 'Company_Information', 'btn-outline-primary': formData.stage !='Company_Information' }"
            x-on:click="fetchData('{{ $company->uuid }}'), fetchAssessment('{{ $company->uuid }}', 'Company_Information');">
            Information
        </button>

        <button type="button" class="btn waves-effect text-medium btn-sm"
            :class="{ 'btn-primary': formData.stage === 'Company_Documents', 'btn-outline-primary': formData.stage !='Company_Documents' }"
            x-on:click="fetchData('{{ $company->uuid }}'), fetchAssessment('{{ $company->uuid }}', 'Company_Documents');">
            Documents
        </button>

        <button type="button" class="btn waves-effect text-medium btn-sm"
            :class="{ 'btn-primary': formData.stage === 'Company_Collection_Accounts', 'btn-outline-primary': formData.stage !='Company_Collection_Accounts' }"
            x-on:click="fetchData('{{ $company->uuid }}'), fetchAssessment('{{ $company->uuid }}', 'Company_Collection_Accounts');">
            Collection Account(s)
        </button>

        <button type="button" class="btn waves-effect text-medium btn-sm"
            :class="{ 'btn-primary': formData.stage === 'Company_Authorized_Representative', 'btn-outline-primary': formData.stage !='Company_Authorized_Representative' }"
            x-on:click="fetchData('{{ $company->uuid }}'), fetchAssessment('{{ $company->uuid }}', 'Company_Authorized_Representative');">
            Authorized Representative
        </button>

        <button type="button" class="btn waves-effect text-medium btn-sm"
            :class="{ 'btn-primary': formData.stage === 'Confirm_Assessment', 'btn-outline-primary': formData.stage !='Confirm_Assessment' }"
            x-on:click="confirmation('{{ $company->uuid }}');">
            Confirmation
        </button>
    </div>

    <div>
        <button type="button" class="btn btn-outline-danger text-medium btn-sm" x-on:click="sidebarOpen = false"
            x-transition>
            <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>

<template x-if="eModal=='Company_Information'">
    <div class="d-flex flex-column justify-content-start">
        <!--form message => success or danger -->
        <div class="message_wrp"></div>

        <table class="table table-stripped table-bordered mb-2">
            <tr>
                <td colspan="4" class="font-weight-600">
                    Service Provider Information
                </td>
            </tr>
            <tr>
                <th width=20%>Business Name</td>
                <td width=30%>
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
                <td colspan="3">{{ $company->number_of_staff }}</td>
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

        <form action="#" id="companyForm" @submit.prevent="submitInformationForm();" autocomplete="off">
            <input type="hidden" name="uuid" x-model="formData.uuid">
            <input type="hidden" name="stage" x-model="formData.stage">

            <div class="row">
                <div class="col-lg-12">
                    <div class="mb-1">
                        <label>Assessment Criteria <span class="text-danger">*</span></label>
                        <p>
                            <input type="radio" value="approved" x-model="formData.criteria" name="criteria">
                            <label> Approve</label><br />

                            <input type="radio" value="rejected" x-model="formData.criteria" name="criteria">
                            <label> Reject</label>
                        </p>
                        <span class="formError"></span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="mb-2">
                        <label>Remarks </label>
                        <textarea name="remarks" id="remarks" class="form-control" placeholder="Write remarks here..." rows="3"
                            x-model="formData.remarks"></textarea>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="mb-2 text-sm-end d-none d-sm-block">
                        <button type='button' class="btn btn-danger text-medium"
                            x-on:click="sidebarOpen = false">Cancel</button>

                        <button type="submit" name="submit" class="btn btn-primary" x-text="buttonSubmit"
                            :disabled="formLoading"></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<template x-if="eModal=='Company_Documents'">
    <div class="d-flex flex-column justify-content-start">
        <!--form message => success or danger -->
        <div class="message_wrp"></div>

        @if ($company->partnerDocuments->isNotEmpty())
            <table class="table align-middle table-bordered mb-4">
                <thead>
                    <tr>
                        <th width="3%">#</th>
                        <th width="40%">Name</th>
                        <th width="40%">Attachment</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($company->partnerDocuments as $val)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $val->name }}</td>
                            <td>
                                @if ($val->attachment != null)
                                    <a href="{{ asset('assets/uploads/docs/' . $val->attachment) }}">
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

        <form action="#" id="companyForm" @submit.prevent="submitInformationForm();" autocomplete="off">
            <input type="hidden" name="uuid" x-model="formData.uuid">
            <input type="hidden" name="stage" x-model="formData.stage">

            <div class="row">
                <div class="col-lg-12">
                    <div class="mb-1">
                        <label>Assessment Criteria <span class="text-danger">*</span></label>
                        <p>
                            <input type="radio" value="approved" x-model="formData.criteria" name="criteria">
                            <label> Approve</label><br />

                            <input type="radio" value="rejected" x-model="formData.criteria" name="criteria">
                            <label> Reject</label>
                        </p>
                        <span class="formError"></span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="mb-2">
                        <label>Remarks </label>
                        <textarea name="remarks" id="remarks" class="form-control" placeholder="Write remarks here..." rows="3"
                            x-model="formData.remarks"></textarea>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="mb-2 text-sm-end d-none d-sm-block">
                        <button type='button' class="btn btn-danger text-medium"
                            x-on:click="sidebarOpen = false">Cancel</button>

                        <button type="submit" name="submit" class="btn btn-primary" x-text="buttonSubmit"
                            :disabled="formLoading"></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<template x-if="eModal=='Company_Collection_Accounts'">
    <div class="d-flex flex-column justify-content-start">
        <!--form message => success or danger -->
        <div class="message_wrp"></div>

        @if ($company->partnerAccounts->isNotEmpty())
            <table class="table table-bordered mb-4">
                <thead>
                    <tr>
                        <th width="3%">#</th>
                        <th width="20%">Account Type</th>
                        <th width="20%">Bank/MNO Name</th>
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

        <form action="#" id="companyForm" @submit.prevent="submitInformationForm();" autocomplete="off">
            <input type="hidden" name="uuid" x-model="formData.uuid">
            <input type="hidden" name="stage" x-model="formData.stage">

            <div class="row">
                <div class="col-lg-12">
                    <div class="mb-1">
                        <label>Assessment Criteria <span class="text-danger">*</span></label>
                        <p>
                            <input type="radio" value="approved" x-model="formData.criteria" name="criteria">
                            <label> Approve</label><br />

                            <input type="radio" value="rejected" x-model="formData.criteria" name="criteria">
                            <label> Reject</label>
                        </p>
                        <span class="formError"></span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="mb-2">
                        <label>Remarks </label>
                        <textarea name="remarks" id="remarks" class="form-control" placeholder="Write remarks here..." rows="3"
                            x-model="formData.remarks"></textarea>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="mb-2 text-sm-end d-none d-sm-block">
                        <button type='button' class="btn btn-danger text-medium"
                            x-on:click="sidebarOpen = false">Cancel</button>

                        <button type="submit" name="submit" class="btn btn-primary" x-text="buttonSubmit"
                            :disabled="formLoading"></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<template x-if="eModal=='Company_Authorized_Representative'">
    <div class="d-flex flex-column justify-content-start">
        <!--form message => success or danger -->
        <div class="message_wrp"></div>

        @if ($company->partnerStaff->isNotEmpty())
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th width="3%">#</th>
                        <th width="35%">Staff</th>
                        <th width="25%">Email</th>
                        <th width="20%">Phone</th>
                        <th width="16%">Title</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($company->partnerStaff as $val)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $val->name ?? '' }}<br />
                                {{ $val->NIDA_number ?? '' }}
                            </td>
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

        <form action="#" id="companyForm" @submit.prevent="submitInformationForm();" autocomplete="off">
            <input type="hidden" name="uuid" x-model="formData.uuid">
            <input type="hidden" name="stage" x-model="formData.stage">

            <div class="row">
                <div class="col-lg-12">
                    <div class="mb-1">
                        <label>Assessment Criteria <span class="text-danger">*</span></label>
                        <p>
                            <input type="radio" value="approved" x-model="formData.criteria" name="criteria">
                            <label> Approve</label><br />

                            <input type="radio" value="rejected" x-model="formData.criteria" name="criteria">
                            <label> Reject</label>
                        </p>
                        <span class="formError"></span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="mb-2">
                        <label>Remarks </label>
                        <textarea name="remarks" id="remarks" class="form-control" placeholder="Write remarks here..." rows="3"
                            x-model="formData.remarks"></textarea>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="mb-2 text-sm-end d-none d-sm-block">
                        <button type='button' class="btn btn-danger text-medium"
                            x-on:click="sidebarOpen = false">Cancel</button>

                        <button type="submit" name="submit" class="btn btn-primary" x-text="buttonSubmit"
                            :disabled="formLoading"></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<template x-if="eModal=='Confirm_Assessment'">
    <div class="d-flex flex-column justify-content-start">
        <!--form message => success or danger -->
        <div class="message_wrp"></div>

        <table class="table table-nowrap align-middle table-bordered mb-2">
            <thead>
                <tr>
                    <th width="3%">#</th>
                    <th width="60%">Assessment Criteria</th>
                    <th width="30%">Status</th>
                </tr>
            </thead>

            <tbody>
                <template x-for="(stage, index) in stages" :key="index">
                    <tr>
                        <td x-text="stage.sequence"></td>
                        <td x-text="stage.label"></td>
                        <td>
                            <template x-if="stage.status=='pending'">
                                <span class="badge bg-warning">Pending</span>
                            </template>

                            <template x-if="stage.status=='rejected'">
                                <span class="badge bg-danger">Rejected</span>
                            </template>

                            <template x-if="stage.status=='approved'">
                                <span class="badge bg-success">Approved</span>
                            </template>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>

        <template x-if="status=2">
            <form action="#" id="companyForm" @submit.prevent="confirmationForm();" autocomplete="off">
                <input type="hidden" name="uuid" x-model="formData.uuid">

                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-2 text-sm-end d-none d-sm-block">
                            <button type='button' class="btn btn-danger text-medium"
                                x-on:click="sidebarOpen = false">Cancel</button>

                            <button type="submit" name="confirm" class="btn btn-success" x-text="buttonConfirm"
                                :disabled="formLoading"></button>
                        </div>
                    </div>
                </div>
            </form>
        </template>

    </div>
</template>
