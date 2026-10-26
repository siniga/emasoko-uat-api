<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Models\PartnerUser;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function account(Request $request): JsonResponse
    {
        $partnerId = $this->authorisedPartnerId($request);
        if ($partnerId instanceof JsonResponse) {
            return $partnerId;
        }

        // Wallets are a default business feature, including for legacy businesses.
        return $this->walletResponse(Wallet::forPartner($partnerId));
    }

    private function authorisedPartnerId(Request $request): int|JsonResponse
    {
        if ((int) $request->user()->active !== 1) {
            return $this->walletError('Your account is inactive.', 403);
        }

        $validated = $request->validate(['partner_id' => ['nullable', 'integer', 'min:1']]);
        $memberships = PartnerUser::query()
            ->where('user_id', $request->user()->id)
            ->where('authorize', 1)
            ->whereHas('partner');

        if (isset($validated['partner_id'])) {
            $partnerId = (int) $validated['partner_id'];
            if (!$memberships->where('partner_id', $partnerId)->exists()) {
                return $this->walletError('You do not have access to this business wallet.', 403);
            }

            return $partnerId;
        }

        $partnerIds = $memberships->distinct()->pluck('partner_id');
        if ($partnerIds->isEmpty()) {
            return $this->walletError('You do not have an authorised business linked to your account.', 403);
        }
        if ($partnerIds->count() > 1) {
            return $this->walletError('Choose a business using partner_id from your authorised businesses.', 422);
        }

        return (int) $partnerIds->first();
    }

    private function walletResponse(Wallet $wallet): JsonResponse
    {
        return response()->json([
            'success' => true,
            'response_code' => 200,
            'message' => 'Wallet fetched successfully.',
            'data' => [
                'wallet' => [
                    'id' => $wallet->id,
                    'partner_id' => $wallet->partner_id,
                    'currency' => $wallet->currency,
                    'status' => $wallet->status,
                    'created_at' => \Illuminate\Support\Carbon::parse($wallet->getRawOriginal('created_at'), 'UTC')->toIso8601String(),
                ],
            ],
        ])->header('Cache-Control', 'no-store, private');
    }

    private function walletError(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'response_code' => $status,
            'message' => $message,
            'data' => [],
        ], $status)->header('Cache-Control', 'no-store, private');
    }

    /**
     * Entry context only. Wallet balances and transactions are added in later parts.
     */
    public function context(Request $request): JsonResponse
    {
        $user = $request->user();

        if ((int) $user->active !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive.',
                'data' => [],
            ], 403)->header('Cache-Control', 'no-store, private');
        }

        // Never trust a partner ID supplied by the caller to establish access.
        $businesses = PartnerUser::query()
            ->with('partner:id,uuid,business_name,reference')
            ->where('user_id', $user->id)
            ->where('authorize', 1)
            ->get()
            ->filter(fn (PartnerUser $membership) => $membership->partner !== null)
            ->map(fn (PartnerUser $membership) => [
                'id' => $membership->partner->id,
                'uuid' => $membership->partner->uuid,
                'name' => $membership->partner->business_name,
                'reference' => $membership->partner->reference,
            ])
            ->unique('id')
            ->values();

        if ($businesses->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have an authorised business linked to your account.',
                'data' => [],
            ], 403)->header('Cache-Control', 'no-store, private');
        }

        return response()->json([
            'success' => true,
            'response_code' => 200,
            'message' => 'Wallet context fetched successfully.',
            'data' => [
                'currency' => 'TZS',
                'user' => ['id' => $user->id, 'name' => $user->name],
                'businesses' => $businesses,
            ],
        ])->header('Cache-Control', 'no-store, private');
    }
}
