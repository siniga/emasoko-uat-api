<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\PermWrapper;
use App\Jobs\SendSMSJob;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Services\MessagingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class SmsLogsController extends Controller
{
    private $data;
    protected MessagingService $messagingService;

    /**
     * Create a new controller instance.
     */
    public function __construct(MessagingService $messagingService)
    {
        $this->messagingService = $messagingService;
    }

    //index
    public function index(Request $request)
    {
        $title = "SMS Logs";

        //sms logs
        $sms_logs = SmsLog::orderBy('created_at', 'desc');

        //sms logs
        $sms_logs = $sms_logs->paginate(100);

        //render view
        return view('messaging.sms_logs.lists', compact('title', 'sms_logs'));
    }

    //send quick sms
    public function quick_sms()
    {
        $title = 'Send Quick SMS';
        //has_allowed('roles', 'create');

        //render view
        return view('messaging.sms_logs.quick_sms', compact('title'));
    }

    //send quick sms
    public function send_quick_sms(Request $request)
    {
        //validation
        $this->validate(
            $request,
            [
                'phone' => 'required|string',
                'message' => 'required|string',
                'schedule_at' => 'required_if:schedule,==,1',
                'schedule_time' => 'required_if:schedule,==,1',

            ],
            [
                'phone.required' => 'Phone required',
                'message.required' => 'Message required',
                'schedule_at.required_if' => 'Schedule date required',
                'schedule_time.required_if' => 'Schedule time required',
            ]
        );

        //post data
        $arr_phone = trim($request->input('phone'));
        $message = $request->input('message');
        $schedule_at = $request->input('schedule_at') . ' ' . $request->input('schedule_time');

        //message id
        $messageId = $this->messagingService->generateMessageId();

        //expode phones
        $recipients = explode(',', $arr_phone);

        foreach ($recipients as $val) {
            //save to database
            $sms_log = SmsLog::firstOrNew([
                'message_id' => $messageId,
                'phone' => $this->messagingService->castPhone(trim($val)),
            ]);
            $sms_log->uuid = Str::uuid();
            $sms_log->message = $message;
            $sms_log->number_of_sms = $this->messagingService->countNumberOfSms($message);
            $sms_log->schedule = $request->input('schedule');
            $sms_log->schedule_at = date('Y-m-d H:i:s', strtotime($schedule_at));
            $sms_log->status = 'PENDING';
            $sms_log->created_by = Auth::user()->id;
            $sms_log->save();
        }

        //trigger background job for send SMS
        dispatch(new SendSMSJob(['messageId' => $messageId]));

        //redirect 
        return Redirect::route('sms-logs.quick-sms')->with('success', 'Quick sms processed successfully!');
    }

    //send file sms
    public function file_sms()
    {
        $title = 'Send File SMS';
        //has_allowed('roles', 'create');

        //render view
        return view('messaging.sms_logs.file_sms', compact('title'));
    }

    //send file sms
    function send_file_sms(Request $request)
    {
        //validation
        $this->validate(
            $request,
            [
                'attachment' => 'required',
                'message' => 'required|string',
                'schedule_at' => 'required_if:schedule,==,1',
                'schedule_time' => 'required_if:schedule,==,1',
            ],
            [
                'attachment.required' => 'Attachment required',
                'message.required' => 'Message required',
                'schedule_at.required_if' => 'Schedule date required',
                'schedule_time.required_if' => 'Schedule time required',
            ]
        );

        //post data
        $message = $request->input('message');
        $schedule_at = $request->input('schedule_at') . ' ' . $request->input('schedule_time');

        //deal with attachment
        $path = $request->file('attachment');
        $row = Excel::toArray([], $path);

        //form validation
        $count = 2;
        $validation = [];
        for ($i = 1; $i < sizeof($row[0]); $i++) {
            if ($row[0][$i][0] == '')
                array_push($validation, 'Missing phone number ' . $count);
            $count++;
        }

        //check for validation
        if (count($validation) > 0) {
            return Redirect::back()->with('validation_errors', $validation);
        } else {
            //message id
            $messageId = $this->messagingService->generateMessageId();

            //count success sms
            $count = 2;
            $success = 0;
            for ($i = 1; $i < sizeof($row[0]); $i++) {
                //check if phone is 10 digits and start with 0
                if ((strlen($row[0][$i][0]) == 10 || strlen($row[0][$i][0]) == 9))
                    $phone = $row[0][$i][0];
                else
                    $phone = null;

                //check if phone is not null or ''
                if ($phone != null || $phone != '') {
                    //save to database
                    $sms_log = SmsLog::firstOrNew([
                        'message_id' => $messageId,
                        'phone' => $this->messagingService->castPhone($phone),
                    ]);
                    $sms_log->uuid = Str::uuid();
                    $sms_log->message = $message;
                    $sms_log->number_of_sms = $this->messagingService->countNumberOfSms($message);
                    $sms_log->schedule = $request->input('schedule');
                    $sms_log->schedule_at = date('Y-m-d H:i:s', strtotime($schedule_at));
                    $sms_log->status = 'PENDING';
                    $sms_log->created_by = Auth::user()->id;
                    $sms_log->save();
                }
            }

            //trigger background job for send SMS
            dispatch(new SendSMSJob(['messageId' => $messageId]));
        }

        //redirect 
        return Redirect::route('sms-logs.file-sms')->with('success', 'File sms processed successfully!');
    }
}
