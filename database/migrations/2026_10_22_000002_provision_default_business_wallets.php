<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('partners')->select('id')->orderBy('id')->chunkById(500, function ($partners) {
            foreach ($partners as $partner) {
                // Preserve existing wallet IDs, statuses and timestamps on retries.
                $timestamp = now('UTC')->format('Y-m-d H:i:s');
                DB::table('wallets')->insertOrIgnore([
                    'id' => (string) Str::uuid(),
                    'partner_id' => $partner->id,
                    'currency' => 'TZS',
                    'status' => 'ACTIVE',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Provisioned wallets may hold financial records; never delete them on rollback.
    }
};
