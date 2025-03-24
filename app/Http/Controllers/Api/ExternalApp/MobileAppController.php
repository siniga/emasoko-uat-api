<?php

namespace App\Http\Controllers\Api\ExternalApp;

use App\Http\Controllers\Controller;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class MobileAppController extends Controller
{
    function serivice_provider_dashboard(Request $request)
    {
        $approvedStatuses = [0, 1, 2];

        //pending debit orders
        $pending_debit_orders = DebitOrder::filterCondition()->where(['approved' => 0])->count();

        //approved debit orders
        $approved_debit_orders = DebitOrder::filterCondition()->where(['approved' => 1])->count();

        //failed debit orders
        $failed_debit_orders = DebitOrder::filterCondition()->where(['approved' => 2])->count();

        //failed debit orders
        $recent_debit_orders = DebitOrder::filterCondition()->take(10)->get();

        // graph stats
        $stats = [13545454, 23545454, 12545454, 23545454, 13545454, 23545454, 23545454, 23545454, 23545454, 23545454, 23545454, 23545454];

        return response()->json([
            'success' => true,
            'response_code' => 200,
            'data' => [
                'pending_debit_orders' => $pending_debit_orders,
                'approved_debit_orders' => $approved_debit_orders,
                'failed_debit_orders' => $failed_debit_orders,
                'total_debit_orders' => ($pending_debit_orders + $approved_debit_orders + $failed_debit_orders),
                'pending_debit_orders_val' => 1403000,
                'approved_debit_orders_val' => 140380000,
                'failed_debit_orders_val' => 403880000,
                'total_debit_orders_val' => 140300,
                'stats' => $stats,
                'recent_mandates' => $recent_debit_orders
            ]
        ]);
    }
}
