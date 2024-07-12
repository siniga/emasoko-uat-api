<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DebitOrder;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function lists()
    {
        $title = 'Payment Mandate';
        //has_allowed('roles', 'lists');

        //pending debit orders
        $pending_debit_orders = DebitOrder::where(['approved' => 0])->get();

        //approved debit orders
        $approved_debit_orders = DebitOrder::where(['approved' => 1])->get();

        //rejected debit orders
        $rejected_debit_orders = DebitOrder::where(['approved' => 99])->get();

        //render view
        return view('tasks.lists', compact('title', 'pending_debit_orders', 'approved_debit_orders', 'rejected_debit_orders'));
    }
}
