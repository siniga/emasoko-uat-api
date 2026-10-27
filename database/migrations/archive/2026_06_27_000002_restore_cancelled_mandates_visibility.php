<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('debit_orders')
            ->where(function ($query) {
                $query->where('cancelled', 1)
                    ->orWhere('lifecycle_status', 'cancelled');
            })
            ->update([
                'deleted' => 0,
                'cancelled' => 1,
                'lifecycle_status' => 'cancelled',
            ]);
    }

    public function down(): void
    {
        // Cancellation and deletion are independent states; do not hide mandates again.
    }
};
