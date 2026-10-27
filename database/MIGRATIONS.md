# Database migrations

The baseline dated `0001_01_01` captures the local MariaDB schema on 26 October
2026. It contains 73 application tables and five reporting views. Laravel creates
and manages the additional `migrations` table itself.

Each application table has a create migration containing its exact MariaDB DDL,
including column types, nullability, defaults, primary keys, unique keys, indexes,
JSON checks, engine and collation. A separate migration adds the nine existing
foreign keys after all tables exist. Views are created in dependency order using
the connected database and `SQL SECURITY INVOKER`; no original database name or
dump-account definer is required. Auto-increment counters and data are excluded.

These migrations target the project's MySQL/MariaDB connection. The captured
MariaDB JSON checks and SQL definitions are not an SQLite schema. Existing
relationships enforced only by application code remain unchanged; adding further
database foreign keys requires a separate migration and compatible column types.

## New empty database

Set `DB_DATABASE` to a new empty database, clear cached configuration if needed,
and run:

```powershell
php artisan migrate
```

This creates the baseline and then runs seven existing upgrades. Those upgrades
also create `system_health_checks`, `collection_integrity_issues`, and
`collection_batch_push_attempts`, and add the pending reporting and onboarding
changes. The fully upgraded schema therefore has 77 tables, including the
framework migration table, and five views. No application records are seeded.

## Existing database matching the captured schema

Do not run a reset to adopt this baseline. Verify the schema first:

```powershell
php artisan schema:baseline
```

The command compares every captured table and view, including constraints and
indexes, against `database/schema/baseline.json`. It ignores auto-increment
counters and view definer accounts. If any definition differs, it refuses to
register anything and lists the differences.

For a matching database, register the baseline and then run pending upgrades:

```powershell
php artisan schema:baseline --apply
php artisan migrate
```

Registration adds only baseline entries to the existing migration history in
batch zero. It does not execute DDL or change application records. Repeating
registration is safe. `--database=connection_name` selects another configured
connection. A plain `migrate` on an unregistered existing database stops before
creating project tables and points to this adoption command.

The rebuild itself does not register or apply anything on the existing database.
After adoption, historical rollback before the baseline is replaced by the
baseline's rollback. Resetting or rolling back the baseline removes its tables
and their data; use that only on a database intended for rebuilding.

## Historical migrations

`database/migrations/archive/` preserves 39 original files whose schema is
represented by the baseline, including the already-present jobs table and account
logo column. Laravel's normal migration discovery does not recurse into that
directory. Do not pass the archive as a migration path for a new installation.

The seven unapplied upgrades remain in the active migration directory. Add future
migrations there using normal current timestamps. Keep the baseline and its
manifest unchanged so adoption remains verifiable.

## Verification

```powershell
php scripts/verify_migration_baseline.php
```

This creates a randomly named disposable database on the configured local server.
It verifies exact baseline definitions, foreign key enforcement, adoption preview,
schema-drift rejection, adoption idempotence, all pending upgrades, clean migration
runs, repeated runs, `migrate:fresh` and rollback. It then removes only its own disposable database
and verifies that application row counts in the source database are unchanged.
The configured account needs permission to create and drop the disposable database.
