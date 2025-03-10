<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentMandateExport;
use App\Exports\CollectionExport;
use App\Models\Partner;

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
            if ($request->report_type == "MANDATE") {
                return Excel::download(new PaymentMandateExport($request), date('YmdHis') . '_DebitMandate.xlsx');
            } else if ($request->report_type == "COLLECTION") {
                return Excel::download(new CollectionExport($request), date('YmdHis') . '_Collection.xlsx');
            }
        }

        //populate data
        $account_types = AccountType::all();
        $accounts = Account::all();
        $partners = Partner::filterCondition()->where('status', '=', 202)->get();

        //render view
        return view("reports.index", compact('title', 'account_types', 'accounts', 'partners'));
    }
}
