# Collection Reporting and Integrity

## Purpose

This module keeps BI, portal reports, scheduled summaries, exports, and mobile
APIs on the same collection definitions.

## Sources of Truth

### Expected collection

Expected collection is the installment schedule:

- Source: `vw_dashboard_expected_collections`
- Date: `installments.payment_date`
- Value: `installments.installment_amount`
- Required ownership: approved service provider and approved mandate

### Cash received

Cash received is the net settlement ledger:

- Source: `vw_dashboard_payment_collections`
- Date: `payment_settlements.effective_payment_date`
- Value: `payment_settlements.paid_amount`
- Required flags: `applied_to_balances = 1` and
  `reconciliation_status = POSTED`
- Included statuses: `SUCCESS`, `PARTIAL`, and `REVERSED`

`REVERSED` entries have a negative `paid_amount`. Original settlement rows
must never be edited or deleted to represent a reversal.

### Schedule performance

Schedule performance answers how much of installments due in a selected period
has been paid, even when payment happened later:

```text
schedule_paid = min(max(net settlement allocation, 0), installment_amount)
outstanding   = installment_amount - schedule_paid
expected      = schedule_paid + outstanding
```

### Cash-flow performance

Cash flow answers how much money was received in the selected period. A May
installment paid in June is May schedule performance but June cash received.

## Main Components

- `DashboardReportingQueryService`: applies user ownership and report filters
  to reporting views. It also provides five-minute user/filter-aware caching.
- `CollectionAnalyticsService`: expected and actual collection screens and
  exports.
- `ReportQueryService`: report previews and export queries.
- `CollectionIntegrityService`: detects orphan, duplicate, invalid, and
  overallocated settlement records and validates accounting identities.
- `CollectionMetricCalculator`: currency-safe business-rule calculations
  used by focused unit tests.
- `CollectionIntegrityController`: admin/manager exception screen.

## Integrity Audit

Run the current month:

```bash
php artisan collections:audit-integrity
```

Run a month:

```bash
php artisan collections:audit-integrity --month=2026-08
```

Run a date range and fail automation when issues exist:

```bash
php artisan collections:audit-integrity \
  --from=2026-08-01 \
  --to=2026-08-31 \
  --fail-on-issues
```

The scheduler runs the audit daily at 00:30 with overlap protection.
Repeated runs update existing issues using a stable fingerprint and mark issues
resolved when they are no longer detected.

## Integrity Issue Types

- `ORPHAN_MANDATE`: settlement references a missing debit order.
- `ORPHAN_INSTALLMENT`: valid mandate exists but installment is missing.
- `MISSING_INSTALLMENT`: no installment identifier was supplied.
- `DUPLICATE_PAYMENT`: channel, payment reference, and installment identity
  occurs more than once.
- `OVERALLOCATED_INSTALLMENT`: net posted payments exceed schedule by more
  than one cent.
- `INVALID_POSTED_SETTLEMENT`: reportable posted event has zero amount or no
  effective payment date.

## Recording a Reversal

```php
$reversal = $settlement->reverse('Bank confirmed transaction reversal.');
```

The method:

1. Locks and validates the original settlement.
2. Refuses failed, pending, unposted, or already reversed records.
3. Inserts a linked negative `REVERSED` event.
4. Leaves the original row unchanged.

Restrict any UI or API action calling this method to authorized finance users.

## Historical Backfill

`payments:backfill-from-installment-payments` retains its dry-run and archival
guards. After an applied backfill creates settlement allocations, it runs the
integrity audit for the affected settlement date range. An accounting identity
failure makes the command fail.

## Filters and Access

Reporting views support channel, service provider, corporate reference,
mandate type, keyword, year/month, and date range filters. User access is
applied before aggregation:

- Partner users: company-owned providers only.
- Company approvers: explicitly authorized providers only.
- Company users: mandates created by that user only.
- Administrators/managers: all providers.

Missing ownership mappings fail closed and return no financial rows.

## Performance

Composite indexes cover settlement period reporting, installment allocation,
and expected schedule lookups. Dashboard aggregate caches include the user ID
and normalized filter values, so one provider cannot receive another
provider's cached totals. Cache entries expire after five minutes.

The displayed data-freshness timestamp is the latest update across settlements,
installments, and mandates.

## Tests

```bash
php artisan test tests/Unit/CollectionMetricCalculatorTest.php \
  tests/Unit/PaymentSettlementReportingPolicyTest.php
```

Before deployment also run:

```bash
php artisan collections:audit-integrity --month=YYYY-MM
php artisan test
```

Apply the decimal/index migration during a maintenance window on large
production databases because altering legacy installment columns can lock the
table.
