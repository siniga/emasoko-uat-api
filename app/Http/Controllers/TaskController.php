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

        $debit_orders = DebitOrder::where(['approved' => 0])->get();

        //render view
        return view('tasks.lists', compact('title', 'debit_orders'));
    }
}
