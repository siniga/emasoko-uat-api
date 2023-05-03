@extends('layouts.master')

@section('title')
    Companies
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Register Company</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Companies</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('companies.lists') }}">Companies</a></li>
                        <li class="breadcrumb-item active">Register Company</li>
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

                            <div class="stepwizard-step" @click="eModal='step1'">
                                <a type="button" class="btn btn-secondary btn-circle">1</a>
                                <span class="text-primary">Company Information</span>
                            </div>

                            <div class="stepwizard-step">
                                <a type="button" class="btn btn-default btn-circle" disabled="disabled">2</a>
                                <span>Company Documents</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-default btn-circle"
                                    disabled="disabled">3</a>
                                <span>Collection Accounts</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-default btn-circle"
                                    disabled="disabled">4</a>
                                <span>Authorized Representative</span>
                            </div>

                            <div class="stepwizard-step">
                                <a href="#step-2" type="button" class="btn btn-default btn-circle"
                                    disabled="disabled">5</a>
                                <span>Credentials</span>
                            </div>
                        </div>
                    </div>

                    <template x-if="eModal === 'step1'">
                        <div x-init="initJs()">
                            <form class="form-horizontal" method="POST" @submit.prevent="submitStep1">
                                <section data-step="1">
                                    <!--./row -->
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <h5 class="text-capitalize">Company Information</h5>
                                        </div>
                                        <!--./col -->
                                    </div>

                                    <!--./row -->
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label>Business Name <span class="text-danger">*</span></label>
                                                <input type="text" name="business_name" x-model="formData.business_name"
                                                    placeholder="Write business name..." class="form-control" required />
                                            </div>
                                        </div>

                                        <div class="col-lg-3" x-init="fetchCategories();">
                                            <div class="mb-3">
                                                <label>Category <span class="text-danger">*</span></label>
                                                <select class="form-control select2" name="category_id"
                                                    x-model="formData.category_id" required>
                                                    <option value="">Select</option>
                                                    <template x-for="(category, index) in categories"
                                                        :key="index">
                                                        <option x-bind:value="category.id">
                                                            <span x-text="category.name"></span>
                                                        </option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label>TIN Number <span class="text-danger">*</span></label>
                                                <input type="text" name="tin_number" placeholder="Write tin number..."
                                                    class="form-control input-mask" data-inputmask="'mask': '999-999-999'"
                                                    x-model="formData.tin_number" required />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label>Year of Incorporation <span class="text-danger">*</span></label>
                                                @php
                                                    $year_options = [];
                                                    $current_year = date('Y');
                                                @endphp

                                                <select name="year_of_incorporation"
                                                    x-model="formData.year_of_incorporation" class="form-control select2">
                                                    <option value="">Select</option>
                                                    @foreach (range($current_year, $current_year - 20) as $v)
                                                        <option value="{{ $v }}">{{ $v }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label>Number of Staff <span class="text-danger">*</span></label>
                                                <input type="number" name="number_of_staff"
                                                    x-model="formData.number_of_staff"
                                                    placeholder="Write number of staff..." class="form-control" required />
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label>Company Email <span class="text-danger">*</span></label>
                                                <input type="email" name="company_email"
                                                    x-model="formData.company_email" placeholder="Write company email..."
                                                    class="form-control" required />
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label>Company Website </label>
                                                <input type="text" name="company_website"
                                                    x-model="formData.company_website"
                                                    placeholder="Write company website..." class="form-control" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-lg-6">
                                            <label>Company Phone <span class="text-danger">*</span></label>
                                            <div class="row">
                                                <div class="col-md-3" x-init="fetchCountries();">
                                                    <select class="form-control select2" name="country_code"
                                                        x-model="formData.country_code" required>
                                                        <template x-for="(country, index) in countries"
                                                            :key="index">
                                                            <option x-bind:value="country.code">
                                                                <span x-text="country.flag"></span>
                                                            </option>
                                                        </template>
                                                    </select>
                                                </div>

                                                <div class="col-md-9">
                                                    <input type="number" name="company_phone"
                                                        x-model="formData.company_phone"
                                                        placeholder="Write company phone..." class="form-control"
                                                        required />
                                                </div>
                                            </div>
                                        </div>
                                        <!--./col -->
                                    </div>
                                    <!--./row -->


                                    <div class="row">
                                        <div class="col-lg-12">
                                            <h5 class="text-capitalize">Company Location</h5>
                                        </div>
                                        <!--./col -->
                                    </div>
                                    <!--./row -->

                                    <div class="row">
                                        <div class="col-lg-3" x-init="fetchRegions();">
                                            <div class="mb-3">
                                                <label>Region <span class="text-danger">*</span></label>
                                                <select class="form-control select2" name="region_id"
                                                    x-model="formData.region_id" required>
                                                    <option value="">Select</option>
                                                    <template x-for="(region, index) in regions" :key="index">
                                                        <option x-bind:value="region.id">
                                                            <span x-text="region.name"></span>
                                                        </option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>
                                        <!--./col -->

                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label for="location">Postal Address </label>
                                                <input type="text" name="postal_address"
                                                    x-model="formData.postal_address"
                                                    placeholder="Write postal address..." class="form-control" />
                                            </div>
                                        </div>
                                        <!--./col -->

                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label>Physical Address <span class="text-danger">*</span></label>

                                                <input type="text" name="physical_address"
                                                    x-model="formData.physical_address"
                                                    placeholder="Write physical address..." class="form-control" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-3 text-sm-end d-none d-sm-block">
                                                <a href="{{ route('companies.lists') }}"
                                                    class="btn btn-danger btn-xs text-medium">Cancel</a>
                                                <button type="submit" class="btn btn-primary"
                                                    x-bind:disabled="formLoading" x-text="buttonNext"></button>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </form>
                        </div>
                    </template>

                    <template x-if="eModal === 'step2'">
                        <div x-init="initJs()">
                            <form class="form-horizontal" method="POST" @submit.prevent="submitStep1">
                                <section data-step="2">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <h5 class="text-capitalize">Company Documents</h5>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="mb-3" x-init="fetchDocCategories();">
                                                <div class="table-responsive">
                                                    <table class="table table-nowrap align-middle table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th width="70%">Attachments</th>
                                                                <th width="20%">Action</th>
                                                            </tr>
                                                        </thead>

                                                        <tbody>
                                                            <template x-for="(doc_category, index) in doc_categories"
                                                                :key="index">
                                                                <tr>
                                                                    <td><span x-text="doc_category.name"></span></td>
                                                                    <td>
                                                                        <input type="file" name="doc_category.key"
                                                                            id="formFile" class="form-control"
                                                                            accept="application/pdf" />
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-3 text-sm-end d-none d-sm-block">
                                                <button type="submit" class="btn btn-info"
                                                    x-text="buttonPrevious"></button>

                                                <button type="submit" class="btn btn-primary"
                                                    x-bind:disabled="formLoading" x-text="buttonNext"></button>
                                            </div>
                                        </div>
                                    </div>

                                </section>
                            </form>
                        </div>
                    </template>

                    <template x-if="eModal === 'step3'">
                        <div x-init="initJs()">
                            
                        </div>
                    </template>

                    <template x-if="eModal === 'step4'">
                        <div>Step 4</div>
                    </template>

                </div>
            </div>
        </div>
    </div>
    <!-- end row -->
@endsection

@section('script')
    <script type="text/javascript">
        function initData() {
            return {
                showModal: false,
                eModal: "step2",
                categories: [],
                countries: [],
                doc_categories: [],
                regions: [],

                //form data
                formData: {
                    business_name: "",
                    category_id: "",
                    tin_number: "",
                    year_of_incorporation: "",
                    number_of_staff: "",
                    company_email: "",
                    company_website: "",
                    country_code: "",
                    company_phone: "",
                    region_id: "",
                    postal_address: "",
                    physical_address: "",
                },

                formMessage: "",
                formLoading: false,
                buttonPrevious: "Previous",
                buttonNext: "Next",
                buttonText: "Submit",

                //fetch categories
                fetchCategories() {
                    fetch('/api/categories', {
                            method: "GET",
                            credentials: 'same-origin'
                        })
                        .then(res => res.json())
                        .then(data => {
                            this.categories = data;
                        });
                },

                //document categories
                fetchDocCategories() {
                    fetch('/api/document_categories', {
                            method: "GET",
                            credentials: 'same-origin'
                        })
                        .then(res => res.json())
                        .then(data => {
                            this.doc_categories = data;
                        });
                },

                //countries
                fetchCountries() {
                    fetch('/api/countries', {
                            method: "GET",
                            credentials: 'same-origin'
                        })
                        .then(res => res.json())
                        .then(data => {
                            this.countries = data;
                        });
                },

                //regions
                fetchRegions() {
                    fetch('/api/regions', {
                            method: "GET",
                            credentials: 'same-origin'
                        })
                        .then(res => res.json())
                        .then(data => {
                            this.regions = data;
                        });
                },

                //submit step 1
                submitStep1() {
                    this.formMessage = "";
                    this.formLoading = false;
                    this.buttonText = "Submitting...";

                    console.log("reaches here");

                    //token
                    const csrfToken = document.head.querySelector("[name~=csrf-token][content]").content;

                    fetch("store-detail", {
                        method: "POST",
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: "application/json",
                            "X-CSRF-Token": csrfToken
                        },
                        body: JSON.stringify(this.formData),
                    }).then(response => response.json()).then((response) => {
                        console.log(response);

                        //set step 2
                        this.eModal = "step2";
                    }).catch(() => {
                        //set message
                        this.formMessage = "Something went wrong.";
                    }).finally(() => {
                        this.formLoading = false;
                        this.buttonText = "Submit";
                    });

                },
            }
        }

        //initiate js 
        function initJs() {
            $(".select2").select2();
            // $(".input-mask").inputmask();
        };
    </script>
@endsection
