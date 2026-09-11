<?php

namespace App\Services;

use App\Models\CompanyUser;
use App\Models\PaymentSettlement;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardReportingQueryService
{
    public function remember(User $user, string $namespace, array $filters, Closure $callback, int $seconds = 300)
    {
        $normalized = $this->normalizeCacheFilters($filters);
        $key = 'reporting:v2:' . $namespace . ':user:' . $user->id . ':' . sha1(json_encode($normalized));

        return Cache::remember($key, now()->addSeconds($seconds), $callback);
    }

    public function lastUpdatedAt(): ?Carbon
    {
        $value = Cache::remember('reporting:v2:data-freshness', now()->addMinutes(5), function () {
            return collect([
                DB::table('payment_settlements')->max('updated_at'),
                DB::table('installments')->max('updated_at'),
                DB::table('debit_orders')->max('updated_at'),
            ])->filter()->max();
        });

        return $value ? Carbon::parse($value) : null;
    }

    private function normalizeCacheFilters(array $filters): array
    {
        ksort($filters);

        return array_map(function ($value) {
            if (is_array($value)) {
                ksort($value);
            }

            return $value;
        }, $filters);
    }

    public function mandates(User $user, array $filters = []): Builder
    {
        return $this->query('vw_dashboard_mandates', $user, $filters, 'mandate')
            ->where('partner_approved', 1);
    }

    public function expectedCollections(User $user, array $filters = []): Builder
    {
        return $this->query('vw_dashboard_expected_collections', $user, $filters, 'expected')
            ->where('partner_approved', 1)
            ->where('mandate_approved', 1);
    }

    public function paymentCollections(User $user, array $filters = []): Builder
    {
        $query = $this->query('vw_dashboard_payment_collections', $user, $filters, 'payment')
            ->where('applied_to_balances', 1)
            ->where('reconciliation_status', 'POSTED')
            ->whereNotNull('effective_payment_date');

        return PaymentSettlement::constrainReportable($query, 'collection_status', 'paid_amount');
    }

    public function collectionPostings(User $user, array $filters = []): Builder
    {
        return $this->query('vw_dashboard_collection_postings', $user, $filters, 'posting')
            ->where('batch_pushed', 1)
            ->whereNotNull('posted_at');
    }

    private function query(string $view, User $user, array $filters, string $type): Builder
    {
        $query = DB::table($view);

        $this->applyUserScope($query, $user);
        $this->applyFilters($query, $filters, $type);

        return $query;
    }

    private function applyUserScope(Builder $query, User $user): void
    {
        if ($user->hasRole('Partner')) {
            $companyId = CompanyUser::where('user_id', $user->id)->value('company_id');
            $companyId ? $query->where('company_id', $companyId) : $query->whereRaw('1 = 0');

            return;
        }

        if ($user->hasRole('Company_Approver')) {
            $partnerIds = $user->partners
                ->where('authorize', 1)
                ->pluck('partner_id')
                ->values()
                ->all();

            $partnerIds ? $query->whereIn('partner_id', $partnerIds) : $query->whereRaw('1 = 0');

            return;
        }

        if ($user->hasRole('Company_User')) {
            $query->where('created_by', $user->id);
        }
    }

    private function applyFilters(Builder $query, array $filters, string $type): void
    {
        $query->when($filters['channel'] ?? null, fn ($query, $channel) => $query->where('channel', $channel));
        $query->when($filters['partner_id'] ?? null, fn ($query, $partnerId) => $query->where('partner_id', $partnerId));
        $query->when(
            $filters['corporate_reference'] ?? null,
            fn ($query, $reference) => $query->where('corporate_reference', $reference)
        );
        $query->when($filters['mandate_type'] ?? null, function ($query, $mandateType) {
            $query->whereRaw('LOWER(mandate_type) = ?', [strtolower((string) $mandateType)]);
        });
        $query->when($filters['keyword'] ?? null, function ($query, $keyword) use ($type) {
            $lowered = strtolower((string) $keyword);

            $query->where(function ($searchQuery) use ($lowered, $type) {
                $searchQuery->whereRaw('LOWER(mandate_reference) LIKE ?', ["%{$lowered}%"])
                    ->orWhereRaw('LOWER(corporate_reference) LIKE ?', ["%{$lowered}%"])
                    ->orWhereRaw('LOWER(partner_name) LIKE ?', ["%{$lowered}%"])
                    ->orWhereRaw('LOWER(account_name) LIKE ?', ["%{$lowered}%"])
                    ->orWhereRaw('LOWER(account_number) LIKE ?', ["%{$lowered}%"]);

                if ($type === 'payment') {
                    $searchQuery->orWhereRaw('LOWER(collection_payment_reference) LIKE ?', ["%{$lowered}%"])
                        ->orWhereRaw('LOWER(external_reference) LIKE ?', ["%{$lowered}%"]);
                } elseif ($type === 'posting') {
                    $searchQuery->orWhereRaw('LOWER(collection_reference) LIKE ?', ["%{$lowered}%"])
                        ->orWhereRaw('LOWER(posting_status) LIKE ?', ["%{$lowered}%"]);
                } elseif ($type === 'expected') {
                    $searchQuery->orWhereRaw('LOWER(installment_payment_reference) LIKE ?', ["%{$lowered}%"]);
                }
            });
        });
    }
}
