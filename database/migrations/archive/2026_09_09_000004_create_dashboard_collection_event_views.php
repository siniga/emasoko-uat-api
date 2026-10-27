<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_dashboard_payment_collections');
        DB::statement('DROP VIEW IF EXISTS vw_dashboard_expected_collections');

        DB::statement(<<<'SQL'
            CREATE VIEW vw_dashboard_expected_collections AS
            SELECT
                installments.id AS installment_id,
                installments.installment_order,
                installments.installment_amount,
                installments.last_paid_amount,
                installments.current_balance,
                installments.outstanding_amount,
                installments.status AS collection_status,
                installments.remarks AS collection_remarks,
                installments.payment_reference AS installment_payment_reference,
                installments.payment_date,
                DATE(installments.payment_date) AS expected_date,
                YEAR(installments.payment_date) AS expected_year,
                MONTH(installments.payment_date) AS expected_month,
                installments.created AS collection_created,
                installments.created_at AS installment_created_at,
                installments.updated_at AS installment_updated_at,
                mandates.debit_order_id,
                mandates.debit_order_uuid,
                mandates.mandate_reference,
                mandates.partner_id,
                mandates.partner_uuid,
                mandates.corporate_reference,
                mandates.partner_name,
                mandates.company_id,
                mandates.partner_approved,
                mandates.partner_lifecycle_status,
                mandates.customer_account_id,
                mandates.customer_id,
                mandates.account_id,
                mandates.account_type_id,
                mandates.account_name,
                mandates.account_number,
                mandates.phone,
                mandates.channel_name,
                mandates.channel_slug,
                mandates.channel,
                mandates.source,
                mandates.debit_type,
                mandates.mandate_type,
                mandates.total_amount AS mandate_total_amount,
                mandates.paid_amount AS mandate_paid_amount,
                mandates.outstanding_amount AS mandate_outstanding_amount,
                mandates.current_balance AS mandate_current_balance,
                mandates.mandate_approved,
                mandates.mandate_status_code,
                mandates.lifecycle_status,
                mandates.contract_status,
                mandates.cancelled,
                mandates.closed,
                mandates.archived,
                mandates.deleted,
                mandates.bank_sponsor_partner_id,
                mandates.effective_bank_partner_id,
                mandates.created_by,
                mandates.mandate_created_date,
                mandates.mandate_created_year,
                mandates.mandate_created_month,
                mandates.created_at AS mandate_created_at,
                mandates.updated_at AS mandate_updated_at
            FROM installments
            INNER JOIN vw_dashboard_mandates mandates
                ON mandates.debit_order_id = installments.debit_order_id
        SQL);

        DB::statement(<<<'SQL'
            CREATE VIEW vw_dashboard_payment_collections AS
            SELECT
                payment_settlements.id AS settlement_id,
                payment_settlements.collection_batch_id,
                payment_settlements.collection_debit_order_id,
                payment_settlements.installment_id,
                payment_settlements.payment_reference AS collection_payment_reference,
                payment_settlements.external_reference,
                payment_settlements.raw_status,
                payment_settlements.normalized_status AS collection_status,
                payment_settlements.settlement_type,
                payment_settlements.applied_to_balances,
                payment_settlements.reconciliation_status,
                payment_settlements.backfill_run_id,
                payment_settlements.expected_amount,
                payment_settlements.paid_amount,
                payment_settlements.effective_payment_date,
                DATE(payment_settlements.effective_payment_date) AS collected_date,
                YEAR(payment_settlements.effective_payment_date) AS collected_year,
                MONTH(payment_settlements.effective_payment_date) AS collected_month,
                payment_settlements.transaction_at,
                payment_settlements.remarks AS collection_remarks,
                payment_settlements.created_at AS settlement_created_at,
                payment_settlements.updated_at AS settlement_updated_at,
                mandates.debit_order_id,
                mandates.debit_order_uuid,
                mandates.mandate_reference,
                mandates.partner_id,
                mandates.partner_uuid,
                mandates.corporate_reference,
                mandates.partner_name,
                mandates.company_id,
                mandates.partner_approved,
                mandates.partner_lifecycle_status,
                mandates.customer_account_id,
                mandates.customer_id,
                mandates.account_id,
                mandates.account_type_id,
                mandates.account_name,
                mandates.account_number,
                mandates.phone,
                mandates.channel_name,
                mandates.channel_slug,
                mandates.channel,
                mandates.source,
                mandates.debit_type,
                mandates.mandate_type,
                mandates.total_amount AS mandate_total_amount,
                mandates.paid_amount AS mandate_paid_amount,
                mandates.outstanding_amount AS mandate_outstanding_amount,
                mandates.current_balance AS mandate_current_balance,
                mandates.mandate_approved,
                mandates.mandate_status_code,
                mandates.lifecycle_status,
                mandates.contract_status,
                mandates.cancelled,
                mandates.closed,
                mandates.archived,
                mandates.deleted,
                mandates.bank_sponsor_partner_id,
                mandates.effective_bank_partner_id,
                mandates.created_by,
                mandates.mandate_created_date,
                mandates.mandate_created_year,
                mandates.mandate_created_month,
                mandates.created_at AS mandate_created_at,
                mandates.updated_at AS mandate_updated_at
            FROM payment_settlements
            INNER JOIN vw_dashboard_mandates mandates
                ON mandates.debit_order_id = payment_settlements.debit_order_id
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_dashboard_payment_collections');
        DB::statement('DROP VIEW IF EXISTS vw_dashboard_expected_collections');
    }
};
