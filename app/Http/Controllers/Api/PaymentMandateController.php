<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentMandateController extends Controller
{
    //customers
    public function customer(Request $request)
    {
        if ($request->phone != null) {
            //customer
            $customer = Customer::where(['phone' => $request->phone])->first();

            if ($customer) {
                //customer account
                $customer_accounts = CustomerAccount::where(['phone' => $request->phone])->get();

                //declare customer accounts 
                $arr_customer_accounts = [];

                //check if customer accounts exists
                if ($customer_accounts) {
                    foreach ($customer_accounts as $customer_account) {
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
                        $arr_customer_accounts[] = [
                            'account_type' => $customer_account->accountType->name,
                            'account' => $customer_account->account->name,
                            'account_name' => $customer_account->account_name,
                            'account_number' => $customer_account->account_number,
                            'debit_orders' => $arr_debit_orders
                        ];
                    }
                }

                //array data
                $arr_data = [
                    'phone' => $customer->phone,
                    'NIN' => $customer->id_number,
                    'email' => $customer->email,
                    'address' => $customer->address,
                    'status' => $customer->status,
                    'remarks' => $customer->remarks,
                    'customer_accounts' => $arr_customer_accounts
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

    function customerInfo(Request $request)
    {
        if ($request->phone != null) {
            //customer
            $customer = Customer::where(['phone' => $request->phone])->first();

            if ($customer) {
                //customer account
                $customer_accounts = CustomerAccount::join('debit_orders', 'customer_accounts.id', '=', 'debit_orders.customer_account_id')
                    ->where(['customer_accounts.customer_id' => $customer->id])
                    ->get();

                //declare customer accounts 
                $arr_customer_accounts = [];

                //check if customer accounts exists
                if ($customer_accounts) {
                    foreach ($customer_accounts as $val) {
                        //array data
                        $arr_customer_accounts[] = [
                            'account_type' => $val->accountType->name,
                            'account' => $val->account->name,
                            'account_name' => $val->account_name,
                            'account_number' => $val->account_number,
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
                    'phone' => $customer->phone,
                    'NIN' => $customer->id_number,
                    'email' => $customer->email,
                    'address' => $customer->address,
                    'status' => $customer->status,
                    'remarks' => $customer->remarks,
                    'debit_orders' => $arr_customer_accounts
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

    //update customer terms and condition
    function updateCustomerTerms(Request $request)
    {
        if ($request->phone != null && $request->consent != null) {
            //customer
            $customer = Customer::where(['phone' => $request->phone])->first();

            if ($customer) {
                //update status of customer
                if ($request->consent == 1) {
                    $customer->status = 'registered';
                    $customer->remarks = 'Customer agreed terms and condition';
                } else if ($request->consent == 0) {
                    $customer->status = 'partial';
                    $customer->remarks = 'Customer reject terms and condition';
                }
                $customer->save(); //save

                //customer account
                $customer_accounts = CustomerAccount::where(['phone' => $request->phone])->get();

                //declare customer accounts 
                $arr_customer_accounts = [];

                //check if customer accounts exists
                if ($customer_accounts) {
                    foreach ($customer_accounts as $customer_account) {
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
                        $arr_customer_accounts[] = [
                            'account_type' => $customer_account->accountType->name,
                            'account' => $customer_account->account->name,
                            'account_name' => $customer_account->account_name,
                            'account_number' => $customer_account->account_number,
                            'debit_orders' => $arr_debit_orders
                        ];
                    }
                }

                //array data
                $arr_data = [
                    'phone' => $customer->phone,
                    'NIN' => $customer->id_number,
                    'email' => $customer->email,
                    'address' => $customer->address,
                    'status' => $customer->status,
                    'remarks' => $customer->remarks,
                    'customer_accounts' => $arr_customer_accounts
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

            //return response
            return response()->json($response, 200);
        } else {
            $response = [
                'error' => true,
                'message' => 'Required parameter is missing'
            ];

            return response()->json($response, 404);
        }
    }

    //payment
    function payment()
    {}
}
