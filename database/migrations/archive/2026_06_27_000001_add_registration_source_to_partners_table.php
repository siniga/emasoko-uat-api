<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('registration_source', 20)
                ->default('officer')
                ->after('lifecycle_status')
                ->index();
        });

        DB::table('partners')
            ->whereNull('registration_source')
            ->orWhere('registration_source', '')
            ->update(['registration_source' => 'officer']);
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropIndex(['registration_source']);
            $table->dropColumn('registration_source');
        });
    }
};
