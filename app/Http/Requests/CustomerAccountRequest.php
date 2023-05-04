<?php

namespace App\Http\Requests;

use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class CustomerAccountRequest extends FormRequest
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
            'account_type_id' => 'required',
            'account_id' => 'required',
            'account_name' => 'required_if:account_type_id,==,1',
            'account_number' => 'required_if:account_type_id,==,1',
            'phone' => ['required_if:account_type_id,==,1', new PhoneNumber()],
            'account_number_phone' => ['required_if:account_type_id,==,2', new PhoneNumber()],
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
            'account_type_id.required' => 'Payment channel required',
            'account_id.required' => 'Bank/MNO name required',
            'account_name.required_if' => 'Name of account holder required',
            'account_number.required_if' => 'Account number required',
            'phone.required_if' => 'Phone connected to bank required',
            'account_number_phone.required_if' => 'MNO Phone required',
        ];
    }
}
