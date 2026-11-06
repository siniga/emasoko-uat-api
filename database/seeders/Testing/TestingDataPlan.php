<?php

namespace Database\Seeders\Testing;

class TestingDataPlan
{
    public const GROUPS = [
        ReferenceDataSeeder::class => ['account_types','accounts','acquisition','app_variables','countries','currencies','document_categories','gender','gepg_tariffs','id_types','loan_purposes','mandate_services','marital_status','occupations','partner_categories','regions','roles','sectors','sms_categories','sms_templates','tb_occupations'],
        IdentityDataSeeder::class => ['users','role_user','user_otps','perm_classes','perm_methods','perm_roles','perm_users','companies','company_partner_categories','company_users','oauth_clients','oauth_personal_access_clients'],
        PartnerDataSeeder::class => ['partners','partner_accounts','wallets','mandate_service_partner','partner_users','partner_documents','partner_approvals','partner_staff'],
        CustomerDataSeeder::class => ['customers','customer_accounts','customer_partners','subscribers','subscriber_users'],
        MandateDataSeeder::class => ['otp_requests','debit_orders','installments','debit_order_requests'],
        CollectionDataSeeder::class => ['collection_batches','collection_debit_orders','collection_batch_push_attempts'],
        PaymentDataSeeder::class => ['payment_settlements','installment_payments','payment_histories','payments','payment_logs','luku_payment_purchases','gepg_bill_payments','subscriber_mandate_requests','payment_settlement_backfill_archives'],
        ObservabilityDataSeeder::class => ['audit_logs','back_logs','notifications','sms_logs','external_callback_logs','collection_integrity_issues','system_health_checks'],
    ];

    public const RUNTIME = [
        'jobs' => 'Jobs are created by application actions, not by fixtures.',
        'failed_jobs' => 'Failed serialized jobs must come from real test executions.',
        'password_resets' => 'Password reset tokens are generated on request.',
        'oauth_access_tokens' => 'Passport issues signed access tokens after authentication.',
        'oauth_auth_codes' => 'OAuth authorization codes are issued by the authorization flow.',
        'oauth_refresh_tokens' => 'OAuth refresh tokens are issued with authenticated grants.',
        'personal_access_tokens' => 'Sanctum issues access tokens on request.',
    ];

    public static function tables(): array
    {
        return array_merge(...array_values(self::GROUPS));
    }
}
