    <!-- confirmModal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true"
        data-keyboard="false" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 justify-content-center">
                    <h5 class="text-uppercase">Payment Mandate Created </h5>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="avatar-md mx-auto mb-4">
                            <div class="avatar-title bg-light rounded-circle text-success h1">
                                <i class="mdi mdi-check"></i>
                            </div>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-xl-10">
                                <p class="text-muted font-size-14">
                                    Payment mandate successfully created
                                </p>

                                <p class="text-muted font-size-14">
                                    Waiting for consent from customer.
                                </p>

                                <p class="text-muted font-size-14 mb-4">
                                    Do you want to create new payment mandate?
                                </p>

                                <a href="{{ route('payment-mandate.create-account') }}" class="btn btn-success">Yes</a> &nbsp;
                                <a href="{{ route('payment-mandate.lists') }}" class="btn btn-danger">No</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end modal -->