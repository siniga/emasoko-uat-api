@extends('layouts.master-without-nav')

@section('title')
    Request for Information.
@endsection

@section('content')
    <div>
        <div class="container-fluid p-0">
            <div class="row g-0">

                <div class="col-xl-8">
                    <div class="auth-full-bg hero-section bg-ico-hero p-4">
                        <div class="w-120">
                            <div class="bg-overlay bg-primary"></div>
                            <div class="d-flex h-100 flex-column">
                                <div class="my-auto">
                                    <div class="row justify-content-start">
                                        <div class="col-lg-9">
                                            <div class="text-left">
                                                <h1 class="text-white fw-semibold mb-3 hero-title">
                                                    Aggregate, manage and monitor your <span
                                                        class="font-weight-600 text-warning px-1">debit</span> <span
                                                        class="font-weight-600 text-success px-1">mandate.</span>
                                                </h1>
                                                <p class="font-size-18 text-white">
                                                    Enabling a seamless debit mandate ecosystem.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end col -->


                <div class="col-xl-4">
                    <div class="auth-full-page-content p-md-5 p-4">
                        <div class="w-100">

                            <div class="d-flex flex-column h-100">
                                <div class="mb-4 mb-md-5">
                                    <a href="{{ route('front-page') }}" class="d-block font-size-24 font-weight-600">
                                        eMakato
                                    </a>
                                </div>
                                <div class="">
                                    <div>
                                        <h5 class="text-muted font-size-14">
                                            Reach out to us about your payment mandate.
                                        </h5>
                                    </div>

                                    <div class="mt-4" x-data="initData()">
                                        <div x-html="formMessage"></div>

                                        <form class="form-horizontal" method="POST" @submit.prevent="submitForm">
                                            <section data-step="0">
                                                <!-- row -->
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="mb-3">
                                                            <label>Email address <span class="text-danger">*</span></label>
                                                            <input type="email" name="email" x-model="formData.email"
                                                                placeholder="Write email..." class="form-control"
                                                                required />
                                                            <div class="invalid-feedback">
                                                                Email address required
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- row -->
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="mb-3">
                                                            <label>Phone <span class="text-danger">*</span></label>
                                                            <input type="text" name="phone" x-model="formData.phone"
                                                                placeholder="Write phone..." class="form-control"
                                                                required />
                                                            <div class="invalid-feedback">
                                                                Phone required
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- row -->
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="mb-3">
                                                            <label>Subject <span class="text-danger">*</span></label>
                                                            <input type="text" name="subject" x-model="formData.subject"
                                                                placeholder="Write subject..." class="form-control"
                                                                required />
                                                            <div class="invalid-feedback">
                                                                Subject required
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- row -->
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="mb-3">
                                                            <label>Message <span class="text-danger">*</span></label>
                                                            <textarea name="message" x-model="formData.message" placeholder="Write message ..." class="form-control" rows="3"
                                                                required></textarea>
                                                            <div class="invalid-feedback">
                                                                Message required
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="text-sm-end d-none d-sm-block">
                                                            <button type="reset"
                                                                class="btn btn-danger text-medium ">Reset</button>
                                                            <button type="submit" class="btn btn-primary"
                                                                x-bind:disabled="formLoading" x-text="buttonText"></button>
                                                        </div>
                                                    </div>
                                                    <!--./col -->
                                                </div>
                                                <!--./row -->
                                            </section>
                                        </form>
                                    </div>
                                </div>

                                <div class="mt-4 mt-md-5 text-center">
                                    <p class="mb-0">
                                        © Copyright
                                        <script>
                                            document.write(new Date().getFullYear())
                                        </script>All rights reserved.

                                        <br /> A product of <a href="#" class="text-muted">Solocodes</a>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end col -->
            </div>
            <!-- end row -->
        </div>
        <!-- end container-fluid -->
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        function initData() {
            return {
                //form data
                formData: {
                    email: "",
                    phone: "",
                    subject: "",
                    message: "",
                },
                formMessage: "",
                formLoading: false,
                buttonText: "Submit",

                //submit form
                submitForm() {
                    this.formMessage = "";
                    this.formLoading = false;
                    this.buttonText = "Submitting...";

                    //token
                    const csrfToken = document.head.querySelector("[name~=csrf-token][content]").content;

                    fetch("process-information", {
                        method: "POST",
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: "application/json",
                            "X-CSRF-Token": csrfToken
                        },
                        body: JSON.stringify(this.formData),
                    }).then(response => response.json()).then((response) => {
                        this.formData.email = "";
                        this.formData.phone = "";
                        this.formData.subject = "";
                        this.formData.message = "";

                        //set message
                        this.formMessage = response.success_msg;
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
    </script>
@endsection
