<div id="addCollectionAccountModal" class="modal fade" tabindex="-1"
    aria-labelledby="addCollectionAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered collection-account-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="addCollectionAccountModalLabel">Add Collection Account</h5>
                    <p class="text-muted mb-0">Record a settlement account for {{ $company->business_name }}.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCollectionAccountForm" action="{{ route('companies.store-collection-account') }}"
                method="POST" autocomplete="off">
                @csrf
                <input type="hidden" name="partner_id" value="{{ $company->id }}">
                <input type="hidden" name="partner_uuid" value="{{ $company->uuid }}">
                <div class="modal-body">
                    <div class="account-form-message"></div>
                    <div class="collection-account-modal__grid">
                        <div class="collection-account-modal__wide">
                            <label class="form-label" for="add_account_id">
                                Payment Channel <span class="text-danger">*</span>
                            </label>
                            <select id="add_account_id" name="account_id" class="form-control select2" required>
                                <option value="">Select payment channel</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @include('companies.modals.partials.collection_account_fields', ['prefix' => 'add'])
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary account-submit">Add Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($canReviewRegistration)
    <div id="editCollectionAccountModal" class="modal fade" tabindex="-1"
        aria-labelledby="editCollectionAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered collection-account-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="editCollectionAccountModalLabel">Edit Collection Account</h5>
                        <p class="text-muted mb-0">Bank and channel cannot be changed after the account is created.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editCollectionAccountForm" method="POST" autocomplete="off">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="account-form-message"></div>
                        <div class="collection-account-modal__grid">
                            @include('companies.modals.partials.collection_account_fields', ['prefix' => 'edit'])
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary account-submit">Update Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
