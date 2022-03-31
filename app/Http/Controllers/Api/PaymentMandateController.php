<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use Illuminate\Http\Request;

class PaymentMandateController extends Controller
{
    //customers
    public function customer(Request $request)
    {
        if ($request->phone != null) {

            //customer account
            $customer_account = CustomerAccount::where(['phone' => $request->phone])->first();

            if ($customer_account) {
                //debit orders
                $arr_debit_orders = [];
                if ($customer_account->debitOrders) {
                    foreach ($customer_account->debitOrders as $val) {
                        $arr_debit_orders[] = [
                            'loan_amout' => $val->total_amount,
                            'paid_amout' => $val->paid_amount,
                            'outstanding_amout' => $val->outstanding_amount,
                            'debit_type' => $val->debit_type,
                            'min_installment_amount' => $val->min_installment_amount,
                            'max_installment_amount' => $val->max_installment_amount,
                            'number_of_installment' => $val->installment_number,
                            'periodicity' => $val->periodicity,
                            'start_date' => $val->start_date,
                            'end_date' => $val->end_date,
                            'approved' => $val->approved,
                            'remarks' => $val->remarks,
                        ];
                    }
                }

                //array data
                $arr_data = [
                    'account_type' => $customer_account->accountType->name,
                    'account' => $customer_account->account->name,
                    'account_name' => $customer_account->account_name,
                    'account_number' => $customer_account->account_number,
                    'phone' => $customer_account->phone,
                    'NIN' => $customer_account->id_number,
                    'email' => $customer_account->email,
                    'address' => $customer_account->address,
                    'status' => $customer_account->status,
                    'remarks' => $customer_account->remarks,
                    'debit_orders' => $arr_debit_orders
                ];

                //response
                $response = [
                    'error' => false,
                    'data'    => $arr_data,
                    'message' => 'Data retrieved!',
                ];
            } else {
                //response
                $response = [
                    'error' => false,
                    'message' => 'Customer does not exists!',
                ];
            }

            return response()->json($response, 200);
        } else {
            $response = [
                'error' => true,
                'message' => 'Required parameter is missing'
            ];

            return response()->json($response, 404);
        }
    }
}
