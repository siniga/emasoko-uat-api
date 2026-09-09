<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDashboardReportingViews extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('DROP VIEW IF EXISTS vw_dashboard_collections');
        DB::statement('DROP VIEW IF EXISTS vw_dashboard_mandates');

        $mandateTypeExpression = $this->coalescedColumnExpression('debit_orders', [
            'mandate_type',
            'mandate_service',
            'service_type',
        ], "'NORMAL'");

        $bankSponsorExpression = $this->coalescedColumnExpression('debit_orders', [
            'bank_sponsor_partner_id',
            'bank_partner_sponsor_id',
        ], 'NULL');

        $lifecycleExpression = $this->lifecycleExpression();
        $deletedExpression = $this->columnOrFallback('debit_orders', 'deleted', '0');
        $cancelledExpression = $this->columnOrFallback('debit_orders', 'cancelled', '0');
        $closedExpression = $this->columnOrFallback('debit_orders', 'closed', '0');
        $archivedExpression = $this->columnOrFallback('debit_orders', 'archived', '0');

        DB::statement("
            CREATE VIEW vw_dashboard_mandates AS
            SELECT
                debit_orders.id AS debit_order_id,
                {$this->columnOrNull('debit_orders', 'uuid')} AS debit_order_uuid,
                debit_orders.reference AS mandate_reference,
                debit_orders.partner_id,
                {$this->columnOrNull('partners', 'uuid')} AS partner_uuid,
                partners.reference AS corporate_reference,
                partners.business_name AS partner_name,
                partners.company_id,
                partners.approved AS partner_approved,
                {$this->columnOrNull('partners', 'lifecycle_status')} AS partner_lifecycle_status,
                debit_orders.customer_account_id,
                customer_accounts.customer_id,
                customer_accounts.account_id,
                customer_accounts.account_type_id,
                customer_accounts.account_name,
                customer_accounts.account_number,
                customer_accounts.phone,
                accounts.name AS channel_name,
                {$this->columnOrNull('accounts', 'slug')} AS channel_slug,
                NULL AS payment_reference,
                debit_orders.channel,
                {$this->columnOrNull('debit_orders', 'source')} AS source,
                debit_orders.debit_type,
                debit_orders.total_amount,
                debit_orders.paid_amount,
                debit_orders.outstanding_amount,
                debit_orders.current_balance,
                debit_orders.installment_amount,
                debit_orders.min_installment_amount,
                debit_orders.max_installment_amount,
                debit_orders.installment_number,
                debit_orders.periodicity,
                debit_orders.start_date,
                debit_orders.end_date,
                debit_orders.contract_status,
                debit_orders.status AS mandate_status_code,
                debit_orders.remarks AS mandate_remarks,
                debit_orders.approved AS mandate_approved,
                {$cancelledExpression} AS cancelled,
                {$closedExpression} AS closed,
                {$archivedExpression} AS archived,
                {$deletedExpression} AS deleted,
                {$lifecycleExpression} AS lifecycle_status,
                {$mandateTypeExpression} AS mandate_type,
                {$bankSponsorExpression} AS bank_sponsor_partner_id,
                COALESCE({$bankSponsorExpression}, debit_orders.partner_id) AS effective_bank_partner_id,
                debit_orders.created_by,
                DATE(debit_orders.created_at) AS mandate_created_date,
                YEAR(debit_orders.created_at) AS mandate_created_year,
                MONTH(debit_orders.created_at) AS mandate_created_month,
                debit_orders.created_at,
                debit_orders.updated_at
            FROM debit_orders
            LEFT JOIN partners ON partners.id = debit_orders.partner_id
            LEFT JOIN customer_accounts ON customer_accounts.id = debit_orders.customer_account_id
            LEFT JOIN accounts ON accounts.id = customer_accounts.account_id
        ");

        DB::statement("
            CREATE VIEW vw_dashboard_collections AS
            SELECT
                installments.id AS installment_id,
                installments.debit_order_id,
                installments.installment_order,
                installments.installment_amount,
                installments.last_paid_amount,
                installments.current_balance,
                installments.outstanding_amount,
                installments.status AS collection_status,
                installments.remarks AS collection_remarks,
                installments.payment_reference,
                installments.payment_date,
                DATE(installments.payment_date) AS expected_date,
                YEAR(installments.payment_date) AS expected_year,
                MONTH(installments.payment_date) AS expected_month,
                installments.effective_payment_date,
                DATE(installments.effective_payment_date) AS collected_date,
                YEAR(installments.effective_payment_date) AS collected_year,
                MONTH(installments.effective_payment_date) AS collected_month,
                installments.created AS collection_created,
                installments.created_at AS installment_created_at,
                installments.updated_at AS installment_updated_at,
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
                mandates.mandate_total_amount,
                mandates.mandate_approved,
                mandates.mandate_status_code,
                mandates.lifecycle_status,
                mandates.contract_status,
                mandates.deleted,
                mandates.bank_sponsor_partner_id,
                mandates.effective_bank_partner_id,
                mandates.created_by,
                mandates.mandate_created_date,
                mandates.mandate_created_year,
                mandates.mandate_created_month,
                mandates.created_at AS mandate_created_at
            FROM installments
            LEFT JOIN (
                SELECT
                    debit_order_id,
                    debit_order_uuid,
                    mandate_reference,
                    partner_id,
                    partner_uuid,
                    corporate_reference,
                    partner_name,
                    company_id,
                    partner_approved,
                    partner_lifecycle_status,
                    customer_account_id,
                    customer_id,
                    account_id,
                    account_type_id,
                    account_name,
                    account_number,
                    phone,
                    channel_name,
                    channel_slug,
                    channel,
                    source,
                    debit_type,
                    mandate_type,
                    total_amount AS mandate_total_amount,
                    mandate_approved,
                    mandate_status_code,
                    lifecycle_status,
                    contract_status,
                    deleted,
                    bank_sponsor_partner_id,
                    effective_bank_partner_id,
                    created_by,
                    mandate_created_date,
                    mandate_created_year,
                    mandate_created_month,
                    created_at
                FROM vw_dashboard_mandates
            ) mandates ON mandates.debit_order_id = installments.debit_order_id
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS vw_dashboard_collections');
        DB::statement('DROP VIEW IF EXISTS vw_dashboard_mandates');
    }

    protected function coalescedColumnExpression(string $table, array $columns, string $fallback): string
    {
        $expressions = [];

        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                $expressions[] = "{$table}.{$column}";
            }
        }

        if (empty($expressions)) {
            return $fallback;
        }

        $expressions[] = $fallback;

        return 'COALESCE(' . implode(', ', $expressions) . ')';
    }

    protected function columnOrNull(string $table, string $column): string
    {
        return $this->columnOrFallback($table, $column, 'NULL');
    }

    protected function columnOrFallback(string $table, string $column, string $fallback): string
    {
        return Schema::hasColumn($table, $column) ? "{$table}.{$column}" : $fallback;
    }

    protected function lifecycleExpression(): string
    {
        if (Schema::hasColumn('debit_orders', 'lifecycle_status')) {
            return 'debit_orders.lifecycle_status';
        }

        return "CASE
            WHEN debit_orders.approved = 1 OR debit_orders.status = 240 THEN 'active'
            WHEN debit_orders.approved = 2 OR debit_orders.status IN (403, 404, 405, 430, 500, 501) THEN 'failed'
            WHEN debit_orders.status = 202 THEN 'verified'
            WHEN debit_orders.status = 201 THEN 'awaiting_otp'
            WHEN debit_orders.status IN (200, 249) THEN 'submitted'
            ELSE 'draft'
        END";
    }
}

