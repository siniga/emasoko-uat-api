<?php

use App\Models\Account;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AlterTableAddColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table("partner_accounts", function (Blueprint $table) {
            $table->tinyInteger("is_verified")->default(0)->after("remarks");
            $table->timestamp("verified_at")->nullable()->after("is_verified");
            $table->timestamp("rejected_at")->nullable()->after("verified_at");
            $table->mediumText("response_sms")->nullable()->after("rejected_at");
            $table->tinyInteger("send_verification")->default(0)->after("response_sms");
        });


        Schema::table("accounts", function (Blueprint $table) {
            $table->string("slug")->nullable();
        });

        foreach (Account::get() as $account) {
            $account->slug  = Str::slug($account->name);
            $account->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
