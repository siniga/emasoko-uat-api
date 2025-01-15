<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CollectionRequest extends FormRequest
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
            'provider_id' => 'required',
            'created_at' => 'required',
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
            'provider_id.required' => 'Service provider required',
            'created_at.required' => 'Collection date required',
        ];
    }
}
