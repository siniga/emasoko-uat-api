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
            Companies
        @endslot
    @endcomponent

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

                            <div class="stepwizard-step" @click="eModal='step2'">
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
                        <div>
                            <form>
                                <section data-step="0">
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
                                                <input type="text" name="business_name" placeholder="Write business name..." class="form-control" required />
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label>Business Name <span class="text-danger">*</span></label>
                                                <input type="text" name="business_name" placeholder="Write business name..." class="form-control" required />
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label>Business Name <span class="text-danger">*</span></label>
                                                <input type="text" name="business_name" placeholder="Write business name..." class="form-control" required />
                                            </div>
                                        </div>

                                    </div>

                                </section>
                            </form>
                        </div>
                    </template>

                    <template x-if="eModal === 'step2'">
                        <div>Step 2</div>
                    </template>

                    <template x-if="eModal === 'step3'">
                        <div>Step 3</div>
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
                eModal: "step1",



                //form data
            }
        }
    </script>
@endsection
