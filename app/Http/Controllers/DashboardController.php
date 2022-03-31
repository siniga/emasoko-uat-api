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

        //debit order
        $debit_orders = DebitOrder::where_cond()->get();

        //pending debit orders
        $number_of_pending = DebitOrder::where(['approved' => 0])->count();

        //approved debit orders
        $number_of_approved = DebitOrder::where(['approved' => 1])->count();

        //rejected debit orders
        $number_of_rejected = DebitOrder::where(['approved' => 99])->count();

        //pending debit orders
        $pending_debit_orders = DebitOrder::where(['approved' => 0])->get();

        //approved debit orders
        $approved_debit_orders = DebitOrder::where(['approved' => 1])->get();

        //rejected debit orders
        $rejected_debit_orders = DebitOrder::where(['approved' => 99])->get();

        //render view
        return view('dashboard', compact('title', 'debit_orders', 'number_of_pending', 'number_of_approved', 'number_of_rejected', 'pending_debit_orders', 'approved_debit_orders', 'rejected_debit_orders'));
    }
}
