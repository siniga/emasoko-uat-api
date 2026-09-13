<div class="collection-account-modal__wide">
    <label class="form-label" for="{{ $prefix }}_account_name">Account Name <span class="text-danger">*</span></label>
    <input id="{{ $prefix }}_account_name" type="text" name="account_name" class="form-control"
        placeholder="Write account name..." required>
</div>
<div>
    <label class="form-label" for="{{ $prefix }}_account_number">Account Number <span class="text-danger">*</span></label>
    <input id="{{ $prefix }}_account_number" type="text" name="account_number" class="form-control"
        placeholder="Account number..." required>
</div>
<div>
    <label class="form-label" for="{{ $prefix }}_customer_code">Customer Number <span class="text-danger">*</span></label>
    <input id="{{ $prefix }}_customer_code" type="text" name="customer_code" class="form-control"
        placeholder="Customer number..." required>
</div>
<div>
    <label class="form-label" for="{{ $prefix }}_corporate_limit">Corporate Limit <span class="text-danger">*</span></label>
    <input id="{{ $prefix }}_corporate_limit" type="text" name="corporate_limit"
        class="form-control number-separator" placeholder="Write corporate limit..." required>
</div>
<div>
    <label class="form-label" for="{{ $prefix }}_currency_id">Currency <span class="text-danger">*</span></label>
    <select id="{{ $prefix }}_currency_id" name="currency_id" class="form-control select2" required>
        <option value="">Select currency</option>
        @foreach ($currencies as $currency)
            <option value="{{ $currency->id }}">{{ $currency->name }}</option>
        @endforeach
    </select>
</div>
