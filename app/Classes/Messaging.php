<?php

namespace App\Classes;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Messaging
{
    public function __construct()
    {
    }

    //action to send notification
    function sendSMS($phone, $message)
    {
        //create payload
        $payload = [
            'user'       => 'SOLOCODES',
            'pwd'          => 'L@1N@2022',
            'senderid'     => 'Emakato',
            'mobileno'  => $phone,
            'msgtext'  => $message,
            'priority'     => 'High',
            'CountryCode'    => 'ALL',
        ];

        //send sms to API
        $response = Http::withOptions(['verify' => false])->get('https://mshastra.com/sendurl.aspx', $payload);

        //resurn repsonse
        return $response;
    }

    //activon to get delivery reports
    function deliveryReport($arr_data) {}

    //generate message_id
    function generateMessageId()
    {
        //the characters you want in your id
        $characters = '123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $max = strlen($characters) - 1;
        $string = '';

        for ($i = 0; $i <= 10; $i++) {
            $string .= $characters[mt_rand(0, $max)];
        }

        return $string;
    }
}
