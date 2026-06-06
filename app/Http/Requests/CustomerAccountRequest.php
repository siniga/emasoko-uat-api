<?php

namespace App\Http\Requests;

use App\Models\Account;
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

    protected function prepareForValidation()
    {
        if (!$this->filled('account_type_id') && $this->filled('account_id')) {
            $accountTypeId = Account::where('id', $this->input('account_id'))
                ->where('active', 1)
                ->value('account_type_id');

            if ($accountTypeId) {
                $this->merge([
                    'account_type_id' => $accountTypeId,
                ]);
            }
        }
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
            'secondary_phone' => [new PhoneNumber()],
            'account_number_phone' => ['required_if:account_type_id,==,2', new PhoneNumber()],
            'nida_number' => ['required', 'min:23','max:23'],
            'address' => 'required',
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
            'phone.required_if' => 'Phone required',
            'account_number_phone.required_if' => 'Phone required',
            'nida_number.required' => 'NIDA number required',
            'address.required' => 'Address required',
        ];
    }
}
