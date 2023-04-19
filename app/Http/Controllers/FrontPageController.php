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
        $title = "Pay Less";

        //render view
        return view('front_page', compact('title'));
    }

    //request for information
    function request_for_information()
    {
        $title = "Request for Information";

        //render view
        return view('request_for_information', compact('title'));
    }


    //process information
    function process_information(Request $request)
    {
        //validator
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'phone' => 'required',
            'subject' => 'required',
            'message' => 'required',
        ]);

        //post variables
        $email = $request->input('email');
        $phone = $request->input('phone');
        $subject = $request->input('subject');
        $message = $request->input('message');

        //construct message
        $message_to_user = "";
        $message_to_user .= "<p>Phone : '$phone'</p>";
        $message_to_user .= "<p>Inquiries : '$subject'</p>";
        $message_to_user .= "<p>Message : '$message'</p>";

        //send email for verification
        $data = [
            "from_email" => $email,
            "to_email" => "renfrid.william@solocodes.io",
            "name" => "Renfrid William",
            "subject" => "Request for Information",
            "body" => $message_to_user
        ];
        dispatch(new SendEmailJob($data));

        //response message
        $response_message = '<div class="alert alert-success">Your inquiry sent and the Team will get intouch with you!</div>';

        //response
        return response()->json(['error' => false, 'success_msg' => $response_message], 200);
    }
}
