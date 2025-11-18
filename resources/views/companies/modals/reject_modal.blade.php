<!-- Create or update Contract Price -->
<div id="rejectModal" class="modal fade" tabindex="-1" aria-labelledby="rejectModal" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-uppercase font-weight-600">Reject Mandate Registration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!--form message => success or danger -->
                <div id="message_wrp_1"></div>

                <div class="row mb-2">
                    <form @submit.prevent="rejectRegistration();" autocomplete="off">

                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-12">
                                <div class="mb-2">
                                    <label>Reason for rejection</label>
                                    <textarea name="remarks" x-model="formData.remarks" placeholder="Write reason for rejection..." class="form-control" rows="2" required=""></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12 col-md-12">
                                <div class="mb-2 d-flex justify-content-between">
                                    <div>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-outline-danger waves-effect" data-bs-dismiss="modal">
                                            Close
                                        </button>
                                        <button type="submit" name="submit" class="btn btn-primary btn-block"
                                            x-text="buttonSubmit" :disabled="formLoading"></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>