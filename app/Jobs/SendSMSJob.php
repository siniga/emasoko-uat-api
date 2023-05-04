<?php

namespace App\Jobs;

use App\Classes\Messaging;
use App\Models\SmsLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSMSJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //call messaging class
        $messaging = new Messaging();

        //data submitted
        $messageId = $this->data['message_id'];
        $schedule = $this->data['schedule'];

        //limit
        $limit = 100;
        $no_of_pending_sms = SmsLog::where(['message_id' => $messageId, 'status' => 'PENDING'])->count();

        //looping sms
        $looping = $no_of_pending_sms / $limit;

        //iterate looping
        for ($i = 1; $i <= ceil($looping); $i++) {
            $recipients = SmsLog::select('id', 'phone', 'schedule_at')->where(['message_id' => $messageId, 'status' => 'PENDING'])->take($limit)->get();

            foreach ($recipients as $val) {
                $arr_data = [
                    'phone' => $messaging->castPhone($val->phone),
                    'message' => $val->message
                ];

                //post data
                $response = $messaging->sendSMS($arr_data);
                $result = json_decode($response);

                Log::debug("SMS response");
                Log::debug($result);
            }
        }
    }
}
