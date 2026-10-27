<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Apply existing foreign keys after every referenced table has been created.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
ALTER TABLE `debit_orders` ADD CONSTRAINT `debit_orders_cancellationapprovedby_foreign` FOREIGN KEY (`cancellationApprovedBy`) REFERENCES `users` (`id`)
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE `debit_orders` ADD CONSTRAINT `debit_orders_cancellationstartedby_foreign` FOREIGN KEY (`cancellationStartedBy`) REFERENCES `users` (`id`)
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE `mandate_service_partner` ADD CONSTRAINT `mandate_service_partner_mandate_service_id_foreign` FOREIGN KEY (`mandate_service_id`) REFERENCES `mandate_services` (`id`) ON DELETE CASCADE
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE `perm_methods` ADD CONSTRAINT `perm_methods_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `perm_classes` (`id`) ON DELETE CASCADE
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE `perm_roles` ADD CONSTRAINT `perm_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE `perm_users` ADD CONSTRAINT `perm_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE `role_user` ADD CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE `role_user` ADD CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE `wallets` ADD CONSTRAINT `wallets_partner_id_foreign` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`)
SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
ALTER TABLE `wallets` DROP FOREIGN KEY `wallets_partner_id_foreign`
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE `role_user` DROP FOREIGN KEY `role_user_user_id_foreign`
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE `role_user` DROP FOREIGN KEY `role_user_role_id_foreign`
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE `perm_users` DROP FOREIGN KEY `perm_users_user_id_foreign`
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE `perm_roles` DROP FOREIGN KEY `perm_roles_role_id_foreign`
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE `perm_methods` DROP FOREIGN KEY `perm_methods_class_id_foreign`
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE `mandate_service_partner` DROP FOREIGN KEY `mandate_service_partner_mandate_service_id_foreign`
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE `debit_orders` DROP FOREIGN KEY `debit_orders_cancellationstartedby_foreign`
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE `debit_orders` DROP FOREIGN KEY `debit_orders_cancellationapprovedby_foreign`
SQL);
    }
};
