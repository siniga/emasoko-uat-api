<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FrontPageController extends Controller
{

    //front page
    public function index()
    {
        $title = "Pay Less";

        //render view
        return view('front_page', compact('title'));
    }
}
