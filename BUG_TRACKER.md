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

### BUG-20260224-011 — Auditable trait crashes on delete for non-SoftDeletes models
- **Severity:** Critical
- **Module:** Compliance / Audit
- **Environment:** all
- **Symptoms:** Deleting a `Dispensation`, `LabOrderTest`, or `QueueTicket` throws `BadMethodCallException: Call to undefined method isForceDeleting()`.
- **Root cause:** `Auditable::bootAuditable()` unconditionally called `$model->isForceDeleting()` which only exists on models using `SoftDeletes`.
- **Fix summary:** Guarded with `method_exists($model, 'isForceDeleting')` check. Also fixed `restored` event registration to use `class_uses_recursive()` to check for `SoftDeletes` trait.
- **Status:** Fixed

### BUG-20260224-012 — AdminUserSeeder double-hashes password (login impossible)
- **Severity:** Critical
- **Module:** Setup / Seeder
- **Environment:** all
- **Symptoms:** Admin user created via seeder can never log in because password is double-hashed.
- **Root cause:** Seeder used `Hash::make('password')` but `User` model has `'hashed'` cast which auto-hashes on assignment.
- **Fix summary:** Changed to `'password' => 'password'` (plain text) and let the model cast handle hashing.
- **Status:** Fixed

### BUG-20260224-013 — Deprecated BadgeColumn in QueueTicketResource
- **Severity:** Critical
- **Module:** Queue / Filament UI
- **Environment:** all
- **Symptoms:** `Filament\Tables\Columns\BadgeColumn` deprecated/removed in Filament v3 — page may crash.
- **Fix summary:** Replaced with `TextColumn::make('status')->badge()->color(...)`.
- **Status:** Fixed

### BUG-20260224-014 — Regex rejects lowercase input before dehydration uppercases it
- **Severity:** High
- **Module:** Queue / Filament UI
- **Environment:** all
- **Symptoms:** Queue Department/Counter code fields reject lowercase input even though `dehydrateStateUsing` uppercases it (dehydration runs after validation).
- **Fix summary:** Changed regex from `/^[A-Z0-9\-]+$/` to `/^[A-Za-z0-9\-]+$/`.
- **Status:** Fixed

### BUG-20260224-015 — Dispensation batch dropdown unfiltered by drug
- **Severity:** High
- **Module:** Pharmacy / Filament UI
- **Environment:** all
- **Symptoms:** Batch select showed all batches regardless of selected drug; user could assign wrong drug's batch.
- **Fix summary:** Made `drug_id` reactive, batch dropdown now dynamically filters by selected drug with active/in-stock batches only.
- **Status:** Fixed

### BUG-20260224-016 — Missing unique validation on queue department/counter code
- **Severity:** High
- **Module:** Queue / Filament UI
- **Environment:** all
- **Symptoms:** Duplicate code submission caused unhandled `QueryException` instead of friendly validation error.
- **Fix summary:** Added `->unique(ignoreRecord: true)` on both `QueueDepartmentResource` and `QueueCounterResource` code inputs.
- **Status:** Fixed

### BUG-20260224-017 — DatabaseBackup hardcoded to PostgreSQL
- **Severity:** High
- **Module:** Backup
- **Environment:** all (especially SQLite/MySQL setups)
- **Symptoms:** Backup command always read from `database.connections.pgsql` regardless of `DB_CONNECTION`; fails on non-PostgreSQL systems.
- **Fix summary:** Refactored to read from active connection config and support PostgreSQL, MySQL, and SQLite backup strategies.
- **Status:** Fixed

### BUG-20260224-018 — Closure routes prevent route:cache
- **Severity:** Medium
- **Module:** Routes
- **Environment:** production deployment
- **Symptoms:** `php artisan route:cache` fails with `LogicException: Unable to prepare route for serialization.`
- **Fix summary:** Moved queue display closure to `QueueDisplayController` invokable controller; replaced root redirect closure with `Route::redirect()`.
- **Status:** Fixed

### BUG-20260224-019 — StockMovement missing reference() morphTo relationship
- **Severity:** Medium
- **Module:** Pharmacy / Models
- **Environment:** all
- **Symptoms:** No polymorphic relationship defined despite `reference_type`/`reference_id` columns being populated.
- **Fix summary:** Added `reference(): MorphTo` to `StockMovement` model.
- **Status:** Fixed

### BUG-20260224-020 — Patient model missing dispensations/queueTickets relationships
- **Severity:** Medium
- **Module:** Patient EHR / Models
- **Environment:** all
- **Symptoms:** `$patient->dispensations` / `$patient->queueTickets` threw `RelationNotFoundException`.
- **Fix summary:** Added `dispensations(): HasMany` and `queueTickets(): HasMany` to `Patient` model.
- **Status:** Fixed

### BUG-20260224-021 — LabTestParameter female child falls back to male range
- **Severity:** Medium
- **Module:** Lab / Clinical
- **Environment:** all
- **Symptoms:** For female children, if `normal_range_child` was null, fallback went to `normal_range_male` instead of `normal_range_female`.
- **Fix summary:** Updated `getNormalRangeFor()` fallback chain: child → female (if gender=female) → male.
- **Status:** Fixed

### BUG-20260224-022 — Drug model missing dispensationItems() relationship
- **Severity:** Low
- **Module:** Pharmacy / Models
- **Environment:** all
- **Fix summary:** Added `dispensationItems(): HasMany` to `Drug` model.
- **Status:** Fixed

### BUG-20260224-023 — visits.visited_at NOT NULL without default
- **Severity:** Medium
- **Module:** Database / Migrations
- **Environment:** all
- **Symptoms:** Creating a Visit without explicit `visited_at` threw `QueryException`.
- **Fix summary:** Made `visited_at` nullable in migration.
- **Status:** Fixed

### BUG-20260224-024 — SetupController updateEnv regex not escaped
- **Severity:** Low
- **Module:** Setup
- **Environment:** all
- **Symptoms:** If `updateEnv()` was called with a key containing regex special chars, it could match wrong lines.
- **Fix summary:** Applied `preg_quote()` on the `$key` variable in the regex.
- **Status:** Fixed

### BUG-20260224-025 — scout:import scheduled without Meilisearch check
- **Severity:** Low
- **Module:** Search / Scheduled Tasks
- **Environment:** all (especially non-Meilisearch setups)
- **Symptoms:** Daily cron error logs when Meilisearch isn't running.
- **Fix summary:** Wrapped `scout:import` schedule in `config('scout.driver') === 'meilisearch'` guard.
- **Status:** Fixed

### BUG-20260224-026 — DrugBatchResource allows direct edit of quantity_on_hand
- **Severity:** Medium
- **Module:** Pharmacy / Filament UI
- **Environment:** all
- **Symptoms:** Direct edit bypasses stock movement audit trail, causing inventory discrepancies.
- **Fix summary:** Made `quantity_on_hand` field disabled and non-dehydrated in the form.
- **Status:** Fixed

### BUG-20260224-027 — EnsureInstalled cache stale after DB reset
- **Severity:** Medium
- **Module:** Setup / Middleware
- **Environment:** development/testing
- **Symptoms:** Cached `intracare.installed=true` persisted even after all users were deleted.
- **Fix summary:** When cache says `true`, re-verify with `User::exists()` and clear cache if stale.
- **Status:** Fixed
