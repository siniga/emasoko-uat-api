<?php

namespace App\Services;

use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessagingService
{
    private string $USERNAME;
    private string $PASSWORD;
    private string $API_BASE_URL;

    public function __construct()
    {
        $this->USERNAME = 'SOLOCODES';
        $this->PASSWORD = 'L@1N@2022';
        $this->API_BASE_URL = 'https://mshastra.com/sendurl.aspx';
    }

    //action to send sms
    public function actionSendSMS(string $messageId)
    {
        //limit
        $limit = 100;
        $no_of_pending_sms = SmsLog::where(['message_id' => $messageId, 'status' => 'PENDING'])->count();

        //looping sms
        $looping = $no_of_pending_sms / $limit;

        //iterate looping
        for ($i = 1; $i <= ceil($looping); $i++) {
            $recipients = SmsLog::select('id', 'phone', 'message', 'sender', 'schedule', 'schedule_at')->where(['message_id' => $messageId, 'status' => 'PENDING'])->take($limit)->get();

            foreach ($recipients as $val) {
                $phone = $this->castPhone($val->phone);
                $sender = $val->sender;
                $message = $val->message;

                //send message by curl
                $response = $this->sendSMSByCurl($phone, $sender, $message);

                // $responseArr = json_decode($response, true);
                // if ($responseArr && isset($responseArr['results'][0]['status']) && $responseArr['results'][0]['status'] == '0') {
                //     //update sms status
                //     $sms_log = SmsLog::findOrFail($val->id);
                //     $sms_log->response = json_encode($responseArr);
                //     $sms_log->status = "SENT";
                //     $sms_log->save();
                // } else {
                //     //update sms status
                //     $sms_log = SmsLog::findOrFail($val->id);
                //     $sms_log->response = json_encode($responseArr);
                //     $sms_log->status = "FAILED";
                //     $sms_log->save();
                // }
            }
        }

        return response()->json(['status' => true, 'message' => 'SMS sent successfully']);
    }

    //send sms by curl
    public function sendSMSByCurl(string $phone, string $sender, string $message): string
    {
        //create payload
        $payload = [
            'user'  => $this->USERNAME,
            'pwd'   => $this->PASSWORD,
            'senderid'  => $sender,
            'mobileno'  => $phone,
            'msgtext'  => $message,
            'priority'     => 'High',
            'CountryCode'    => 'ALL',
        ];

        Log::debug("== SMS Payload ==");
        Log::debug($payload);

        //send sms to API
        $response = Http::withOptions(['verify' => false])->get($this->API_BASE_URL, $payload);
        Log::debug("== SMS API Response ==");
        Log::debug($response->body());

        //return response
        return $response;
    }

    //action to send notification
    public function sendSMS(array $arr_data) {}

    //activon to get delivery reports
    public function deliveryReport(array $arr_data) {}

    //generate message_id
    public function generateMessageId()
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

    // count number of sms
    public function countNumberOfSms(string $message): int
    {
        $length = mb_strlen($message, 'UTF-8');

        if ($length <= 160) {
            return 1;
        }

        // Deduct first 160 chars, remaining are divided by 159
        $remaining = $length - 160;

        return 1 + (int) ceil($remaining / 159);
    }

    //remove 0 and + on start of mobile
    function castPhone(string $mobile): string
    {
        $M_CODE = "255";

        if (preg_match("~^0\d+$~", $mobile)) {
            return $M_CODE . substr($mobile, 1);
        } else if (substr($mobile, 0, 3) != '255' & strlen($mobile) == 9) {
            return $M_CODE . $mobile;
        } else {
            return str_replace('+', '', $mobile);
        }
    }

    //add 0 on the mobile phone
    public function addZeroOnPhone(string $mobile): string
    {
        if (!empty($mobile)) {
            if (strlen($mobile) == 9) {
                $phone = '0' . $mobile;
            } else if (strlen($mobile) == 12) {
                $phone = '0' . substr($mobile, -9);
            } else {
                $phone = $mobile;
            }
        } else {
            $phone = $mobile;
        }

        return $phone;
    }
}
