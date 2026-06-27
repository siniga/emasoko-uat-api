<?php

namespace App\Http\Requests\Api\ExternalApp;

use App\Models\Account;
use App\Models\Partner;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;

class CreatePaymentMandateRequest extends FormRequest
{
    protected ?Account $paymentAccount = null;
    protected ?Partner $partner = null;
    protected ?Collection $authorizedPartners = null;

    protected function prepareForValidation()
    {
        $normalized = [];

        if (!$this->filled('payment_channel')) {
            if ($this->filled('channel')) {
                $normalized['payment_channel'] = $this->input('channel');
            } elseif ($this->filled('account_slug')) {
                $normalized['payment_channel'] = $this->input('account_slug');
            } elseif ($this->filled('account_id')) {
                $normalized['payment_channel'] = Account::whereKey($this->input('account_id'))->value('slug');
            }
        }

        if (!$this->filled('NIN') && $this->filled('nin')) {
            $normalized['NIN'] = $this->input('nin');
        }

        if (!$this->filled('NIN') && $this->filled('id_number')) {
            $normalized['NIN'] = $this->input('id_number');
        }

        if (!$this->filled('address')) {
            $normalized['address'] = 'Dar es Salaam';
        }

        if (!$this->filled('total_amount') && $this->filled('loan_amount')) {
            $normalized['total_amount'] = $this->input('loan_amount');
        }

        if (!$this->filled('deduction_start_date') && $this->filled('start_date')) {
            $normalized['deduction_start_date'] = $this->input('start_date');
        }

        if (!$this->filled('account_name') && $this->filled('customer_name')) {
            $normalized['account_name'] = $this->input('customer_name');
        }

        if (!$this->filled('installment_amount') && $this->filled('deduction_amount')) {
            $normalized['installment_amount'] = $this->input('deduction_amount');
        }

        if (!empty($normalized)) {
            $this->merge($normalized);
        }
    }

    public function authorize()
    {
        return $this->authorizedPartners()->isNotEmpty();
    }

    public function rules()
    {
        return [
            'payment_channel' => 'required',
            'corporate_reference' => 'nullable|string',
            'phone' => 'required',
            'NIN' => 'nullable|string',
            'address' => 'nullable|string',
            'total_amount' => [
                'required',
                function ($attribute, $value, $fail) {
                    $amount = floatval(str_replace(',', '', (string) $value));
                    if ($amount < 1000) {
                        return $fail('Total amount should be greater than or equal to 1,000');
                    }
                },
            ],
            'debit_type' => 'required',
            'installment_amount' => [
                'required_if:debit_type,FIXED',
                function ($attribute, $value, $fail) {
                    $amount = floatval(str_replace(',', '', (string) $value));
                    if ($amount < 1000) {
                        return $fail('Installment amount should be greater than or equal to 1,000');
                    }
                },
            ],
            'min_installment_amount' => 'required_if:debit_type,DYNAMIC',
            'max_installment_amount' => [
                'required_if:debit_type,DYNAMIC',
                function ($attribute, $value, $fail) {
                    $minInstallmentAmount = floatval(str_replace(',', '', (string) $this->input('min_installment_amount')));
                    $maxInstallmentAmount = floatval(str_replace(',', '', (string) $value));

                    if ($minInstallmentAmount > $maxInstallmentAmount) {
                        return $fail('Maximum installment amount should not be less than minimum deduction amount');
                    }
                },
            ],
            'periodicity' => 'required',
            'deduction_start_date' => 'required|date|after_or_equal:today',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validatePartner($validator);

            $account = $this->paymentAccount();

            if (!$account) {
                $validator->errors()->add('payment_channel', 'Selected payment channel was not found.');
                return;
            }

            if ((int) $account->account_type_id === 1) {
                if (!$this->filled('account_name')) {
                    $validator->errors()->add('account_name', 'Account name is required for bank payment channels.');
                }

                if (!$this->filled('account_number')) {
                    $validator->errors()->add('account_number', 'Account number is required for bank payment channels.');
                }
            }

            if ($this->filled('periodicity') && !$account->supportsPeriodicity($this->periodicity)) {
                $validator->errors()->add('periodicity', 'Selected periodicity is not supported for this payment channel.');
            }
        });
    }

    protected function validatePartner($validator): void
    {
        $partners = $this->authorizedPartners();
        $corporateReference = $this->input('corporate_reference');

        if ($partners->count() === 1 && !$this->filled('corporate_reference')) {
            $this->partner = $partners->first();
            return;
        }

        if ($partners->count() > 1 && !$this->filled('corporate_reference')) {
            $validator->errors()->add('corporate_reference', 'Corporate reference is required when your user is linked to more than one partner.');
            return;
        }

        if ($this->filled('corporate_reference')) {
            $partner = $partners->firstWhere('reference', $corporateReference);

            if (!$partner) {
                $validator->errors()->add('corporate_reference', 'Selected corporate reference is not linked to your user.');
                return;
            }

            $this->partner = $partner;
        }
    }

    public function partner(): ?Partner
    {
        if ($this->partner) {
            return $this->partner;
        }

        $partners = $this->authorizedPartners();

        if ($partners->count() === 1 && !$this->filled('corporate_reference')) {
            $this->partner = $partners->first();
            return $this->partner;
        }

        if ($this->filled('corporate_reference')) {
            $this->partner = $partners->firstWhere('reference', $this->input('corporate_reference'));
        }

        return $this->partner;
    }

    protected function authorizedPartners(): Collection
    {
        if ($this->authorizedPartners !== null) {
            return $this->authorizedPartners;
        }

        $user = $this->user();

        if (!$user) {
            return $this->authorizedPartners = collect();
        }

        $this->authorizedPartners = $user->partners()
            ->with('partner')
            ->where('authorize', 1)
            ->get()
            ->pluck('partner')
            ->filter()
            ->values();

        return $this->authorizedPartners;
    }

    public function paymentAccount(): ?Account
    {
        if ($this->paymentAccount || !$this->filled('payment_channel')) {
            return $this->paymentAccount;
        }

        $this->paymentAccount = Account::whereIn('account_type_id', [1, 2])
            ->whereRaw('LOWER(slug) = ?', [strtolower((string) $this->payment_channel)])
            ->first();

        return $this->paymentAccount;
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'response_code' => 403,
            'message' => 'Your user is not linked to an authorized partner.',
            'data' => [],
        ], 403));
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'response_code' => 422,
            'message' => 'Validation failed.',
            'data' => [],
            'errors' => $validator->errors(),
        ], 422));
    }
}
