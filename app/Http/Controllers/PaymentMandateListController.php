<?php

namespace App\Http\Controllers;

use App\Exports\DebitOrders;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\DebitOrder;
use App\Models\Partner;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PaymentMandateListController extends BasePaymentMandateController
{
    public function lists(Request $request)
    {
        $title = 'Payment Mandate';
        $filters = $this->filters($request);
        $listQuery = $this->paymentMandateQueryService->listQuery($filters, $request->user());

        if (isset($_POST['filter'])) {
            $number_of_debit_orders = (clone $listQuery)->count();
            $debit_orders = $listQuery->orderBy('debit_orders.created_at', 'DESC')->get();
        } else {
            $number_of_debit_orders = (clone $listQuery)->count();
            $debit_orders = $listQuery->orderBy('debit_orders.created_at', 'DESC')->paginate(50)->withQueryString();
        }

        $listStats = $this->paymentMandateQueryService->stats($filters, $request->user());

        $post_data = $filters;

        $account_types = AccountType::all();
        $accounts = Account::where('active', '=', 1)->get();
        $partners = Partner::filterCondition()->where('approved', '=', 1)->get();

        return view('payment_mandate.lists', compact('title', 'account_types', 'accounts', 'partners', 'debit_orders', 'number_of_debit_orders', 'post_data', 'listStats'));
    }

    public function export_xls(Request $request)
    {
        $filters = $this->filters($request);
        $debit_orders = $this->paymentMandateQueryService
            ->exportQuery($filters, $request->user())
            ->orderBy('debit_orders.created_at', 'DESC')
            ->get();

        $arr_debit_orders = [];

        foreach ($debit_orders as $val) {
            $consent_status = match ($val->summary_status) {
                DebitOrder::SUMMARY_APPROVED => 'APPROVED',
                DebitOrder::SUMMARY_FAILED => 'FAILED',
                default => 'PENDING',
            };

            $arr_debit_orders[] = [
                'Business_Name' => strtoupper($val->partner->business_name),
                'Mandate_Reference' => $val->reference,
                'Mandate_Status' => $consent_status,
                'Account_Name' => strtoupper($val->customerAccount->account_name ?? ''),
                'Account_Number' => $val->customerAccount->account_number ?? '',
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
                'Remarks' => $val->remarks,
                'Created_Date' => $val->created_at,
                'Created_By' => $val->userCreated->name ?? '',
            ];
        }

        $filename = 'Debit_Mandates_' . date('YmdHis') . '.xlsx';

        return Excel::download(new DebitOrders($arr_debit_orders), $filename);
    }

    public function export_pdf(Request $request)
    {
        $filters = $this->filters($request);
        $debit_orders = $this->paymentMandateQueryService
            ->exportQuery($filters, $request->user())
            ->orderBy('debit_orders.created_at', 'DESC')
            ->get();

        $pdf = Pdf::loadView('payment_mandate.pdf.report', compact('debit_orders'))->setPaper('a4', 'portrait');

        return $pdf->download(date('YmdHis') . '_Payment_Mandate.pdf');
    }

    protected function filters(Request $request): array
    {
        return [
            'start_at' => $request->start_at ?? null,
            'end_at' => $request->end_at ?? null,
            'account_type_id' => $request->account_type_id ?? null,
            'account_id' => $request->account_id ?? null,
            'partner_id' => $request->partner_id ?? null,
            'status' => $request->status ?? null,
            'keyword' => $request->keyword ?? null,
        ];
    }
}
