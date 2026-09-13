<?php

use App\Models\PartnerAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_accounts', function (Blueprint $table) {
            $table->string('onboarding_status', 50)->nullable()->index()->after('status');
            $table->string('bank_response_code', 50)->nullable()->after('remarks');
            $table->uuid('bank_correlation_id')->nullable()->after('bank_response_code');
            $table->longText('bank_response_payload')->nullable()->after('bank_correlation_id');
            $table->timestamp('bank_submitted_at')->nullable()->after('bank_response_payload');
            $table->timestamp('bank_status_checked_at')->nullable()->after('bank_submitted_at');
        });

        DB::table('partner_accounts')
            ->select(['id', 'channel', 'is_verified', 'remarks', 'rejected_at', 'send_verification'])
            ->orderBy('id')
            ->chunkById(200, function ($accounts) {
                foreach ($accounts as $account) {
                    DB::table('partner_accounts')->where('id', $account->id)->update([
                        'onboarding_status' => PartnerAccount::inferOnboardingStatus(
                            $account->channel,
                            (bool) $account->is_verified,
                            $account->remarks,
                            $account->rejected_at !== null,
                            (int) $account->send_verification
                        ),
                    ]);
                }
            });

        DB::table('partners')->where('approved', 1)
            ->update(['lifecycle_status' => 'pending_account_activation']);

        DB::table('partners')->where('approved', 1)
            ->whereExists(function ($query) {
                $query->selectRaw('1')->from('partner_accounts')
                    ->whereColumn('partner_accounts.partner_id', 'partners.id');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('partner_accounts')
                    ->whereColumn('partner_accounts.partner_id', 'partners.id')
                    ->whereNotIn('partner_accounts.onboarding_status', [
                        PartnerAccount::ONBOARDING_ACTIVE,
                        PartnerAccount::ONBOARDING_NOT_REQUIRED,
                    ]);
            })
            ->update(['lifecycle_status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('partner_accounts', function (Blueprint $table) {
            $table->dropIndex(['onboarding_status']);
            $table->dropColumn([
                'onboarding_status',
                'bank_response_code',
                'bank_correlation_id',
                'bank_response_payload',
                'bank_submitted_at',
                'bank_status_checked_at',
            ]);
        });
    }
};
