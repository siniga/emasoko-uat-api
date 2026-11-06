# Testing-server data

These seeders use the local database's schema, reference values, role IDs and
relationships. Business records are synthetic; local customer identities,
passwords, bank credentials and real transaction payloads are not exported.

The default dataset contains 30 connected workflows and approximately 1,500 rows.
It covers 76 application tables: 69 receive fixtures and seven runtime tables are
left to the application. Laravel manages `migrations`, and migrations create the
five reporting views. Child tables can contain more than 30 rows where a provider
needs several documents, approvals, service assignments or user links.

## Run on the testing server

Use the testing server's existing `.env` and database connection. Add:

```dotenv
APP_ENV=staging
TEST_DATA_ENABLED=true
TEST_DATA_ROWS=30
TEST_DATA_PASSWORD="replace-with-your-own-test-only-password"
```

Choose your own password with at least 12 characters. There is no committed default
password. Supported environments are `local`, `testing`, `staging`, `uat`, `test`
and `development`. Production is rejected even when `--force` is used.

Then run:

```sh
php artisan config:clear
php artisan migrate
php artisan db:seed --class=TestingDatabaseSeeder
```

The default `php artisan db:seed` calls the same testing seeder. For a new empty
database, `php artisan migrate --seed` also works after enabling the settings.
For an existing database with the old migration history, follow
[MIGRATIONS.md](MIGRATIONS.md) to register its baseline before migrating.

On a new testing installation, generate `APP_KEY` with `php artisan key:generate`
only if it is absent. Passport API/mobile login also needs signing keys; run
`php artisan passport:keys` once if the testing server has none. Keep existing
keys on subsequent deployments. The seeders create personal-access and password
OAuth clients. Remove stale `PASSPORT_PERSONAL_ACCESS_CLIENT_ID` and
`PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET` settings copied from another database,
or point them at the testing personal-access client created in `oauth_clients`.
Do not copy local or production private signing keys through the seeders.

The web app's normal upload directories must be writable. The seeder copies the
committed synthetic document to `public/assets/uploads/docs/` and
`public/assets/uploads/pdf/` using the filename `emakato-testing-sample.txt`, so
registration and agreement download links work. Existing files with a different
content are never replaced.

## Test accounts

Every account below uses your `TEST_DATA_PASSWORD`:

| Email | Application role | Purpose |
| --- | --- | --- |
| `admin@testing.emakato.invalid` | admin | Dashboards and administration |
| `approver@testing.emakato.invalid` | Company_Approver | Provider/mandate approvals |
| `operator@testing.emakato.invalid` | Company_User | Provider operations |
| `partner@testing.emakato.invalid` | Partner | First testing company's providers |
| `manager@testing.emakato.invalid` | Manager | Management and system health |
| `subscriber.admin@testing.emakato.invalid` | Subscriber_Admin | Subscriber administration |
| `subscriber.user@testing.emakato.invalid` | Subscriber_User | Subscriber access |
| `api@testing.emakato.invalid` | Company_User, API enabled | External API username `test.api` |

The first eight users are these profiles. Further users provide additional sample
records. The operator, approver and API account are linked to all testing service
providers. Company and subscriber links are also populated. Canonical role IDs
are retained because existing application code refers to IDs 2, 4 and 6.

The current web login checks the legacy `otp_password` field as well as normal
authentication credentials; both are populated from your test password. Seeding
does not bypass OTP or replace authentication code. Seeded OTP records are expired
history; logging in creates a new OTP using the existing application flow.

Fixture email addresses use `.invalid` and fixture phone/account numbers are
deliberately synthetic. Configure a testing SMS/mail transport to inspect new
login messages. You can also inspect the generated OTP in the testing database:

```sql
SELECT u.email, o.otp, o.expire_at
FROM user_otps o
JOIN users u ON u.id = o.user_id
WHERE u.email = 'admin@testing.emakato.invalid'
ORDER BY o.id DESC
LIMIT 1;
```

Seeding itself sends no SMS, email, callbacks, bank requests, or queued jobs. Using
the application afterwards still invokes its normal integrations. Configure bank
and payment sandbox endpoints/credentials separately and replace synthetic account
numbers with the provider's sandbox fixtures when testing real integration calls.
The existing `MessagingService` currently contains a hard-coded SMS endpoint, so
configure or mock that sender separately before testing OTP delivery. Seed data
does not turn a live integration into a sandbox.

## Dataset and repeat runs

NMB/CRDB channels, account types, all seven roles, currencies, regions, categories,
tariffs, mandate services and Swahili SMS templates come from local reference data.
Thirty synthetic providers, customers, accounts, subscribers, mandates,
installments and collections are connected using the inserted IDs. Scenarios
include full settlement/closed, partial settlement/active, failed/active,
pending/draft and cancelled/funds-check history. Settlement totals reconcile with
the seeded mandate and installment balances. Active mandates have future next
debit dates; batches are completed history or drafts rather than queued pushes.

The dataset is insert-only. Re-running it reuses its stable keys, keeps user edits,
preserves existing numbering counters, and anchors dates to the first fixture run.
It does not truncate tables, reset IDs, disable foreign keys, delete existing data,
or change existing user passwords. Increasing `TEST_DATA_ROWS` up to 50 appends
additional workflows; reducing it does not delete earlier fixtures. A canonical
reference-ID conflict fails the transaction rather than overwriting existing data.
Changing `TEST_DATA_PASSWORD` does not reset accounts already created; use the
application's password-management flow for those accounts.

Eight seeder groups run in dependency order inside one database transaction, with
an advisory lock preventing concurrent seeder runs. A second pass resolves the
new mandate/LUKU purchase circular link. Query-builder inserts avoid model
observers and outbound integration jobs. Core fixtures do not require Faker or
other development-only packages.

The seven runtime tables are not fabricated: pending/failed serialized jobs,
password reset tokens, OAuth authorization codes, issued access/refresh tokens,
and Sanctum tokens. On a fresh database these tables remain empty until normal
test actions create their records; existing runtime records are left unchanged.

## Verification

```sh
php scripts/verify_testing_seeders.php
```

The verifier requires the development dependencies and permission to create a
disposable database on the configured local MySQL/MariaDB server. It checks all
table coverage, physical and application relationships, financial balances,
reporting views, role access, attachment files, transactional rollback, production
and opt-in guards, and repeat runs including a later month. It also exercises API
and mobile authentication with temporary signing keys and web OTP generation with
fake mail/SMS transports. It checks that the source database is unchanged and
removes its own temporary database and newly created testing attachments.

## Table-by-table migration and seeder map

The machine-readable map is
[`seeders/data/testing-table-map.json`](seeders/data/testing-table-map.json).
It lists each table's migration, seeder, dependencies and runtime policy. The
executable group mapping is in `seeders/Testing/TestingDataPlan.php`.

<!-- table-map:start -->

| Table | Migration | Seeder / policy |
| --- | --- | --- |
| `account_types` | `0001_01_01_000001_create_account_types_table` | ReferenceDataSeeder |
| `accounts` | `0001_01_01_000002_create_accounts_table` | ReferenceDataSeeder |
| `acquisition` | `0001_01_01_000003_create_acquisition_table` | ReferenceDataSeeder |
| `app_variables` | `0001_01_01_000004_create_app_variables_table` | ReferenceDataSeeder |
| `audit_logs` | `0001_01_01_000005_create_audit_logs_table` | ObservabilityDataSeeder |
| `back_logs` | `0001_01_01_000006_create_back_logs_table` | ObservabilityDataSeeder |
| `collection_batch_push_attempts` | `2026_09_13_000003_create_collection_batch_push_attempts_table` | CollectionDataSeeder |
| `collection_batches` | `0001_01_01_000007_create_collection_batches_table` | CollectionDataSeeder |
| `collection_debit_orders` | `0001_01_01_000008_create_collection_debit_orders_table` | CollectionDataSeeder |
| `collection_integrity_issues` | `2026_09_10_000006_create_collection_integrity_issues_table` | ObservabilityDataSeeder |
| `companies` | `0001_01_01_000009_create_companies_table` | IdentityDataSeeder |
| `company_partner_categories` | `0001_01_01_000010_create_company_partner_categories_table` | IdentityDataSeeder |
| `company_users` | `0001_01_01_000011_create_company_users_table` | IdentityDataSeeder |
| `countries` | `0001_01_01_000012_create_countries_table` | ReferenceDataSeeder |
| `currencies` | `0001_01_01_000013_create_currencies_table` | ReferenceDataSeeder |
| `customer_accounts` | `0001_01_01_000014_create_customer_accounts_table` | CustomerDataSeeder |
| `customer_partners` | `0001_01_01_000015_create_customer_partners_table` | CustomerDataSeeder |
| `customers` | `0001_01_01_000016_create_customers_table` | CustomerDataSeeder |
| `debit_order_requests` | `0001_01_01_000017_create_debit_order_requests_table` | MandateDataSeeder |
| `debit_orders` | `0001_01_01_000018_create_debit_orders_table` | MandateDataSeeder |
| `document_categories` | `0001_01_01_000019_create_document_categories_table` | ReferenceDataSeeder |
| `external_callback_logs` | `0001_01_01_000020_create_external_callback_logs_table` | ObservabilityDataSeeder |
| `failed_jobs` | `0001_01_01_000021_create_failed_jobs_table` | Runtime-generated |
| `gender` | `0001_01_01_000022_create_gender_table` | ReferenceDataSeeder |
| `gepg_bill_payments` | `0001_01_01_000023_create_gepg_bill_payments_table` | PaymentDataSeeder |
| `gepg_tariffs` | `0001_01_01_000024_create_gepg_tariffs_table` | ReferenceDataSeeder |
| `id_types` | `0001_01_01_000025_create_id_types_table` | ReferenceDataSeeder |
| `installment_payments` | `0001_01_01_000026_create_installment_payments_table` | PaymentDataSeeder |
| `installments` | `0001_01_01_000027_create_installments_table` | MandateDataSeeder |
| `jobs` | `0001_01_01_000028_create_jobs_table` | Runtime-generated |
| `loan_purposes` | `0001_01_01_000029_create_loan_purposes_table` | ReferenceDataSeeder |
| `luku_payment_purchases` | `0001_01_01_000030_create_luku_payment_purchases_table` | PaymentDataSeeder |
| `mandate_service_partner` | `0001_01_01_000031_create_mandate_service_partner_table` | PartnerDataSeeder |
| `mandate_services` | `0001_01_01_000032_create_mandate_services_table` | ReferenceDataSeeder |
| `marital_status` | `0001_01_01_000033_create_marital_status_table` | ReferenceDataSeeder |
| `migrations` | Laravel migration repository | Laravel-managed |
| `notifications` | `0001_01_01_000034_create_notifications_table` | ObservabilityDataSeeder |
| `oauth_access_tokens` | `0001_01_01_000035_create_oauth_access_tokens_table` | Runtime-generated |
| `oauth_auth_codes` | `0001_01_01_000036_create_oauth_auth_codes_table` | Runtime-generated |
| `oauth_clients` | `0001_01_01_000037_create_oauth_clients_table` | IdentityDataSeeder |
| `oauth_personal_access_clients` | `0001_01_01_000038_create_oauth_personal_access_clients_table` | IdentityDataSeeder |
| `oauth_refresh_tokens` | `0001_01_01_000039_create_oauth_refresh_tokens_table` | Runtime-generated |
| `occupations` | `0001_01_01_000040_create_occupations_table` | ReferenceDataSeeder |
| `otp_requests` | `0001_01_01_000041_create_otp_requests_table` | MandateDataSeeder |
| `partner_accounts` | `0001_01_01_000042_create_partner_accounts_table` | PartnerDataSeeder |
| `partner_approvals` | `0001_01_01_000043_create_partner_approvals_table` | PartnerDataSeeder |
| `partner_categories` | `0001_01_01_000044_create_partner_categories_table` | ReferenceDataSeeder |
| `partner_documents` | `0001_01_01_000045_create_partner_documents_table` | PartnerDataSeeder |
| `partner_staff` | `0001_01_01_000046_create_partner_staff_table` | PartnerDataSeeder |
| `partner_users` | `0001_01_01_000047_create_partner_users_table` | PartnerDataSeeder |
| `partners` | `0001_01_01_000048_create_partners_table` | PartnerDataSeeder |
| `password_resets` | `0001_01_01_000049_create_password_resets_table` | Runtime-generated |
| `payment_histories` | `0001_01_01_000050_create_payment_histories_table` | PaymentDataSeeder |
| `payment_logs` | `0001_01_01_000051_create_payment_logs_table` | PaymentDataSeeder |
| `payment_settlement_backfill_archives` | `0001_01_01_000052_create_payment_settlement_backfill_archives_table` | PaymentDataSeeder |
| `payment_settlements` | `0001_01_01_000053_create_payment_settlements_table` | PaymentDataSeeder |
| `payments` | `0001_01_01_000054_create_payments_table` | PaymentDataSeeder |
| `perm_classes` | `0001_01_01_000055_create_perm_classes_table` | IdentityDataSeeder |
| `perm_methods` | `0001_01_01_000056_create_perm_methods_table` | IdentityDataSeeder |
| `perm_roles` | `0001_01_01_000057_create_perm_roles_table` | IdentityDataSeeder |
| `perm_users` | `0001_01_01_000058_create_perm_users_table` | IdentityDataSeeder |
| `personal_access_tokens` | `0001_01_01_000059_create_personal_access_tokens_table` | Runtime-generated |
| `regions` | `0001_01_01_000060_create_regions_table` | ReferenceDataSeeder |
| `role_user` | `0001_01_01_000061_create_role_user_table` | IdentityDataSeeder |
| `roles` | `0001_01_01_000062_create_roles_table` | ReferenceDataSeeder |
| `sectors` | `0001_01_01_000063_create_sectors_table` | ReferenceDataSeeder |
| `sms_categories` | `0001_01_01_000064_create_sms_categories_table` | ReferenceDataSeeder |
| `sms_logs` | `0001_01_01_000065_create_sms_logs_table` | ObservabilityDataSeeder |
| `sms_templates` | `0001_01_01_000066_create_sms_templates_table` | ReferenceDataSeeder |
| `subscriber_mandate_requests` | `0001_01_01_000067_create_subscriber_mandate_requests_table` | PaymentDataSeeder |
| `subscriber_users` | `0001_01_01_000068_create_subscriber_users_table` | CustomerDataSeeder |
| `subscribers` | `0001_01_01_000069_create_subscribers_table` | CustomerDataSeeder |
| `system_health_checks` | `2026_09_09_000001_create_system_health_checks_table` | ObservabilityDataSeeder |
| `tb_occupations` | `0001_01_01_000070_create_tb_occupations_table` | ReferenceDataSeeder |
| `user_otps` | `0001_01_01_000071_create_user_otps_table` | IdentityDataSeeder |
| `users` | `0001_01_01_000072_create_users_table` | IdentityDataSeeder |
| `wallets` | `0001_01_01_000073_create_wallets_table` | PartnerDataSeeder |
