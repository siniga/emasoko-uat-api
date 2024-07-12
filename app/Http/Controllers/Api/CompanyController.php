<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\DocumentCategory;
use App\Models\Partner;
use App\Models\PartnerCategory;
use App\Models\Region;
use Illuminate\Http\Request;
use Stidges\CountryFlags\CountryFlag;
use Illuminate\Support\Facades\Validator;
use App\Jobs\SendEmailJob;
use App\Models\PartnerApproval;

class CompanyController extends Controller
{
    //contact information
    function contacts(Request $request)
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

    //categories
    function categories()
    {
        $categories = PartnerCategory::all();

        return $categories;
    }

    //countries
    function countries()
    {
        $countries = Country::all();
        $flag = new CountryFlag;

        $arr_countries = [];
        foreach ($countries as $val) {
            $arr_countries[] = [
                'id' => $val->id,
                'code' => $val->code,
                'flag' => $flag->get(strtoupper($val->initial)) . ' ' . $val->code
            ];
        }

        return $arr_countries;
    }

    //regions
    function regions()
    {
        $regions = Region::all();

        return $regions;
    }

    //Document Categories
    function document_categories()
    {
        $categories = DocumentCategory::all();

        return $categories;
    }
}
