<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->index()->after('id');
            }

            if (!Schema::hasColumn('users', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->index()->after('created_by');
            }
        });

        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        DB::table('audit_logs')
            ->where('auditable_type', 'App\\Models\\User')
            ->where('event', 'created')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunkById(500, function ($audits) {
                foreach ($audits as $audit) {
                    DB::table('users')
                        ->where('id', $audit->auditable_id)
                        ->whereNull('created_by')
                        ->update(['created_by' => $audit->user_id]);
                }
            });

        DB::table('audit_logs')
            ->where('auditable_type', 'App\\Models\\User')
            ->where('event', 'updated')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunkById(500, function ($audits) {
                foreach ($audits as $audit) {
                    DB::table('users')
                        ->where('id', $audit->auditable_id)
                        ->update(['updated_by' => $audit->user_id]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'updated_by')) {
                $table->dropColumn('updated_by');
            }

            if (Schema::hasColumn('users', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
};
