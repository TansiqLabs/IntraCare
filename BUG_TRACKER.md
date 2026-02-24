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

### BUG-20260224-007 — Default welcome page used external font CDN
- **Severity:** Medium
- **Module:** UI / Offline-first
- **Environment:** local/prod
- **Symptoms:** `resources/views/welcome.blade.php` referenced `https://fonts.bunny.net` which breaks offline runtime if the page is accessed.
- **Fix summary:** Replaced the welcome page with an offline-safe landing page using only local Vite assets.

### BUG-20260224-008 — Queue display redirected to setup
- **Severity:** Medium
- **Module:** Queue / Display
- **Environment:** local/testing
- **Symptoms:** `/queue/display/{department}` returned 302 redirect to `/setup` when no users existed.
- **Root cause:** Global `EnsureInstalled` middleware only exempted `setup.*` routes.
- **Fix summary:** Exempted `queue.display` route from install redirect in `app/Http/Middleware/EnsureInstalled.php`.
- **Tests added:** `tests/Feature/QueueDisplayTest.php`.

### BUG-20260224-009 — Tailwind scanned cached Blade views (non-deterministic builds)
- **Severity:** Low
- **Module:** UI / Build
- **Environment:** local
- **Symptoms:** CSS output could vary/bloat because Tailwind was scanning `storage/framework/views` (compiled, runtime-generated Blade cache).
- **Root cause:** `resources/css/app.css` included `@source '../../storage/framework/views/*.php';`.
- **Fix summary:** Removed the cached-views `@source` and added a guard test to prevent external URLs from being introduced into runtime Blade/JS assets.
- **Tests added:** `tests/Feature/OfflineExternalAssetsTest.php`.

### BUG-20260224-010 — Install-check cache could become stale after DB reset
- **Severity:** Medium
- **Module:** Setup / Middleware
- **Environment:** testing/local
- **Symptoms:** App could incorrectly treat the system as installed (redirecting `/` to `/admin`) even when there were actually no users.
- **Root cause:** `EnsureInstalled` cached `intracare.installed=true` for 1 hour and didn’t re-validate against the DB; cache could survive across DB refreshes/tests.
- **Fix summary:** If cached value is `true`, re-check `User::exists()` and clear the cache if it’s stale.
- **Verification:** `php artisan test` passes; caching (`config:cache`, `route:cache`, `view:cache`) no longer breaks feature tests.

### 2026-02-24 — Ops: `.env.example` made offline-first by default
- **Change:** Updated `.env.example` defaults to boot without Redis/Meilisearch and without requiring an absolute `BACKUP_PATH`.
- **Why:** Fresh installs should run on a single machine offline; Redis/Meilisearch remain optional and can be enabled by uncommenting env values.

### 2026-02-24 — Pharmacy: voiding completed dispensations restores stock
- **Change:** Added `PharmacyInventoryService::voidCompletedDispensation()` and Filament UI action to void a completed dispensation.
- **Why:** Mistakes happen; voiding must create compensating movements (type `return`) and restore batch on-hand without manual DB edits.
- **Tests added:** `tests/Feature/PharmacyInventoryTest.php`.
