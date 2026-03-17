<?php

namespace App\Http\Requests;

use App\Models\Partner;
use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CompanyDetailRequest extends FormRequest
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
            'tin_number' => 'required',
            'business_name' => 'required',
            'category_id' => 'required',
            'year_of_incorporation' => 'required|numeric',
            'number_of_staff' => 'required|numeric|min:1',
            'country_code' => 'required',
            'company_phone' => ['required', new PhoneNumber()],
            'company_email' => 'required|email|max:255',
            'region_id' => 'required',
            'physical_address' => 'required',
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
            'tin_number.required' => 'TIN number required',
            // 'tin_number.unique' => 'TIN number should be unique',
            'business_name.required' => 'Business name required',
            'category_id.required' => 'Category required',
            'year_of_incorporation.required' => 'Year of incorporation required',
            'year_of_incorporation.numeric' => 'Year of incorporation should be numeric',
            'number_of_staff.required' => 'Number of staff required',
            'number_of_staff.numeric' => 'Number of staff should be numeric',
            'number_of_staff.min' => 'Number of staff must be at least 1',
            'country_code.required' => 'This is required',
            'company_phone.required' => 'Phone number required',
            'company_email.required' => 'Email required',
            'company_email.email' => 'Email valid format required',
            // 'company_email.unique' => 'Email should be unique',
            'region_id.required' => 'Region required',
            'physical_address.required' => 'Physical address required',
        ];
    }
}
