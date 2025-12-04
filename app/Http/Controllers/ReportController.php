<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentMandateExport;
use App\Exports\CollectionExport;
use App\Exports\CollectionPayments;
use App\Exports\DebitOrders;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\Partner;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function lists(Request $request)
    {
        $title = "Reports";

        if (isset($_POST['generate'])) {
            if ($request->report_type == "MANDATE") {
                //Query Debit Mandate
                $debit_orders = DebitOrder::filterCondition()
                    ->select(
                        'id',
                        'reference',
                        'partner_id',
                        'customer_account_id',
                        'debit_type',
                        'total_amount',
                        'paid_amount',
                        'outstanding_amount',
                        'current_balance',
                        'installment_amount',
                        'min_installment_amount',
                        'max_installment_amount',
                        'installment_number',
                        'periodicity',
                        'start_date',
                        'end_date',
                        'contract_status',
                        'status',
                        'remarks',
                        'approved',
                        'created_at',
                        'created_by'
                    )
                    ->whereHas('partner', function ($q) {
                        $q->where('partners.approved', 1);
                    });

                //filtering start and end date
                if ($request->has('start_at') && $request->has('end_at')) {
                    //format dates
                    $start_at = Carbon::createFromFormat('Y-m-d', $request->start_at)->startOfDay();
                    $end_at = Carbon::createFromFormat('Y-m-d', $request->end_at)->endOfDay();

                    $debit_orders = $debit_orders->whereBetween('debit_orders.created_at', [$start_at, $end_at]);
                }

                // filtering status
                if ($request->has('mandate_status') && $request->mandate_status != "") {
                    $mandate_status = $request->mandate_status;

                    switch ($mandate_status) {
                        case 'pending':
                            $approved = 0;
                            break;
                        case 'approved':
                            $approved = 1;
                            break;
                        case 'failed':
                            $approved = 2;
                            break;
                        case 'cancelled':
                            $approved = 9;
                            break;
                        default:
                            $approved = 0;
                    }

                    $debit_orders = $debit_orders->where('debit_orders.approved', $approved);
                }

                //filtering by service provider
                if ($request->has('partner_id') && $request->partner_id != "") {
                    $partner_id = $request->partner_id;

                    $debit_orders = $debit_orders->where('debit_orders.partner_id', '=', $partner_id);
                }

                //filtering by account type
                if ($request->has('account_type_id') && $request->account_type_id != null) {
                    $account_type_id = $request->account_type_id;
                    $debit_orders = $debit_orders->whereHas('customerAccount', function ($query) use ($account_type_id) {
                        $query->where('account_type_id', $account_type_id);
                    });
                }

                //filtering by account
                if ($request->has('account_id') && $request->account_id != null) {
                    $account_id = $request->account_id;
                    $debit_orders = $debit_orders->whereHas('customerAccount', function ($query) use ($account_id) {
                        $query->where('account_id', $account_id);
                    });
                }

                $debit_orders = $debit_orders->orderBy('debit_orders.created_at', 'DESC')->get();

                //array debit orders
                $arr_debit_orders = [];

                foreach ($debit_orders as $val) {
                    //format status
                    $consent_status = "PENDING";
                    if (intval($val->approved) == 0)
                        $consent_status = "PENDING";
                    else if (intval($val->approved) == 1)
                        $consent_status = "APPROVED";
                    else if (intval($val->approved) == 2)
                        $consent_status = "FAILED";
                    else if (intval($val->approved) == 9)
                        $consent_status = "CANCELLED";

                    //arr debit orders
                    $arr_debit_orders[] = [
                        'Business_Name' => strtoupper($val->partner->business_name),
                        'Mandate_Reference' => $val->reference,
                        'Mandate_Status' => $consent_status,
                        'Account_Type' => strtoupper($val->customerAccount->account->name ?? ''),
                        'Account_Name' => strtoupper($val->customerAccount->account_name ?? ''),
                        'Account_Number' => $val->customerAccount->account_number ?? '',
                        'Customer_Address' => $val->customerAccount->customer->address ?? '',
                        'Debit_Type' => $val->debit_type,
                        'Periodicity' => $val->periodicity,
                        'Number_Installment' => $val->installment_number,
                        'Total_Amount' => number_format($val->total_amount),
                        'Paid_Amount' => number_format($val->current_balance),
                        'Outstanding_Amount' => number_format($val->outstanding_amount),
                        'Deduction_Amount' => $val->installment_amount == 0 ? number_format($val->max_installment_amount) : number_format($val->installment_amount),
                        'Deduction_Start_Date' => $val->start_date,
                        'Deduction_End Date' => $val->end_date,
                        'Contract_Status' => $val->contract_status,
                        'Created_Date' => $val->created_at,
                        'Created_By' => strtoupper($val->userCreated->name),
                        'Remarks' => $val->remarks,
                    ];
                }

                //create filename
                $filename = "Debit_Mandates_" . date('YmdHis')  . ".xlsx";

                //return
                return Excel::download(new DebitOrders($arr_debit_orders), $filename);
            } else if ($request->report_type == "EXPECTED_COLLECTION") {
                //Query for payment collection
                $installments = Installment::filterCondition()
                    ->select('id', 'debit_order_id', 'installment_amount', 'last_paid_amount', 'current_balance', 'outstanding_amount', 'status', 'remarks', 'payment_date', 'effective_payment_date', 'created_at')
                    ->whereHas('debitOrder', function ($query) {
                        $query->where('debit_orders.approved', '=', 1)
                            ->whereHas('partner', function ($q) {
                                $q->where('partners.approved', 1);
                            });
                    });

                //filtering start and end date
                if ($request->has('start_at') && $request->has('end_at')) {
                    //format dates
                    $start_at = Carbon::createFromFormat('Y-m-d', $request->start_at)->startOfDay();
                    $end_at = Carbon::createFromFormat('Y-m-d', $request->end_at)->endOfDay();

                    $installments = $installments->whereBetween('installments.payment_date', [$start_at, $end_at]);
                }

                //filtering by service provider
                if ($request->has('partner_id') && $request->partner_id != "") {
                    $partner_id = $request->partner_id;

                    $installments = $installments->whereHas('debitOrder', function ($query) use ($partner_id) {
                        $query->where('debit_orders.partner_id', '=', $partner_id);
                    });
                }

                // filtering status
                if ($request->has('collection_status') && $request->collection_status != "") {
                    $collection_status = $request->collection_status;

                    switch ($collection_status) {
                        case 'pending':
                            $paid = "OPEN";
                            break;
                        case 'paid':
                            $paid = "CLOSED";
                            break;
                        case 'failed':
                            $paid = "FAILED";
                            break;
                        case 'fund_check':
                            $paid = "FUNDSCHECK";
                            break;
                        default:
                            $paid = "CLOSED";
                    }

                    $installments = $installments->where("installments.status", "=", $paid);
                }

                //order installments
                $installments = $installments->orderBy('installments.payment_date', 'ASC')->get();

                //arr installments
                $arr_installments = [];

                foreach ($installments as $val) {
                    if ($val->status == "CLOSED")
                        $status = "PAID";
                    elseif ($val->status == "FAILED")
                        $status = "FAILED";
                    else
                        $status = strtoupper($val->status);

                    //data array
                    $arr_installments[] = [
                        'Business_Name' => strtoupper($val->debitOrder->partner->business_name),
                        'Mandate_Reference' => $val->debitOrder->reference,
                        'Account_Name' => strtoupper($val->debitOrder->customerAccount->account_name),
                        'Account_Number' => $val->debitOrder->customerAccount->account_number,
                        'Customer_Address' => $val->debitOrder->customerAccount->customer->address ?? '',
                        'Periodicity' => $val->debitOrder->periodicity,
                        'Installment_Amount' => $val->installment_amount ?? 0,
                        'Last_Paid_Amount' => $val->last_paid_amount ?? 0,
                        'Current_Balance' => $val->current_balance ?? 0,
                        'Outstanding_Amount' => $val->outstanding_amount ?? 0,
                        'Payment_Status' => $status,
                        'Payment_Date' => Carbon::parse($val->payment_date)->format('d-m-Y'),
                        'Effective_Payment_Date' => Carbon::parse($val->effective_payment_date)->format('d-m-Y'),
                        'Start_Date' => Carbon::parse($val->debitOrder->start_date)->format('d-m-Y'),
                        'End_Date' => Carbon::parse($val->debitOrder->end_date)->format('d-m-Y'),
                        'Payment_Remarks' => $val->remarks,
                    ];
                }

                //create filename
                $filename = "Expected_Payment_Collection_" . date('YmdHis')  . ".xlsx";

                //return
                return Excel::download(new CollectionPayments($arr_installments), $filename);
            } else if ($request->report_type == "ACTUAL_COLLECTION") {
                //Query for payment collection
                $installments = Installment::filterCondition()
                    ->select('id', 'debit_order_id', 'installment_amount', 'last_paid_amount', 'current_balance', 'outstanding_amount', 'status', 'remarks', 'payment_date', 'effective_payment_date', 'created_at')
                    ->where("installments.status", "=", "CLOSED")
                    ->whereHas('debitOrder', function ($query) {
                        $query->where('debit_orders.approved', '=', 1)
                            ->whereHas('partner', function ($q) {
                                $q->where('partners.approved', 1);
                            });
                    });

                //filtering start and end date
                if ($request->has('start_at') && $request->has('end_at')) {
                    //format dates
                    $start_at = Carbon::createFromFormat('Y-m-d', $request->start_at)->startOfDay();
                    $end_at = Carbon::createFromFormat('Y-m-d', $request->end_at)->endOfDay();

                    Log::debug("Report Start_Date = {$start_at}");
                    Log::debug("Report End_Date = {$end_at}");

                    $installments = $installments->whereBetween('installments.effective_payment_date', [$start_at, $end_at]);
                }

                //filtering by service provider
                if ($request->has('partner_id') && $request->partner_id != "") {
                    $partner_id = $request->partner_id;

                    $installments = $installments->whereHas('debitOrder', function ($query) use ($partner_id) {
                        $query->where('debit_orders.partner_id', '=', $partner_id);
                    });
                }

                //order installments
                $installments = $installments->orderBy('installments.effective_payment_date', 'ASC')->get();

                //arr installments
                $arr_installments = [];

                foreach ($installments as $val) {
                    if ($val->status == "CLOSED")
                        $status = "PAID";

                    //data array
                    $arr_installments[] = [
                        'Business_Name' => strtoupper($val->debitOrder->partner->business_name),
                        'Mandate_Reference' => $val->debitOrder->reference,
                        'Account_Name' => strtoupper($val->debitOrder->customerAccount->account_name),
                        'Account_Number' => $val->debitOrder->customerAccount->account_number,
                        'Customer_Address' => $val->debitOrder->customerAccount->customer->address ?? '',
                        'Periodicity' => $val->debitOrder->periodicity,
                        'Installment_Amount' => $val->installment_amount ?? 0,
                        'Last_Paid_Amount' => $val->last_paid_amount ?? 0,
                        'Current_Balance' => $val->current_balance ?? 0,
                        'Outstanding_Amount' => $val->outstanding_amount ?? 0,
                        'Payment_Status' => $status,
                        'Payment_Date' => Carbon::parse($val->payment_date)->format('d-m-Y'),
                        'Effective_Payment_Date' => Carbon::parse($val->effective_payment_date)->format('d-m-Y'),
                        'Start_Date' => Carbon::parse($val->debitOrder->start_date)->format('d-m-Y'),
                        'End_Date' => Carbon::parse($val->debitOrder->end_date)->format('d-m-Y'),
                        'Payment_Remarks' => $val->remarks,
                    ];
                }

                //create filename
                $filename = "Actual_Payment_Collection_" . date('YmdHis')  . ".xlsx";

                //return
                return Excel::download(new CollectionPayments($arr_installments), $filename);
            }
        }

        //populate data
        $account_types = AccountType::all();
        $accounts = Account::where('active', '=', 1)->get();
        $partners = Partner::filterCondition()->where('status', '=', 202)->get();

        //render view
        return view("reports.index", compact('title', 'account_types', 'accounts', 'partners'));
    }
}
