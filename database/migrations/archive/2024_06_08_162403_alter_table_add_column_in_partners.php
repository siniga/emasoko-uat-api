<?php

use App\Models\Currency;
use App\Models\Partner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableAddColumnInPartners extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string("reference")->nullable();
            $table->string("short_code")->nullable();
        });

        foreach (Partner::get() as $partner) {
            $partner->reference = Partner::document_number("reference", "CO", 6, true, false);
            $acronym = "";
            $words = preg_split("/[\s,_-]+/", $partner->business_name);
            foreach ($words as $w) {
                $acronym .= mb_substr($w, 0, 1);
            }
            $partner->short_code = $acronym;
            $partner->save();
        }


        Schema::create('currencies', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("name");
            $table->string("short_code");
            $table->string("symbol");
            $table->timestamps();
        });

        $currencies = [
            [
                "name" => "Tanzania Shilling",
                "short_code" => "TZS",
                "symbol" => "TZS",
            ],
            [
                "name" => "US Dollar",
                "short_code" => "USD",
                "symbol" => "$",
            ],
            [
                "name" => "Euro",
                "short_code" => "Euro",
                "symbol" => "Euro",
            ]
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate($currency);
        }

        Schema::table('partner_accounts', function (Blueprint $table) {
            $table->string("sort_code")->nullable();
            $table->string("currency_id")->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string("TIN_number")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn("TIN_number");
        });
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn("reference");
            $table->dropColumn("short_code");
        });
        Schema::dropIfExists("currencies");
        Schema::table('partner_accounts', function (Blueprint $table) {
            $table->dropColumn("sort_code");
            $table->dropColumn("currency_id");
        });
    }
}
