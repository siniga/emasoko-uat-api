<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_dashboard_collection_postings');

        DB::statement(<<<'SQL'
            CREATE VIEW vw_dashboard_collection_postings AS
            SELECT
                collection_debit_orders.id AS posting_id,
                collection_debit_orders.collection_batch_id,
                collection_debit_orders.debit_order_id,
                collection_debit_orders.installment_id,
                collection_debit_orders.payment_at,
                collection_debit_orders.payment_month,
                collection_debit_orders.status AS posting_status,
                collection_debit_orders.bank_status,
                collection_debit_orders.bank_message,
                collection_batches.collectionReference AS collection_reference,
                collection_batches.lifecycle_status AS batch_lifecycle_status,
                collection_batches.pushed AS batch_pushed,
                collection_batches.deleted AS batch_deleted,
                COALESCE(
                    collection_debit_orders.pushed_at,
                    collection_batches.last_pushed_at,
                    collection_batches.created_at
                ) AS posted_at,
                DATE(COALESCE(
                    collection_debit_orders.pushed_at,
                    collection_batches.last_pushed_at,
                    collection_batches.created_at
                )) AS posted_date,
                YEAR(COALESCE(
                    collection_debit_orders.pushed_at,
                    collection_batches.last_pushed_at,
                    collection_batches.created_at
                )) AS posted_year,
                MONTH(COALESCE(
                    collection_debit_orders.pushed_at,
                    collection_batches.last_pushed_at,
                    collection_batches.created_at
                )) AS posted_month,
                mandates.mandate_reference,
                mandates.partner_id,
                mandates.partner_uuid,
                mandates.corporate_reference,
                mandates.partner_name,
                mandates.company_id,
                mandates.partner_approved,
                mandates.customer_account_id,
                mandates.customer_id,
                mandates.account_id,
                mandates.account_name,
                mandates.account_number,
                mandates.phone,
                mandates.channel_name,
                mandates.channel_slug,
                mandates.channel,
                mandates.source,
                mandates.debit_type,
                mandates.mandate_type,
                mandates.bank_sponsor_partner_id,
                mandates.effective_bank_partner_id,
                mandates.created_by
            FROM collection_debit_orders
            INNER JOIN collection_batches
                ON collection_batches.id = collection_debit_orders.collection_batch_id
            INNER JOIN vw_dashboard_mandates mandates
                ON mandates.debit_order_id = collection_debit_orders.debit_order_id
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_dashboard_collection_postings');
    }
};
