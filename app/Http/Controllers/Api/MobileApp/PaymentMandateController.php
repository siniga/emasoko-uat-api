<?php

namespace App\Http\Controllers\Api\MobileApp;

use App\Http\Controllers\Api\ExternalApp\PaymentMandateController as ExternalPaymentMandateController;
use App\Http\Resources\MandateResource;
use App\Models\DebitOrder;
use App\Models\Installment;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

#[Group('Payment Mandates', description: 'Mobile APIs for creating, viewing, and managing payment mandates.', weight: 2)]
class PaymentMandateController extends ExternalPaymentMandateController
{
    public function summaries(Request $request): JsonResponse
    {
        $channel = $request->input('payment_channel', $request->input('channel'));
        $currentYear = (int) $request->input('year', now()->year);
        $currentMonth = (int) $request->input('month', now()->month);

        $mandates = DebitOrder::mandateApprovalStats($channel, $currentYear);
        $summaryMap = [
            DebitOrder::SUMMARY_PENDING => ['count' => 0, 'amount' => 0.0],
            DebitOrder::SUMMARY_APPROVED => ['count' => 0, 'amount' => 0.0],
            DebitOrder::SUMMARY_FAILED => ['count' => 0, 'amount' => 0.0],
        ];

        foreach ($mandates as $mandate) {
            $summary = match ((int) $mandate->approved) {
                1 => DebitOrder::SUMMARY_APPROVED,
                2 => DebitOrder::SUMMARY_FAILED,
                default => DebitOrder::SUMMARY_PENDING,
            };

            $summaryMap[$summary] = [
                'count' => (int) $mandate->number_of_mandate,
                'amount' => (float) $mandate->total_amount,
            ];
        }

        $thisMonth = Installment::thisMonthStats($currentMonth, $currentYear, $channel);
        $today = Installment::todayStats(now()->toDateString(), $channel);

        return $this->successResponse('Mobile summaries fetched successfully.', [
            'mandates' => [
                'pending' => $summaryMap[DebitOrder::SUMMARY_PENDING],
                'approved' => $summaryMap[DebitOrder::SUMMARY_APPROVED],
                'failed' => $summaryMap[DebitOrder::SUMMARY_FAILED],
                'total' => [
                    'count' => array_sum(array_column($summaryMap, 'count')),
                    'amount' => array_sum(array_column($summaryMap, 'amount')),
                ],
            ],
            'collections' => [
                'month' => [
                    'year' => $currentYear,
                    'month' => $currentMonth,
                    'expected_count' => (int) ($thisMonth->total_collections ?? 0),
                    'paid_count' => (int) ($thisMonth->successful_collections ?? 0),
                    'expected_amount' => (float) ($thisMonth->total_amount ?? 0),
                    'paid_amount' => (float) ($thisMonth->paid_amount ?? 0),
                ],
                'today' => [
                    'date' => now()->toDateString(),
                    'expected_count' => (int) ($today->total_collections ?? 0),
                    'paid_count' => (int) ($today->successful_collections ?? 0),
                    'expected_amount' => (float) ($today->total_amount ?? 0),
                    'paid_amount' => (float) ($today->paid_amount ?? 0),
                ],
            ],
        ]);
    }
}
