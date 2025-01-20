<?php

namespace App\Http\Controllers;

use App\Exports\CollectionExport;
use App\Models\Account;
use App\Models\AccountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentMandateExport;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function lists(Request $request)
    {
        $title = "System Reports";

        if (isset($_POST['generate'])) {
            //all request
            $report_type = $request->report_type;
            $report_format = $request->report_format;
            $start_date = $request->start_at;
            $end_date = $request->end_at;
            $account_type_id = $request->account_type_id;
            $account_id = $request->account_id;

            if ($report_type == "MANDATE") {
                return Excel::download(new PaymentMandateExport($request->start_at, $request->end_at, $request->account_type_id, $request->account_id, null, null), date('YmdHis') . '_DebitMandate.xlsx');
            }elseif($report_type == "COLLECTION"){
                return Excel::download(new CollectionExport($request->start_at, $request->end_at), date('YmdHis') . '_DebitMandate.xlsx');

            }
        }

        //populate data
        $account_types = AccountType::all();
        $accounts = Account::all();

        //render view
        return view("reports.index", compact('title', 'account_types', 'accounts'));
    }
}
