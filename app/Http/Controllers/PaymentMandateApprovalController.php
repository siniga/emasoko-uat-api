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
    private function approvalTabConfig(): array
    {
        return [
            'cancellation' => [
                'pageTitle' => 'Approve Mandate Cancellation',
                'pageSubtitle' => 'Review pending mandate cancellation requests and approve only the records that are ready to move out of the active collection cycle.',
                'guidanceText' => 'Use this page to review cancellation requests submitted by operations teams. Select only mandates that have been validated and are safe to cancel.',
                'tableTitle' => 'Pending Cancellation Requests',
                'tableHint' => 'Select one or more requests below, then approve them in a single step.',
                'actionRoute' => 'payment-mandate.approve-cancellation',
                'actionName' => 'approve_cancellation',
                'actionValue' => 'approve_cancellation',
                'buttonLabel' => 'Approve Cancellation',
                'buttonIcon' => 'bx-chevrons-right',
                'requestLabel' => 'Cancellation',
                'emptyMessage' => 'No cancellation requests at the moment!',
                'requestType' => 'CANCELLATION',
                'tabLabel' => 'Cancellation',
            ],
            'deletion' => [
                'pageTitle' => 'Approve Mandate Deletion',
                'pageSubtitle' => 'Review deletion requests carefully before approval. This step should be reserved for mandates that should be removed from the operational dataset.',
                'guidanceText' => 'Deletion is a stronger action than cancellation or closing. Confirm the request is valid and that the mandate should no longer remain in active operational use.',
                'tableTitle' => 'Pending Deletion Requests',
                'tableHint' => 'Select one or more requests below, then approve them in a single step.',
                'actionRoute' => 'payment-mandate.approve-deletion',
                'actionName' => 'approve_deletion',
                'actionValue' => 'approve_deletion',
                'buttonLabel' => 'Approve Deletion',
                'buttonIcon' => 'bx-trash',
                'requestLabel' => 'Deletion',
                'emptyMessage' => 'No deletion requests at the moment!',
                'requestType' => 'DELETION',
                'tabLabel' => 'Deletion',
            ],
            'archive' => [
                'pageTitle' => 'Approve Mandate Archive',
                'pageSubtitle' => 'Archive requests help move completed or inactive mandates out of the day-to-day portfolio while preserving their historical record.',
                'guidanceText' => 'Use archive approval for mandates that no longer need active operational attention but should remain available for reporting and audit history.',
                'tableTitle' => 'Pending Archive Requests',
                'tableHint' => 'Select one or more requests below, then approve them in a single step.',
                'actionRoute' => 'payment-mandate.approve-archive',
                'actionName' => 'approve_request',
                'actionValue' => 'approve_request',
                'buttonLabel' => 'Approve Archive',
                'buttonIcon' => 'bx-archive-in',
                'requestLabel' => 'Archive',
                'emptyMessage' => 'No archive requests at the moment!',
                'requestType' => 'ARCHIVE',
                'tabLabel' => 'Archive',
            ],
            'closing' => [
                'pageTitle' => 'Approve Mandate Closing',
                'pageSubtitle' => 'Review closing requests for mandates that have reached the end of their operational lifecycle and should be marked as closed.',
                'guidanceText' => 'Approve closing only when the mandate has completed its intended lifecycle and there are no remaining operational actions expected on it.',
                'tableTitle' => 'Pending Closing Requests',
                'tableHint' => 'Select one or more requests below, then approve them in a single step.',
                'actionRoute' => 'payment-mandate.approve-closing',
                'actionName' => 'approve_closing',
                'actionValue' => 'approve_closing',
                'buttonLabel' => 'Approve Closing',
                'buttonIcon' => 'bx-window-close',
                'requestLabel' => 'Closing',
                'emptyMessage' => 'No closing requests at the moment!',
                'requestType' => 'CLOSING',
                'tabLabel' => 'Closing',
            ],
        ];
    }

    private function renderApprovalPage(string $tabKey)
    {
        $tabs = $this->approvalTabConfig();
        abort_unless(isset($tabs[$tabKey]), 404);

        $requestCounts = DebitOrderRequest::query()
            ->where('status', 'PENDING')
            ->whereIn('request_type', ['CANCELLATION', 'DELETION', 'ARCHIVE', 'CLOSING'])
            ->selectRaw('request_type, COUNT(*) as total')
            ->groupBy('request_type')
            ->pluck('total', 'request_type');

        foreach ($tabs as $key => &$tab) {
            $tab['count'] = (int) ($requestCounts[$tab['requestType']] ?? 0);
        }
        unset($tab);

        $config = $tabs[$tabKey];
        $dd_requests = DebitOrderRequest::with('debitOrder')
            ->where('request_type', $config['requestType'])
            ->where('status', 'PENDING')
            ->get();

        return view('payment_mandate.approvals', array_merge($config, [
            'title' => 'Mandate Approvals',
            'dd_requests' => $dd_requests,
            'approvalTabs' => $tabs,
            'activeTab' => $tabKey,
        ]));
    }

    public function approvals(string $tab = 'cancellation')
    {
        return $this->renderApprovalPage($tab);
    }

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

        return $this->renderApprovalPage('cancellation');
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

        return $this->renderApprovalPage('deletion');
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

        return $this->renderApprovalPage('closing');
    }

    public function request_archive($uuid)
    {
        $debitOrder = DebitOrder::where(['uuid' => $uuid])->first();
        $this->paymentMandateWorkflowService->archive($debitOrder, Auth::id());

        return response()->json(['success' => true, 'success_msg' => 'Archiving payment mandate request sent.'], 200);
    }

    public function approve_archive(Request $request)
    {
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

        return $this->renderApprovalPage('archive');
    }
}
