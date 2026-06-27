<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\OtpRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentMandateActionController extends BasePaymentMandateController
{
    public function resend_otp(Request $request)
    {
        return response()->json(
            $this->paymentMandateWorkflowService->resendOtp($request->uuid, Auth::id()),
            200
        );
    }

    public function verify_otp(Request $request)
    {
        $request->validate([
            'uuid' => 'required|uuid|exists:debit_orders,uuid',
            'otpPin' => 'required|string|min:4|max:10',
        ]);

        return response()->json(
            $this->paymentMandateWorkflowService->verifyOtp($request->uuid, $request->otpPin, Auth::id()),
            200
        );
    }

    public function fetch_status(Request $request)
    {
        return response()->json(
            $this->paymentMandateWorkflowService->fetchStatus($request->uuid),
            200
        );
    }

    public function cancel_mandate(Request $request)
    {
        $debitOrder = DebitOrder::where('uuid', $request->uuid)->firstOrFail();
        $result = $this->paymentMandateWorkflowService->cancel(
            $debitOrder,
            Auth::id(),
            (string) $request->remarks
        );

        return response()->json($result, 200);
    }

    public function show($id)
    {
        $title = 'Debit Order Information';
        $debit_order = DebitOrder::with([
            'customerAccount.accountType',
            'customerAccount.account',
            'customerAccount.customer',
            'partner',
            'userCreated',
            'cancellationStarted',
            'cancellationApproved',
            'installments',
            'debitOrderRequests.requestedBy',
            'debitOrderRequests.approvedBy',
        ])->where(['uuid' => $id])->firstOrFail();
        $customer_account = $debit_order->customerAccount ?? null;
        $installments = $debit_order->installments->sortBy('payment_date')->values();

        $installmentStats = [
            'total' => $installments->count(),
            'paid' => $installments->filter(fn ($item) => in_array(strtoupper($item->status ?? ''), ['CLOSED', 'SUCCESS'], true))->count(),
            'partial' => $installments->filter(fn ($item) => strtoupper($item->status ?? '') === 'PARTIAL_PAID')->count(),
            'unpaid' => $installments->filter(fn ($item) => in_array(strtoupper($item->status ?? 'OPEN'), ['OPEN', 'FAILED', 'FUNDSCHECK'], true))->count(),
            'installment_amount' => (float) $installments->sum('installment_amount'),
            'paid_amount' => (float) $installments->sum('current_balance'),
            'outstanding_amount' => (float) $installments->sum('outstanding_amount'),
            'next_due_date' => optional(
                $installments->first(fn ($item) => in_array(strtoupper($item->status ?? 'OPEN'), ['OPEN', 'FAILED', 'FUNDSCHECK'], true))
            )->payment_date,
        ];
        $canReopenMandate = Auth::user()->hasRole(['admin'])
            && $this->paymentMandateWorkflowService->canReopenClosedMandate($debit_order);
        $lifecycleAudit = $this->lifecycleAudit($debit_order);

        return view('payment_mandate.show', compact(
            'title',
            'debit_order',
            'customer_account',
            'installments',
            'installmentStats',
            'canReopenMandate',
            'lifecycleAudit'
        ));
    }

    private function lifecycleAudit(DebitOrder $debitOrder): ?array
    {
        $lifecycleStatus = $debitOrder->resolved_lifecycle_status;

        if ($lifecycleStatus === DebitOrder::LIFECYCLE_CANCELLED) {
            $request = $debitOrder->debitOrderRequests
                ->where('request_type', 'CANCELLATION')
                ->where('status', 'APPROVED')
                ->sortByDesc('approved_at')
                ->first();

            return [
                'action' => 'Cancelled',
                'user' => $debitOrder->cancellationApproved?->name
                    ?? $request?->approvedBy?->name
                    ?? $debitOrder->cancellationStarted?->name
                    ?? $request?->requestedBy?->name
                    ?? 'System',
                'at' => $debitOrder->cancellationApprovedAt
                    ?? $request?->approved_at
                    ?? $debitOrder->updated_at,
            ];
        }

        if ($lifecycleStatus === DebitOrder::LIFECYCLE_CLOSED) {
            $request = $debitOrder->debitOrderRequests
                ->where('request_type', 'CLOSING')
                ->where('status', 'APPROVED')
                ->sortByDesc('approved_at')
                ->first();

            return [
                'action' => 'Closed',
                'user' => $request?->approvedBy?->name
                    ?? $request?->requestedBy?->name
                    ?? 'System',
                'at' => $request?->approved_at ?? $debitOrder->updated_at,
            ];
        }

        return null;
    }

    public function reopen_mandate(string $uuid)
    {
        abort_unless(Auth::user()->hasRole(['admin']), 403);

        $debitOrder = DebitOrder::where('uuid', $uuid)->firstOrFail();
        $this->paymentMandateWorkflowService->reopenClosedMandate($debitOrder);

        return redirect()
            ->route('payment-mandate.show', $debitOrder->uuid)
            ->with('success', 'Payment mandate reopened and activated for collection.');
    }

    public function edit($id)
    {
        $title = 'Payment Mandate';
        $debit_order = DebitOrder::where(['uuid' => $id])->first();
        $account_types = AccountType::all();
        $accounts = Account::all();

        return view('payment_mandate.edit', compact('title', 'debit_order', 'account_types', 'accounts'));
    }

    public function update(Request $request, $id)
    {
        $min_installment_amount = floatval(str_replace(',', '', $request->input('min_installment_amount')));
        $max_installment_amount = floatval(str_replace(',', '', $request->input('max_installment_amount')));

        $this->validate(
            $request,
            [
                'account_type_id' => 'required',
                'account_id' => 'required',
                'account_name' => 'required_if:account_type_id,1',
                'account_number' => 'required_if:account_type_id,1',
                'phone' => new \App\Rules\PhoneNumber(),
                'account_number_phone' => new \App\Rules\PhoneNumber(),
                'installment_amount' => ['required_if:debit_type,==,FIXED'],
                'min_installment_amount' => ['required_if:debit_type,==,DYNAMIC'],
                'max_installment_amount' => [
                    'required_if:debit_type,==,DYNAMIC',
                    function ($attribute, $value, $fail) use ($min_installment_amount, $max_installment_amount) {
                        if ($min_installment_amount > $max_installment_amount) {
                            return $fail('Maximum deduction should not be less than minimum deduction amount');
                        }
                    },
                ],
            ],
            [
                'account_type_id.required' => 'Payment channel required',
                'account_id.required' => 'Bank/MNO name required',
                'account_name.required_if' => 'Name of account holder required',
                'account_number.required_if' => 'Account number required',
                'phone.required_if' => 'Phone connected to bank required',
                'account_number_phone.required_if' => 'MNO Phone required',
            ]
        );

        $debit_order = DebitOrder::findOrFail($id);
        $debit_order->total_amount = floatval(str_replace(',', '', $request->input('loan_amount')));
        $debit_order->debit_type = $request->input('debit_type');

        if ($request->input('debit_type') == 'FIXED') {
            $debit_order->installment_amount = floatval(str_replace(',', '', $request->input('installment_amount')));
        } elseif ($request->input('debit_type') == 'DYNAMIC') {
            $debit_order->max_installment_amount = floatval(str_replace(',', '', $request->input('max_installment_amount')));
            $debit_order->min_installment_amount = floatval(str_replace(',', '', $request->input('min_installment_amount')));
        }

        $debit_order->periodicity = $request->input('periodicity');
        $debit_order->installment_number = $request->input('number_of_installment');
        $debit_order->start_date = $request->input('start_date');
        $debit_order->deduction_end_date = $request->input('deduction_end');

        if ($debit_order->created_by == null) {
            $debit_order->created_by = Auth::user()->id;
        }

        if ($request->input('deduction_end') === 'totalLoanCovered') {
            $debit_order->end_date = date('Y-m-d', strtotime($request->input('start_date') . '+2 year'));
        } elseif ($request->input('deduction_end') == 'specificDate') {
            $debit_order->end_date = $request->input('end_date');
        }

        if ($request->hasFile('evidence_of_aggrement')) {
            $path = $request->file('evidence_of_aggrement');
            $docsName = $path->getClientOriginalName();

            $path->move('assets/uploads/docs', $docsName);
            $debit_order->evidence_of_aggrement = $docsName;
        }

        $debit_order->save();

        return redirect()->route('payment-mandate.lists')->with('success', 'Payment mandate updated');
    }

    public function destroy($uuid)
    {
        DB::transaction(function () use ($uuid) {
            $debitOrder = DebitOrder::where('uuid', $uuid)->firstOrFail();
            $debitOrder->installments()->delete();
            $debitOrder->debitOrderRequests()->delete();
            $debitOrder->collectionDebitOrders()->delete();
            $debitOrder->delete();
        });

        return redirect()->route('payment-mandate.lists')->with('danger', 'Payment mandate deleted!');
    }

    public function unarchieve($id)
    {
        $debit_order = DebitOrder::where(['uuid' => $id])->first();
        $debit_order->deleted = 0;
        $debit_order->save();

        return redirect()->route('payment-mandate.lists')->with('danger', 'Payment mandate unarchived');
    }

    public function update_installments()
    {
        $debit_orders = DebitOrder::where(['periodicity' => 'MONTHLY', 'debit_type' => 'DYNAMIC'])->get();

        foreach ($debit_orders as $val) {
            $dayOfMonth = date('d', strtotime($val->start_date));
            $installments = Installment::where(['debit_order_id' => $val->id])->get();

            foreach ($installments as $installment) {
                $year = date('Y', strtotime($installment->payment_date));
                $month = date('m', strtotime($installment->payment_date));
                $date = Carbon::createFromDate($year, $month, $dayOfMonth);

                $up_installment = Installment::findOrFail($installment->id);
                $up_installment->payment_date = $date->format('Y-m-d');
                $up_installment->save();
            }
        }

        return response()->json(['success' => true, 'success_msg' => 'Installments updated'], 200);
    }

    public function update_mandates()
    {
        $debit_orders = DebitOrder::whereIn('id', [5714, 5720])->get();

        foreach ($debit_orders as $debit_order) {
            $this->deductionService->createInstallment($debit_order);
        }

        return response()->json(['success' => true, 'message' => 'DD updated']);
    }

    public function sendRemainder($id)
    {
        $title = 'Send Remainder';
        $debit_order = DebitOrder::findOrFail($id);
        $account_types = AccountType::all();
        $accounts = Account::all();

        return view('payment_mandate.send_remainder', compact('title', 'debit_order', 'account_types', 'accounts'));
    }
}
