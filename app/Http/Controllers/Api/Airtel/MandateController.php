<?php

namespace App\Http\Controllers\Api\Airtel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MandateController extends Controller
{
    private $auth;

    public function __construct() {}

    //push mandate to Airtel
    public function create(Request $request) {}

    //callback function from Airtel
    public function callback(Request $request)
    {
        //logging response
        Log::debug('== Airtel Callback Response ==');
        Log::debug($request->all());
    }
}
