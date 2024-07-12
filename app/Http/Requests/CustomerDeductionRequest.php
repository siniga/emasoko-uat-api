<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerDeductionRequest extends FormRequest
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
            'loan_amount' => 'required',
            'debit_type' => 'required',
            'periodicity' => 'required',
            'number_of_installment' => 'required',
            'start_date' => 'required',
            'deduction_end' => 'required',
            'installment_amount' => 'required_if:debit_type,==,FIXED',
            'min_installment_amount' => 'required_if:debit_type,==,DYNAMIC',
            'max_installment_amount' => 'required_if:debit_type,==,DYNAMIC',
            'end_date' => 'required_if:deduction_end,==,specificDate',
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
            'loan_amount.required' => 'Loan amount required',
            'debit_type.required' => 'Debit type required',
            'installment_amount.required_if' => 'Installment amount required',
            'min_installment_amount.required_if' => 'Minimum installment amount required',
            'max_installment_amountrequired_if' => 'Maximum installment amount required',
            'periodicity.required' => 'Periodicity required',
            'number_of_installment.required' => 'Number of installment required',
            'start_date.required' => 'Start date required',
            'deduction_end.required' => 'Deduction end required',
            'end_date.required_if' => 'End date required',
        ];
    }
}
