<?php

namespace App\Classes;

use Illuminate\Support\Facades\Http;

class Messaging
{
    private $API_BASE_URL;
    private $API_DELIVERY_URL;
    private $API_ID;
    private $API_PASSWORD;
    private $M_CODE;

    public function __construct()
    {
        $this->API_BASE_URL = 'https://api.sema.co.tz/api/SendSMS';
        $this->API_DELIVERY_URL = '';

        $this->API_ID = "API1136851327";
        $this->API_PASSWORD = "eB8bPjW!sZa#g7";
        $this->M_CODE = "255";
    }

    //action to send notification
    function sendSMS($arr_data)
    {
        $response = Http::get($this->API_BASE_URL ,[
			'api_id' => $this->API_ID,
			'api_password' => $this->API_PASSWORD,
			'sms_type'     =>'P',
			'encoding'     =>'T',
			'sender_id'    =>'LAINA',
			'phonenumber'  => $arr_data['phone'],
			'textmessage'  => $arr_data['message']
		 ]);

        //resurn repsonse
        return $response;
    }

    //activon to get delivery reports
    function deliveryReport($arr_data)
    {
       
    }

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

    //remove 0 and + on start of mobile
    function castPhone($mobile)
    {
        if (preg_match("~^0\d+$~", $mobile)) {
            return $this->M_CODE . substr($mobile, 1);
        } else if (substr($mobile, 0, 3) != '255' & strlen($mobile) == 9) {
            return $this->M_CODE . $mobile;
        } else {
            return str_replace('+', '', $mobile);
        }
    }

    //add 0 on the mobile phone
    function addZeroOnPhone($mobile)
    {
        if (!empty($mobile)) {
            if (strlen($mobile) == 9) {
                $phone = '0' . $mobile;
            } else {
                $phone = $mobile;
            }
        } else {
            $phone = $mobile;
        }

        return $phone;
    }
}
