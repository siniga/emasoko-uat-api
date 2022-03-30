<?php

namespace App\Http\Controllers;

use App\Models\DebitOrder;
use Illuminate\Http\Request;

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

        //pending debit orders
        $pending_debit_orders = DebitOrder::where(['approved' => 0])->count();

        //approved debit orders
        $approved_debit_orders = DebitOrder::where(['approved' => 1])->count();

        //rejected debit orders
        $rejected_debit_orders = DebitOrder::where(['approved' => 99])->count();

        //render view
        return view('dashboard', compact('title', 'pending_debit_orders', 'approved_debit_orders', 'rejected_debit_orders'));
    }
}
