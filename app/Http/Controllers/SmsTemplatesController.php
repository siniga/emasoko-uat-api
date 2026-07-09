<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SmsCategory;
use App\Models\SmsTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmsTemplatesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $title = 'SMS Templates';
        $templates = SmsTemplate::with('category')->orderBy('name')->get();
        $categories = SmsCategory::orderBy('name')->get();

        return view('messaging.sms_templates.lists', compact('title', 'templates', 'categories'));
    }

    public function create() {}

    public function store(Request $request)
    {
        Log::debug($request->all());

        $data = $request->validate([
            'action' => 'required|in:create,update',
            'id' => 'nullable|required_if:action,update|exists:sms_templates,id',
            'category_id' => 'nullable|exists:sms_categories,id',
            'name' => 'required|string|max:50',
            'code' => 'nullable|string|max:80',
            'channel' => 'nullable|string|max:20',
            'message' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $attributes = [
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'channel' => strtoupper($data['channel'] ?? 'ALL'),
            'message' => $data['message'],
            'category_id' => $data['category_id'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];

        if ($data['action'] === 'create') {
            SmsTemplate::create($attributes + ['uuid' => (string) Str::uuid()]);

            return response()->json(['success' => true, 'success_msg' => 'SMS template added'], 200);
        }

        $template = SmsTemplate::findOrFail($data['id']);
        $template->fill($attributes);
        $template->save();

        return response()->json(['success' => true, 'success_msg' => 'SMS template updated'], 200);
    }

    public function show($id) {}

    public function get_all()
    {
        $templates = SmsTemplate::all();
        $arr_data = [];

        foreach ($templates as $template) {
            $arr_data[] = [
                'id' => $template->id,
                'name' => $template->name,
                'code' => $template->code,
                'channel' => $template->channel,
                'category_id' => $template->category_id,
                'message' => $template->message,
                'is_active' => (bool) $template->is_active,
            ];
        }

        return response()->json(['success' => true, 'data' => $arr_data], 200);
    }

    public function get_by($id)
    {
        $template = SmsTemplate::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $template->id,
                'uuid' => $template->uuid,
                'name' => $template->name,
                'code' => $template->code,
                'channel' => $template->channel,
                'category_id' => $template->category_id,
                'message' => $template->message,
                'is_active' => (bool) $template->is_active,
            ],
        ], 200);
    }

    public function edit($id) {}

    public function update(Request $request, $id) {}

    public function delete($id)
    {
        $template = SmsTemplate::findOrFail($id);
        $template->delete();

        return redirect()->route('sms-templates.index')->with(['success' => 'SMS template deleted']);
    }
}
