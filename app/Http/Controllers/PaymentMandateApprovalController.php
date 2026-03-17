<?php

namespace App\Http\Controllers;

use App\Models\DebitOrder;
use App\Models\DebitOrderRequest;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class PaymentMandateApprovalController extends BasePaymentMandateController
{
    public function request_cancellation(Request $request)
    {
        $title = 'Request payment mandate cancellation';

        if ($request->isMethod('post')) {
            $request->validate([
                'partner_id' => 'required|exists:partners,id',
                'attachment' => 'required|file|mimes:xls,xlsx|mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

            $serviceProviderId = $request->input('partner_id');
            $data = Excel::toArray([], $request->file('attachment'));
            $rows = $data[0];
            $headers = array_map('trim', $rows[0]);

            $errors = [];
            foreach (array_slice($rows, 1) as $index => $row) {
                $rowAssoc = array_combine($headers, $row);

                $validator = Validator::make($rowAssoc, [
                    'Mandate_Reference' => [
                        'required',
                        'numeric',
                        function ($attribute, $value, $fail) use ($serviceProviderId) {
                            $exists = DebitOrder::where('reference', $value)
                                ->where('partner_id', $serviceProviderId)
                                ->exists();

                            if (!$exists) {
                                $fail("Mandate Reference [$value] does not belong to the selected Service Provider.");
                            }
                        },
                    ],
                ]);

                if ($validator->fails()) {
                    $errors[$index + 1] = $validator->errors()->all();
                }
            }

            if (!empty($errors)) {
                return back()->with('file_errors', $errors);
            }

            $mandateReferences = [];
            foreach (array_slice($rows, 1) as $row) {
                $rowAssoc = array_combine($headers, $row);
                $mandateReferences[] = $rowAssoc['Mandate_Reference'];
            }

            foreach ($mandateReferences as $reference) {
                $debitOrder = DebitOrder::where('reference', $reference)
                    ->where('partner_id', $serviceProviderId)
                    ->first();

                if ($debitOrder) {
                    $this->paymentMandateWorkflowService->requestCancellation($debitOrder, Auth::id());
                }
            }

            return Redirect::route('payment-mandate.request-cancellation')->with('success', 'Cancellation requests have been successfully created.');
        }

        $providers = Partner::filterCondition()->get();

        return view('payment_mandate.request_cancellation', compact('title', 'providers'));
    }

    public function approve_cancellation(Request $request)
    {
        $title = 'Approve payment mandate cancellation';

        if ($request->isMethod('post')) {
            if (!$request->has('request_id')) {
                return back()->with('danger', 'No payment mandate selected for cancellation.');
            }

            foreach ($request->post('request_id') as $dd_request_id) {
                $cancellationRequest = DebitOrderRequest::findOrFail($dd_request_id);
                $this->paymentMandateWorkflowService->approveCancellation($cancellationRequest, Auth::id());
            }

            return Redirect::route('payment-mandate.approve-cancellation')->with('success', 'Payment mandate cancellation request approved successfully.');
        }

        $dd_requests = DebitOrderRequest::with('debitOrder')
            ->where('request_type', 'CANCELLATION')->where('status', 'PENDING')->get();

        return view('payment_mandate.approve_cancellation', compact('title', 'dd_requests'));
    }

    public function request_deletion(Request $request)
    {
        $title = 'Request payment mandate deletion';

        if ($request->isMethod('post')) {
            $request->validate([
                'partner_id' => 'required|exists:partners,id',
                'attachment' => 'required|file|mimes:xls,xlsx|mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

            $serviceProviderId = $request->input('partner_id');
            $data = Excel::toArray([], $request->file('attachment'));
            $rows = $data[0];
            $headers = array_map('trim', $rows[0]);

            $errors = [];
            foreach (array_slice($rows, 1) as $index => $row) {
                $rowAssoc = array_combine($headers, $row);

                $validator = Validator::make($rowAssoc, [
                    'Mandate_Reference' => [
                        'required',
                        'numeric',
                        function ($attribute, $value, $fail) use ($serviceProviderId) {
                            $debit_order = DebitOrder::where('reference', $value)
                                ->where('partner_id', $serviceProviderId);

                            if (!$debit_order->exists()) {
                                $fail("Mandate Reference [$value] does not belong to the selected Service Provider.");
                            } else {
                                $status = $debit_order->first()->status;
                                if ($status === 240) {
                                    $fail("Mandate Reference [$value] is active. You can not delete an active mandate.");
                                }
                            }
                        },
                    ],
                ]);

                if ($validator->fails()) {
                    $errors[$index + 1] = $validator->errors()->all();
                }
            }

            if (!empty($errors)) {
                return back()->with('file_errors', $errors);
            }

            $mandateReferences = [];
            foreach (array_slice($rows, 1) as $row) {
                $rowAssoc = array_combine($headers, $row);
                $mandateReferences[] = $rowAssoc['Mandate_Reference'];
            }

            foreach ($mandateReferences as $reference) {
                $debitOrder = DebitOrder::where('reference', $reference)
                    ->where('partner_id', $serviceProviderId)
                    ->first();

                if ($debitOrder) {
                    $this->paymentMandateWorkflowService->requestDeletion($debitOrder, Auth::id());
                }
            }

            return Redirect::route('payment-mandate.request-deletion')->with('success', 'Deletion requests have been successfully created.');
        }

        $providers = Partner::filterCondition()->get();

        return view('payment_mandate.request_deletion', compact('title', 'providers'));
    }

    public function delete_selected(Request $request)
    {
        $title = 'Request payment mandate deletion';

        return view('payment_mandate.request_deletion', compact('title'));
    }

    public function approve_deletion(Request $request)
    {
        $title = 'Approve payment mandate cancellation';

        if ($request->isMethod('post')) {
            if (!$request->has('request_id')) {
                return back()->with('danger', 'No payment mandate selected for deletion.');
            }

            foreach ($request->post('request_id') as $dd_request_id) {
                $cancellationRequest = DebitOrderRequest::findOrFail($dd_request_id);
                $this->paymentMandateWorkflowService->approveDeletion($cancellationRequest, Auth::id());
            }

            return Redirect::route('payment-mandate.approve-deletion')->with('success', 'Payment mandate deletion request approved successfully.');
        }

        $dd_requests = DebitOrderRequest::with('debitOrder')
            ->where('request_type', 'DELETION')->where('status', 'PENDING')->get();

        return view('payment_mandate.approve_deletion', compact('title', 'dd_requests'));
    }

    public function request_closing(Request $request)
    {
        $title = 'Request payment mandate closing';

        if ($request->isMethod('post')) {
            $request->validate([
                'partner_id' => 'required|exists:partners,id',
                'attachment' => 'required|file|mimes:xls,xlsx|mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

            $serviceProviderId = $request->input('partner_id');
            $data = Excel::toArray([], $request->file('attachment'));
            $rows = $data[0];
            $headers = array_map('trim', $rows[0]);

            $errors = [];
            foreach (array_slice($rows, 1) as $index => $row) {
                $rowAssoc = array_combine($headers, $row);

                $validator = Validator::make($rowAssoc, [
                    'Mandate_Reference' => [
                        'required',
                        'numeric',
                        function ($attribute, $value, $fail) use ($serviceProviderId) {
                            $exists = DebitOrder::where('reference', $value)
                                ->where('partner_id', $serviceProviderId)
                                ->exists();

                            if (!$exists) {
                                $fail("Mandate Reference [$value] does not belong to the selected Service Provider.");
                            }
                        },
                    ],
                ]);

                if ($validator->fails()) {
                    $errors[$index + 1] = $validator->errors()->all();
                }
            }

            if (!empty($errors)) {
                return back()->with('file_errors', $errors);
            }

            $mandateReferences = [];
            foreach (array_slice($rows, 1) as $row) {
                $rowAssoc = array_combine($headers, $row);
                $mandateReferences[] = $rowAssoc['Mandate_Reference'];
            }

            foreach ($mandateReferences as $reference) {
                $debitOrder = DebitOrder::where('reference', $reference)
                    ->where('partner_id', $serviceProviderId)
                    ->first();

                if ($debitOrder) {
                    $this->paymentMandateWorkflowService->requestClosing($debitOrder, Auth::id());
                }
            }

            return Redirect::route('payment-mandate.request-closing')->with('success', 'Closing requests have been successfully created.');
        }

        $providers = Partner::filterCondition()->get();

        return view('payment_mandate.request_closing', compact('title', 'providers'));
    }

    public function approve_closing(Request $request)
    {
        $title = 'Approve payment mandate closing';

        if ($request->isMethod('post')) {
            if (!$request->has('request_id')) {
                return back()->with('danger', 'No payment mandate selected for closing.');
            }

            foreach ($request->post('request_id') as $dd_request_id) {
                $closingRequest = DebitOrderRequest::findOrFail($dd_request_id);
                $this->paymentMandateWorkflowService->approveClosing($closingRequest, Auth::id());
            }

            return Redirect::route('payment-mandate.approve-closing')->with('success', 'Payment mandate closing request approved successfully.');
        }

        $dd_requests = DebitOrderRequest::with('debitOrder')
            ->where('request_type', 'CLOSING')->where('status', 'PENDING')->get();

        return view('payment_mandate.approve_closing', compact('title', 'dd_requests'));
    }

    public function request_archive($uuid)
    {
        $debitOrder = DebitOrder::where(['uuid' => $uuid])->first();
        $this->paymentMandateWorkflowService->archive($debitOrder, Auth::id());

        return response()->json(['success' => true, 'success_msg' => 'Archiving payment mandate request sent.'], 200);
    }

    public function approve_archive(Request $request)
    {
        $title = 'Approve payment mandate archive';

        if ($request->isMethod('post')) {
            if (!$request->has('request_id')) {
                return back()->with('danger', 'No payment mandate selected for archive.');
            }

            foreach ($request->post('request_id') as $dd_request_id) {
                $closingRequest = DebitOrderRequest::findOrFail($dd_request_id);
                $this->paymentMandateWorkflowService->approveArchive($closingRequest, Auth::id());
            }

            return Redirect::route('payment-mandate.approve-archive')->with('success', 'Payment mandate archive request approved successfully.');
        }

        $dd_requests = DebitOrderRequest::with('debitOrder')
            ->where('request_type', 'ARCHIVE')->where('status', 'PENDING')->get();

        return view('payment_mandate.approve_archive', compact('title', 'dd_requests'));
    }
}
