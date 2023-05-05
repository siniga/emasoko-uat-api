<?php

namespace App\Http\Controllers;

use App\Models\DebitOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    //index page
    function index()
    {
        $title = 'Dashboard';

        //total mandate
        $total = DebitOrder::where_cond()->select(DB::raw('SUM(total_amount) as total_amount'), DB::raw('COUNT(id) as count'))->get();

        //pending debit orders
        $pending = DebitOrder::where_cond()->select(DB::raw('SUM(total_amount) as total_amount'), DB::raw('COUNT(id) as count'))
            ->where(['approved' => 0])->get();

        //approved debit orders
        $approved = DebitOrder::where_cond()->select(DB::raw('SUM(total_amount) as total_amount'), DB::raw('COUNT(id) as count'))
            ->where(['approved' => 1])->get();

        //rejected debit orders
        $rejected = DebitOrder::where_cond()->select(DB::raw('SUM(total_amount) as total_amount'), DB::raw('COUNT(id) as count'))
            ->where(['approved' => 99])->get();

        //pending debit orders
        $pending_debit_orders = DebitOrder::where_cond()->where(['approved' => 0])->take(10)->get();

        //approved debit orders
        $approved_debit_orders = DebitOrder::where_cond()->where(['approved' => 1])->take(10)->get();

        //rejected debit orders
        $rejected_debit_orders = DebitOrder::where_cond()->where(['approved' => 99])->take(10)->get();

        //render view
        return view('dashboard', compact('title', 'total', 'pending', 'approved', 'rejected', 'pending_debit_orders', 'approved_debit_orders', 'rejected_debit_orders'));
    }
}
