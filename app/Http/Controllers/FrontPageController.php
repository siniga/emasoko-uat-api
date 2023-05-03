<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use App\Jobs\SendEmailJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FrontPageController extends Controller
{
    //front page
    function index()
    {
        $title = "Enabling a seamless payment collections experience.";

        //render view
        return view('front_page', compact('title'));
    }

}
