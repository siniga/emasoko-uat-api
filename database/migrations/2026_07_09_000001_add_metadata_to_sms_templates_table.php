<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMetadataToSmsTemplatesTable extends Migration
{
    public function up()
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('sms_templates', 'code')) {
                $table->string('code', 80)->nullable()->after('name');
            }

            if (!Schema::hasColumn('sms_templates', 'channel')) {
                $table->string('channel', 20)->default('ALL')->after('code');
            }

            if (!Schema::hasColumn('sms_templates', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('message');
            }
        });
    }

    public function down()
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            if (Schema::hasColumn('sms_templates', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('sms_templates', 'channel')) {
                $table->dropColumn('channel');
            }

            if (Schema::hasColumn('sms_templates', 'code')) {
                $table->dropColumn('code');
            }
        });
    }
}
