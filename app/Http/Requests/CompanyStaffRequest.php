<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyStaffRequest extends FormRequest
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
            'staff_name.*' => 'required',
            'nida_number.*' => 'required',
            'staff_email.*' => 'required',
            'staff_phone.*' => 'required',
            'staff_title.*' => 'required',
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
            'staff_name.*.required' => 'Staff name required',
            'nida_number.*.required' => 'NIDA number required',
            'staff_email.*.required' => 'Email address required',
            'staff_phone.*.required' => 'Phone required',
            'staff_title.*.required' => 'Title required',
        ];
    }
}
