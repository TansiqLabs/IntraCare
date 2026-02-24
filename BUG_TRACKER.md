# IntraCare HMS — Bug Tracker

> **Purpose:** A lightweight, human-friendly log for tracking defects found in production/testing and documenting fixes.
> **Policy:** Every bug fixed must be recorded here with reproducible steps and verification notes.

---

## How to log a bug

Add a new entry under **Open Bugs** with:

- **ID**: `BUG-YYYYMMDD-###` (e.g., `BUG-20260224-001`)
- **Title**: short, specific
- **Severity**: `Critical | High | Medium | Low`
- **Module**: Patient EHR | Lab | Billing | RBAC | Queue | Pharmacy | Accounting | Setup | Other
- **Environment**: `local | staging | production` + OS + PHP + DB version
- **Detected in**: commit hash or release tag (if available)
- **Steps to reproduce**: numbered list
- **Expected** vs **Actual**
- **Logs / Screenshots**: paths only (no PHI in screenshots)
- **Root cause**: concise technical explanation
- **Fix summary**: what changed (files / approach)
- **Tests added**: test names + location (Pest/PHPUnit)
- **Verification**: exact commands run and results
- **Status**: `Open | In Progress | Fixed | Released | Won't Fix`
- **Owner**: person/role

---

## Open Bugs

_None yet._

---

## Fixed Bugs

### BUG-20260224-001 — Audit log insert fails (missing created_at)
- **Severity:** Critical
- **Module:** Compliance / Audit
- **Environment:** local (PostgreSQL)
- **Symptoms:** Saving any model using `Auditable` could throw a DB error because `audit_logs.created_at` is NOT NULL while `AuditLog` has `$timestamps = false`.
- **Root cause:** `Auditable::logAudit()` created `AuditLog` rows without explicitly setting `created_at`.
- **Fix summary:** Updated `app/Traits/Auditable.php` to always set `'created_at' => now()`.
- **Verification:** Admin user creation via CLI + app requests no longer throw audit insert errors.

### BUG-20260224-002 — Middleware crashes before migrations/tests
- **Severity:** High
- **Module:** Setup / Middleware
- **Environment:** testing (SQLite in-memory), fresh install
- **Symptoms:** HTTP requests return 500 with `no such table: users`.
- **Root cause:** `EnsureInstalled` called `User::count()` even when `users` table wasn’t created yet.
- **Fix summary:** Updated `app/Http/Middleware/EnsureInstalled.php` to bypass install check when `Schema::hasTable('users')` is false.
- **Tests added:** Updated feature test to validate redirect behavior.

### BUG-20260224-003 — SQLite test compatibility (jsonb)
- **Severity:** Medium
- **Module:** Database / Migrations
- **Environment:** testing (SQLite)
- **Symptoms:** SQLite can’t handle PostgreSQL-specific `jsonb` columns reliably.
- **Root cause:** Migrations used `$table->jsonb()`.
- **Fix summary:** Switched `jsonb` columns to `json` in foundational migrations.

### BUG-20260224-004 — UserFactory violates NOT NULL constraints
- **Severity:** Medium
- **Module:** Testing
- **Environment:** testing (SQLite)
- **Symptoms:** Feature tests fail because `users.employee_id` is required but factory didn’t set it.
- **Fix summary:** Updated `database/factories/UserFactory.php` to include `employee_id` and `is_active` defaults.

### BUG-20260224-005 — Setup wizard admin creation crashes (mass assignment)
- **Severity:** Critical
- **Module:** Setup
- **Environment:** local/testing
- **Symptoms:** POST `/setup` returns 500 with `MassAssignmentException` for `email_verified_at`.
- **Root cause:** `SetupController` sets `email_verified_at` during `User::create()` but `App\Models\User::$fillable` did not include `email_verified_at`.
- **Fix summary:** Added `email_verified_at` to `User::$fillable`.
- **Tests added:** `tests/Feature/SetupWizardTest.php` covers setup POST flow.

### BUG-20260224-006 — DB backup produced mislabeled format
- **Severity:** High
- **Module:** Backup
- **Environment:** local (PostgreSQL)
- **Symptoms:** Backup file named `.sql.gz` but command used `pg_dump -Fc` (custom format). Restore would require `pg_restore`, not `psql`.
- **Root cause:** Format/extension mismatch in `app/Console/Commands/DatabaseBackup.php`.
- **Fix summary:** Removed `-Fc` and now produces **plain SQL piped to gzip**, consistent with `.sql.gz`.
- **Verification:** Ran `php artisan db:backup` and confirmed file created under `BACKUP_PATH/database/2026/February/`.
