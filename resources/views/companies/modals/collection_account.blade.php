<!-- Create or update Contract Price -->
<div id="rejectModal" class="modal fade" tabindex="-1" aria-labelledby="rejectModal" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-uppercase font-weight-600"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!--form message => success or danger -->
                <div id="message_wrp_1"></div>

                <div class="row mb-2">
                    <form @submit.prevent="rejectRegistration();" autocomplete="off">

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>