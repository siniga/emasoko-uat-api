<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SmsCategory;
use App\Models\SmsTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmsTemplatesController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    //index
    public function index(Request $request)
    {
        $title = "SMS Templates";

        //sms templates
        $templates = SmsTemplate::all();

        //sms categories
        $categories = SmsCategory::all();

        //render view
        return view('messaging.sms_templates.lists', compact('title', 'templates', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validate post data
        Log::debug($request->all());

        if ($request->action == 'create') {
            //new sms template
            $template = new SmsTemplate([
                'uuid' => Str::uuid(),
                'name' => $request->name,
                'message' => $request->message,
                'category_id' => $request->category_id
            ]);
            $template->save();

            //response
            return response()->json(['success' => true, "success_msg" => "SMS template added"], 200);
        } else if ($request->action == 'update') {
            //update sms template
            $template = SmsTemplate::findorFail($request->id);
            $template->name = $request->name;
            $template->message = $request->message;
            $template->category_id = $request->category_id;
            $template->save();

            //response
            return response()->json(['success' => true, "success_msg" => "SMS template update"], 200);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {}

    //Get By
    public function get_all()
    {
        $templates = SmsTemplate::all();

        if ($templates) {
            foreach ($templates as $template) {
                $arr_data[] = [
                    'id' => $template->id,
                    'name' => $template->name,
                    'category_id' => $template->category_id,
                    'message' => $template->message
                ];
            }

            return response()->json(['success' => true, "data" => $arr_data], 200);
        } else {
            return response()->json(['success' => false, "data" => []], 200);
        }
    }

    //Get By
    public function get_by($id)
    {
        $template = SmsTemplate::findorFail($id);

        if (!$template) {
            return response()->json(['error' => 'SMS template not found'], 404);
        }

        if ($template) {
            $arr_data = [
                'id' => $template->id,
                'uuid' => $template->uuid,
                'name' => $template->name,
                'category_id' => $template->category_id,
                'message' => $template->message,
            ];

            return response()->json(['success' => true, "data" => $arr_data], 200);
        } else {
            return response()->json(['success' => false, "data" => []], 200);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {}

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        //sms template
        $template = SmsTemplate::findorFail($id);

        if (!$template) {
            return redirect()->route('sms-templates.index')->with(['error' => 'SMS template not found']);
        }

        //delete
        $template->delete();

        //redirect
        return redirect()->route('sms-templates.index')->with(['success' => 'SMS template delete']);
    }
}
