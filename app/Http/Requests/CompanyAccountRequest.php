<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_type_id.*' => 'required',
            'account_id.*' => 'required',
            'account_name.*' => 'required',
            'account_number.*' => 'required',
            'customer_code.*' => 'required',
            'corporate_limit.*' => 'required',
            'currency_id.*' => 'required',
        ];
    }

    /**
     * Custom message for validation
     *
     * @return array
     */
    public function messages()
    {
        return [
            'account_type_id.*.required' => 'Account type required',
            'account_id.*.required' => 'Bank/MNO name required',
            'account_name.*.required' => 'Account name required',
            'account_number.*.required' => 'Account number required',
            'customer_code.*.required' => 'Customer code required',
            'corporate_limit.*.required' => 'Corporate limit required',
            'currency_id.*.required' => 'Currency required',
        ];
    }
}
