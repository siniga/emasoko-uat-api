<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerAccountRequest;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\Partner;
use App\Models\PartnerUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class PaymentMandateCreateController extends BasePaymentMandateController
{
    public function create_account($customer_id = null, $debit_order_id = null)
    {
        $title = 'Payment Mandate';

        $user = User::findOrFail(Auth::user()->id);

        if ($user->hasRole('Company_Approver') || $user->hasRole('Company_User')) {
            $partner_user = PartnerUser::whereHas('partner', function ($query) {
                $query->where('approved', '=', 1);
            })->where('user_id', '=', $user->id);

            if ($partner_user->count() == 0) {
                return Redirect::route('payment-mandate.lists')->with('danger', 'Service provider is not activated, Kindly contact eMakato Team.');
            }
        }

        $customer_account = [];
        if ($customer_id != null) {
            $customer_account = CustomerAccount::where(['uuid' => $customer_id])->first();
        }

        $debit_order = [];
        if ($debit_order_id != null) {
            $debit_order = DebitOrder::where(['uuid' => $debit_order_id])->first();
        }

        $account_types = AccountType::all();
        $accounts = Account::where(['active' => 1])->get();

        return view('payment_mandate.create.account_form', compact('title', 'customer_account', 'debit_order', 'account_types', 'accounts'));
    }

    public function store_account(CustomerAccountRequest $request)
    {
        $validated = $request->validated();
        $debit_order_uuid = $request->debit_order_uuid;
        $customer_account_uuid = $request->customer_account_uuid != null ? $request->customer_account_uuid : Str::uuid();

        if ($validated['account_type_id'] == 1) {
            $unique_phone = casting_phone($validated['phone']);
        } elseif ($request->input('account_type_id') == 2) {
            $unique_phone = casting_phone($validated['account_number_phone']);
        }

        $bank_code = null;
        if ($validated['account_type_id'] == 1) {
            $bank_code = substr($validated['account_number'], 0, 3);
        }

        $this->paymentMandatePartyService->upsertCustomerAccount([
            'account_type_id' => $validated['account_type_id'],
            'account_id' => $validated['account_id'],
            'phone' => $unique_phone,
            'secondary_phone' => $validated['secondary_phone'],
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'email' => $request->input('email'),
            'id_number' => $request->input('nida_number'),
            'address' => $request->input('address'),
        ], Auth::id(), $customer_account_uuid);

        return Redirect::route('payment-mandate.create-deduction', [$customer_account_uuid, $debit_order_uuid])->with('success', 'Customer deduction channel saved');
    }

    public function create_deduction($customer_account_uuid, $debit_order_uuid = null)
    {
        $title = 'Payment Mandate';

        $arr_debit_orders = [];
        if ($debit_order_uuid == null) {
            $arr_debit_orders = $this->deductionService->hasActiveDebitOrder($customer_account_uuid);
        }

        $user = User::findOrFail(Auth::user()->id);

        $provider = null;
        if ($user->partners->where('authorize', '=', 1)->count() == 1) {
            $provider = $user->partners[0];
        }

        $customer_account = CustomerAccount::where(['uuid' => $customer_account_uuid])->first();
        $periodicity_options = ['' => '-- Select --'];

        if ($customer_account && $customer_account->account) {
            foreach ($customer_account->account->normalizedAllowedPeriodicities() as $periodicity) {
                $periodicity_options[$periodicity] = ucfirst(strtolower($periodicity));
            }
        }

        $debit_order = [];
        if ($debit_order_uuid != null) {
            $debit_order = DebitOrder::where(['uuid' => $debit_order_uuid])->first();
        }

        $providers = Partner::filterCondition()->get();

        return view('payment_mandate.create.deduction_form', compact('title', 'customer_account', 'debit_order', 'providers', 'provider', 'arr_debit_orders', 'periodicity_options'));
    }

    public function store_deduction(Request $request)
    {
        $loan_amount = floatval(str_replace(',', '', $request->input('loan_amount')));
        $installment_amount = floatval(str_replace(',', '', $request->input('installment_amount')));
        $min_installment_amount = floatval(str_replace(',', '', $request->input('min_installment_amount')));
        $max_installment_amount = floatval(str_replace(',', '', $request->input('max_installment_amount')));

        $this->validate(
            $request,
            [
                'provider_id' => 'required_if:partner_id,==,null',
                'loan_amount' => [
                    'required',
                    function ($attribute, $value, $fail) use ($loan_amount) {
                        if ($loan_amount < 1000) {
                            return $fail('Total amount should be greater than or equal to 1,000');
                        }
                    },
                ],
                'debit_type' => 'required',
                'periodicity' => 'required',
                'number_of_installment' => 'required',
                'start_date' => 'required|date|after:today',
                'installment_amount' => [
                    'required_if:debit_type,==,FIXED',
                    function ($attribute, $value, $fail) use ($installment_amount) {
                        if ($installment_amount < 1000) {
                            return $fail('Installment amount should be greater than or equal to 1,000');
                        }
                    },
                ],
                'min_installment_amount' => 'required_if:debit_type,==,DYNAMIC',
                'max_installment_amount' => [
                    'required_if:debit_type,==,DYNAMIC',
                    function ($attribute, $value, $fail) use ($min_installment_amount, $max_installment_amount) {
                        if ($min_installment_amount > $max_installment_amount) {
                            return $fail('Maximum installment amount should not be less than minimum deduction amount');
                        }
                    },
                ],
            ],
            [
                'provider_id.required_if' => 'Service provider required',
                'loan_amount.required' => 'Total amount required',
                'debit_type.required' => 'Debit type required',
                'installment_amount.required_if' => 'Installment amount required',
                'min_installment_amount.required_if' => 'Minimum installment amount required',
                'max_installment_amountrequired_if' => 'Maximum installment amount required',
                'periodicity.required' => 'Periodicity required',
                'number_of_installment.required' => 'Number of installment required',
                'start_date.required' => 'Deduction start date required',
                'start_date.after' => 'Deduction start date must start from tomorrow',
            ]
        );

        $customerAccount = CustomerAccount::with('account')->findOrFail($request->input('customer_account_id'));
        $account = $customerAccount->account;

        if (!$account || !$account->supportsPeriodicity($request->input('periodicity'))) {
            return Redirect::back()
                ->withErrors(['periodicity' => 'Selected periodicity is not supported for this payment channel.'])
                ->withInput();
        }

        $debit_order = $this->paymentMandateWorkflowService->createDraft($request->all(), Auth::id());

        return Redirect::route('payment-mandate.preview', [$debit_order->uuid])->with('success', 'Customer deduction information saved, OTP PIN sent to customer for Terms and Condition consent.');
    }

    public function preview($debit_uuid)
    {
        $title = 'Payment Mandate';
        $debit_order = DebitOrder::where(['uuid' => $debit_uuid])->firstOrFail();
        $customer_account = $debit_order->customerAccount ?? null;

        return view('payment_mandate.create.preview', compact('title', 'customer_account', 'debit_order'));
    }

    public function cancel($debit_order_uuid)
    {
        $debit_order = DebitOrder::where(['uuid' => $debit_order_uuid])->first();

        Installment::where(['debit_order_id' => $debit_order->id])->delete();
        $debit_order->delete();

        return Redirect::route('payment-mandate.lists')->with('danger', 'Payment mandate application cancelled');
    }

    public function save(Request $request)
    {
        $this->paymentMandateWorkflowService->saveDraft($request->input('uuid'));

        return response()->json(['success' => false, 'success_msg' => 'Debit mandate saved'], 200);
    }

    public function confirm(Request $request)
    {
        return response()->json(
            $this->paymentMandateWorkflowService->submit($request->input('uuid')),
            200
        );
    }
}
