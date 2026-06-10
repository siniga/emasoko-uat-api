<?php

use App\Models\Partner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('lifecycle_status')->nullable()->after('status')->index();
        });

        Partner::query()->chunkById(200, function ($partners) {
            foreach ($partners as $partner) {
                $partner->lifecycle_status = $partner->resolved_lifecycle_status;
                $partner->save();
            }
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropIndex(['lifecycle_status']);
            $table->dropColumn('lifecycle_status');
        });
    }
};
