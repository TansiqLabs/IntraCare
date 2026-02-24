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

### BUG-20260224-028 — Notifications migration uses `morphs()` incompatible with ULID PKs
- **Severity:** Critical
- **Module:** Database / Notifications
- **Symptoms:** `morphs('notifiable')` generates `unsignedBigInteger` columns, but all models use 26-char ULID strings. Notifications table can't store references to User or Patient records.
- **Fix summary:** Replaced `$table->morphs('notifiable')` with `$table->string('notifiable_type')`, `$table->string('notifiable_id', 26)`, and explicit index.
- **Files:** `database/migrations/2026_02_24_130001_create_notifications_table.php`
- **Status:** Fixed

### BUG-20260224-029 — Dispensation model missing enum cast for status
- **Severity:** High
- **Module:** Pharmacy
- **Symptoms:** `Dispensation->status` returned raw strings instead of typed enum. Inconsistent comparisons across service and resource layers.
- **Fix summary:** Created `DispensationStatus` enum (Draft/Completed/Cancelled) with `label()` and `color()` methods. Added cast to Dispensation model. Updated PharmacyInventoryService and DispensationResource to use enum.
- **Files:** `app/Enums/DispensationStatus.php` (new), `app/Models/Dispensation.php`, `app/Services/Pharmacy/PharmacyInventoryService.php`, `app/Filament/Resources/DispensationResource.php`
- **Status:** Fixed

### BUG-20260224-030 — QueueTicket model missing enum cast for status
- **Severity:** High
- **Module:** Queue
- **Symptoms:** `QueueTicket->status` returned raw strings. Status comparisons used magic strings throughout.
- **Fix summary:** Created `QueueTicketStatus` enum (Waiting/Called/Served/NoShow). Added cast to model. Updated QueueTokenService and QueueTicketResource to use enum consistently.
- **Files:** `app/Enums/QueueTicketStatus.php` (new), `app/Models/QueueTicket.php`, `app/Services/Queue/QueueTokenService.php`, `app/Filament/Resources/QueueTicketResource.php`
- **Status:** Fixed

### BUG-20260224-031 — StockMovement model missing enum cast for type
- **Severity:** High
- **Module:** Pharmacy
- **Symptoms:** `StockMovement->type` returned raw strings instead of typed enum.
- **Fix summary:** Created `StockMovementType` enum (Receive/Dispense/Adjust/Return). Added cast to model.
- **Files:** `app/Enums/StockMovementType.php` (new), `app/Models/StockMovement.php`
- **Status:** Fixed

### BUG-20260224-032 — Payment model missing Auditable trait and invoice balance never recalculated
- **Severity:** Critical
- **Module:** Billing / Compliance
- **Symptoms:** After a payment is created or deleted, the parent Invoice's `paid` and `balance` fields are never updated. Also, payments are not audit-logged despite being financial records.
- **Fix summary:** Added `Auditable` trait. Added `booted()` with `saved` and `deleted` observers that recalculate the invoice's `paid`/`balance` via `forceFill`.
- **Files:** `app/Models/Payment.php`
- **Status:** Fixed

### BUG-20260224-033 — User model exposes sensitive fields in $fillable
- **Severity:** High
- **Module:** Auth / Security
- **Symptoms:** `email_verified_at`, `last_login_at`, `last_login_ip` were mass-assignable, allowing tampering via request injection.
- **Fix summary:** Removed those three fields from `$fillable`. Updated `SetupController` and `AdminUserSeeder` to use `forceFill()` for `email_verified_at`.
- **Files:** `app/Models/User.php`, `app/Http/Controllers/SetupController.php`, `database/seeders/AdminUserSeeder.php`
- **Status:** Fixed

### BUG-20260224-034 — EnsureInstalled middleware re-queries DB on every request
- **Severity:** High
- **Module:** Performance / Middleware
- **Symptoms:** When `installed=true` in cache, the middleware still ran `User::query()->exists()` on every single request as "re-validation", adding unnecessary DB load.
- **Fix summary:** Removed the re-validation block. When cache says installed, trust it. Cache is explicitly cleared during setup/teardown workflows.
- **Files:** `app/Http/Middleware/EnsureInstalled.php`
- **Status:** Fixed

### BUG-20260224-035 — Backup cleanup only deletes .sql.gz, misses .sqlite.gz
- **Severity:** Medium
- **Module:** Admin / Backup
- **Symptoms:** `cleanupOldBackups()` checked `str_ends_with($file, '.sql.gz')` only, so old SQLite backups (.sqlite.gz) accumulated indefinitely.
- **Fix summary:** Added `|| str_ends_with($file, '.sqlite.gz')` to the cleanup condition.
- **Files:** `app/Console/Commands/DatabaseBackup.php`
- **Status:** Fixed

### BUG-20260224-036 — FilamentShield plugin not registered in admin panel
- **Severity:** Critical
- **Module:** RBAC / Auth
- **Symptoms:** `filament-shield` config existed and spatie/laravel-permission was installed, but the `FilamentShieldPlugin` was never registered in `AdminPanelProvider`. Shield's permission checks and policy generation had no effect.
- **Fix summary:** Added `->plugin(FilamentShieldPlugin::make())` to the panel chain.
- **Files:** `app/Providers/Filament/AdminPanelProvider.php`
- **Status:** Fixed

### BUG-20260224-037 — 7 PHI/clinical models missing Auditable trait
- **Severity:** Medium
- **Module:** Compliance / Audit
- **Symptoms:** `Prescription`, `PrescriptionItem`, `LabResult`, `LabSample`, `VisitDiagnosis`, `PatientAllergy`, `PatientChronicCondition` modified patient health data without audit logging.
- **Fix summary:** Added `use Auditable` trait to all 7 models.
- **Files:** All 7 model files in `app/Models/`
- **Status:** Fixed

### BUG-20260224-038 — Missing inverse Eloquent relationships on 4 models
- **Severity:** Medium
- **Module:** Data Integrity / ORM
- **Symptoms:** `Prescription` lacked `dispensations()`, `Visit` lacked `queueTickets()`, `DrugBatch` lacked `dispensationItems()`, `IcdCode` lacked `visitDiagnoses()` and `chronicConditions()`. Navigation from parent to children impossible without raw queries.
- **Fix summary:** Added the missing `HasMany` relationships.
- **Files:** `app/Models/Prescription.php`, `app/Models/Visit.php`, `app/Models/DrugBatch.php`, `app/Models/IcdCode.php`
- **Status:** Fixed

### BUG-20260224-039 — PharmacyInventoryService receiveToBatch ignores zero prices
- **Severity:** High
- **Module:** Pharmacy
- **Symptoms:** `receiveToBatch()` used `int $unitCost = 0` and `if ($unitCost !== 0)` to decide whether to update batch pricing. A legitimate zero-cost donation item would never update the batch price.
- **Fix summary:** Changed parameters to `?int $unitCost = null` / `?int $salePrice = null`. Changed guard from `!== 0` to `!== null`.
- **Files:** `app/Services/Pharmacy/PharmacyInventoryService.php`
- **Status:** Fixed

### BUG-20260224-040 — DrugBatchResource allows editing quantity_received
- **Severity:** High
- **Module:** Pharmacy / Filament
- **Symptoms:** `quantity_received` field was editable on existing batches, allowing manual tampering that bypasses stock movement tracking. Should only be set at creation or via `receiveToBatch()`.
- **Fix summary:** Added `->disabled(fn (?DrugBatch $record) => $record !== null)->dehydrated()` to the field.
- **Files:** `app/Filament/Resources/DrugBatchResource.php`
- **Status:** Fixed

### BUG-20260224-041 — EditDispensation allows editing completed/cancelled records
- **Severity:** High
- **Module:** Pharmacy / Filament
- **Symptoms:** No guard prevented editing a completed or cancelled dispensation. Users could modify finalized pharmacy records.
- **Fix summary:** Added `authorizeAccess()` override that aborts with 403 for non-draft dispensations. Delete action now only visible for drafts.
- **Files:** `app/Filament/Resources/DispensationResource/Pages/EditDispensation.php`
- **Status:** Fixed

### BUG-20260224-042 — QueueTicketResource missing confirmations and counter selection
- **Severity:** Medium
- **Module:** Queue / Filament
- **Symptoms:** Call/Serve/No-show actions had no confirmation dialog — accidental clicks irreversible. Call action didn't allow selecting a counter. Token date/number were editable on existing tickets.
- **Fix summary:** Added `->requiresConfirmation()` to all 3 actions. Added counter selection form to call action. Made `token_date` and `token_number` disabled on edit. Updated all status comparisons to use `QueueTicketStatus` enum.
- **Files:** `app/Filament/Resources/QueueTicketResource.php`
- **Status:** Fixed

### BUG-20260224-043 — DrugResource N+1 query on total_on_hand
- **Severity:** Medium
- **Module:** Pharmacy / Performance
- **Symptoms:** The `total_on_hand` column executed a separate aggregate query per row in the table listing, causing N+1 performance issues.
- **Fix summary:** Added `->modifyQueryUsing(fn ($query) => $query->withSum('batches', 'quantity_on_hand'))` to eagerly load the aggregate. Updated `state()` to read from the aggregate.
- **Files:** `app/Filament/Resources/DrugResource.php`
- **Status:** Fixed

### BUG-20260224-044 — StockMovementResource missing default sort
- **Severity:** Low
- **Module:** Pharmacy / Filament
- **Symptoms:** Stock movements listed in arbitrary order. Most recent movements should appear first.
- **Fix summary:** Added `->defaultSort('occurred_at', 'desc')` to the table.
- **Files:** `app/Filament/Resources/StockMovementResource.php`
- **Status:** Fixed

### BUG-20260224-045 — DispensationResource line_total not reactive
- **Severity:** Medium
- **Module:** Pharmacy / Filament
- **Symptoms:** Changing `quantity` or `unit_price` in the dispensation items repeater did not update `line_total` in real-time. Users saw stale totals.
- **Fix summary:** Made both `quantity` and `unit_price` reactive with `afterStateUpdated` callbacks that recalculate and set `line_total`.
- **Files:** `app/Filament/Resources/DispensationResource.php`
- **Status:** Fixed

### BUG-20260224-046 — Queue display view uses hardcoded English strings
- **Severity:** Medium
- **Module:** Queue / i18n
- **Symptoms:** All UI strings in the queue display blade template were hardcoded in English, preventing future localization.
- **Fix summary:** Created `lang/en/queue.php` translation file. Replaced all hardcoded strings with `__('queue.*')` helpers.
- **Files:** `resources/views/livewire/queue/display.blade.php`, `lang/en/queue.php` (new)
- **Status:** Fixed

### BUG-20260224-047 — PharmacyInventoryTest used string assertions for enum-cast fields
- **Severity:** Low
- **Module:** Tests
- **Symptoms:** After adding enum casts, test assertions like `assertSame('completed', $completed->status)` failed because `status` now returns an enum instance.
- **Fix summary:** Updated all 3 failing assertions to compare against enum instances (`DispensationStatus::Completed`, `StockMovementType::Receive`, etc.). Added enum imports.
- **Files:** `tests/Feature/PharmacyInventoryTest.php`
- **Status:** Fixed

### BUG-20260224-048 — .env injection via setup wizard
- **Severity:** Critical
- **Module:** Setup
- **Symptoms:** `SetupController::updateEnv()` wrote user-supplied values directly into `.env` without sanitising newlines, CR, or null-bytes. An attacker could inject arbitrary .env directives during initial setup.
- **Fix summary:** `updateEnv()` now strips `\n`, `\r`, `\0` from values, and properly quotes them. Added `DB::transaction` around user creation. Added `throttle:5,1` to the POST `/setup` route.
- **Files:** `app/Http/Controllers/SetupController.php`, `routes/web.php`
- **Status:** Fixed

### BUG-20260224-049 — UserFactory double-hashes passwords
- **Severity:** Critical
- **Module:** Tests / Auth
- **Symptoms:** `UserFactory` wrapped password in `Hash::make()`, but the User model's `'hashed'` cast auto-hashes on assignment, producing a double-hashed password. Factory-created users could not log in.
- **Fix summary:** Removed `Hash::make()` wrapper; factory now assigns plain `'password'` string.
- **Files:** `database/factories/UserFactory.php`
- **Status:** Fixed

### BUG-20260224-050 — Password hashes leaked into audit logs
- **Severity:** High
- **Module:** Compliance / Audit
- **Symptoms:** The `Auditable` trait logged all model attributes including `password` and `remember_token` into `audit_logs.old_values`/`new_values`. This is a HIPAA/security violation.
- **Fix summary:** Added `auditExclude()` hook (overridable per model) and `resolveAuditExclusions()` helper that always excludes `['password', 'remember_token']`. All event handlers now filter excluded keys.
- **Files:** `app/Traits/Auditable.php`
- **Status:** Fixed

### BUG-20260224-051 — Invoice status never transitions after payment
- **Severity:** High
- **Module:** Billing
- **Symptoms:** `Payment::boot()` recalculated `paid`/`balance` but never updated `Invoice::status`. An invoice remained `Issued` even after full payment.
- **Fix summary:** `recalculateInvoice()` now auto-transitions status to `Paid` (balance ≤ 0), `Partial` (partial payment), or `Issued` (no payments). Skips `Cancelled`/`Refunded` invoices.
- **Files:** `app/Models/Payment.php`
- **Tests:** `test_can_create_invoice_and_payment_and_audit_log_is_written` (updated assertions)
- **Status:** Fixed

### BUG-20260224-052 — Drug total_on_hand includes expired/inactive batches
- **Severity:** High
- **Module:** Pharmacy
- **Symptoms:** `Drug::getTotalOnHandAttribute()` summed ALL batches including expired and inactive ones, showing inflated stock counts.
- **Fix summary:** Filtered to only `is_active = true` AND non-expired batches. Also added `Auditable` trait and `scopeActive()`.
- **Files:** `app/Models/Drug.php`, `app/Models/DrugBatch.php`
- **Status:** Fixed

### BUG-20260224-053 — LabOrderTest::sample() returns rejected sample
- **Severity:** Medium
- **Module:** Lab
- **Symptoms:** `sample()` was a plain `hasOne()`, so when a sample was rejected and a new one collected, the relationship returned the oldest (rejected) sample instead of the current one.
- **Fix summary:** Changed to `hasOne(LabSample::class)->latestOfMany()`.
- **Files:** `app/Models/LabOrderTest.php`
- **Status:** Fixed

### BUG-20260224-054 — LabTestParameter::getNormalRangeFor() TypeError with Gender enum
- **Severity:** Medium
- **Module:** Lab
- **Symptoms:** Passing a `Patient::gender` (a `Gender` enum) to `getNormalRangeFor(?string $gender)` caused a TypeError because the method expected a string.
- **Fix summary:** Changed signature to `Gender|string|null $gender` with internal enum-to-value conversion. Added `scopeActive()`.
- **Files:** `app/Models/LabTestParameter.php`
- **Status:** Fixed

### BUG-20260224-055 — LabTestCatalog hardcoded currency smallest_unit
- **Severity:** Low
- **Module:** Lab / Billing
- **Symptoms:** `getFormattedCostAttribute()` divided by hardcoded `100`, which is incorrect for currencies with different subunit sizes (e.g. KWD = 1000).
- **Fix summary:** Now uses `config('intracare.currency.smallest_unit', 100)` with division-by-zero guard.
- **Files:** `app/Models/LabTestCatalog.php`
- **Status:** Fixed

### BUG-20260224-056 — QueueCounter missing scopeActive
- **Severity:** Low
- **Module:** Queue
- **Symptoms:** No way to query only active counters; inactive counters could appear in dropdowns.
- **Fix summary:** Added `scopeActive()` query scope.
- **Files:** `app/Models/QueueCounter.php`
- **Status:** Fixed

### BUG-20260224-057 — Livewire Queue Display uses hardcoded status strings
- **Severity:** Medium
- **Module:** Queue
- **Symptoms:** `getNowServingProperty` used `->whereIn('status', ['called'])` and `getWaitingProperty` used `->where('status', 'waiting')` — raw strings instead of `QueueTicketStatus` enums. With enum casts, these queries returned empty results.
- **Fix summary:** Replaced with `QueueTicketStatus::Called` and `QueueTicketStatus::Waiting`. Added `abort_unless($department->is_active, 404)` guard in `mount()`.
- **Files:** `app/Livewire/Queue/Display.php`
- **Status:** Fixed

### BUG-20260224-058 — No middleware to block deactivated users
- **Severity:** High
- **Module:** Auth / Security
- **Symptoms:** After an admin deactivates a user (`is_active = false`), that user's existing session remains valid. They can continue using the system until their session expires.
- **Fix summary:** Created `EnsureUserIsActive` middleware that checks `is_active` on every authenticated request. Logs out and redirects inactive users. Registered in `AdminPanelProvider->authMiddleware()`.
- **Files:** `app/Http/Middleware/EnsureUserIsActive.php` (new), `app/Providers/Filament/AdminPanelProvider.php`
- **Status:** Fixed

### BUG-20260224-059 — Dangerous bulk delete actions on critical resources
- **Severity:** High
- **Module:** Pharmacy / Queue / Billing
- **Symptoms:** `DeleteBulkAction` on `DispensationResource`, `DrugBatchResource`, `DrugResource`, and `QueueTicketResource` allowed mass-deleting records including completed dispensations and active inventory — corrupting stock history and audit trails.
- **Fix summary:** Removed `DeleteBulkAction` from DrugBatch, Drug, and QueueTicket resources. Hidden in Dispensation resource (kept for potential draft cleanup). Added `canCreate(): false` to `StockMovementResource` to prevent manual creation via URL.
- **Files:** `app/Filament/Resources/DispensationResource.php`, `app/Filament/Resources/DrugBatchResource.php`, `app/Filament/Resources/DrugResource.php`, `app/Filament/Resources/QueueTicketResource.php`, `app/Filament/Resources/StockMovementResource.php`
- **Status:** Fixed

### BUG-20260224-060 — QueueTicketResource allows status change on edit
- **Severity:** Medium
- **Module:** Queue
- **Symptoms:** The status field in QueueTicketResource's edit form was editable, allowing users to bypass the intended workflow actions (Call → Serve → No-show) and set arbitrary statuses.
- **Fix summary:** Disabled the status field when editing an existing record (`->disabled(fn (?QueueTicket $record) => $record !== null)`).
- **Files:** `app/Filament/Resources/QueueTicketResource.php`
- **Status:** Fixed

### BUG-20260224-061 — No forceDelete protection on legally-required records
- **Severity:** High
- **Module:** Compliance / All
- **Symptoms:** Patient, Visit, LabOrder, Invoice, and User models use SoftDeletes but had no guard against `forceDelete()`. A developer or rogue admin script could permanently destroy legally-required medical/financial records.
- **Fix summary:** Added `static::forceDeleting()` guard in `booted()` on all 5 models that throws `RuntimeException` to prevent permanent deletion.
- **Files:** `app/Models/Patient.php`, `app/Models/Visit.php`, `app/Models/LabOrder.php`, `app/Models/Invoice.php`, `app/Models/User.php`
- **Status:** Fixed

### BUG-20260224-062 — Missing PatientFactory and VisitFactory
- **Severity:** Medium
- **Module:** Tests
- **Symptoms:** No factory existed for Patient or Visit models, forcing tests to manually create records with verbose `::create()` calls and making it impossible to use `Patient::factory()` or `Visit::factory()`.
- **Fix summary:** Created `PatientFactory` (mr_number, first_name, last_name, date_of_birth, gender) and `VisitFactory` (patient_id, doctor_id, visit_number, visit_type, status, visited_at).
- **Files:** `database/factories/PatientFactory.php` (new), `database/factories/VisitFactory.php` (new)
- **Status:** Fixed

### BUG-20260224-063 — BillingTest doesn't verify invoice recalculation
- **Severity:** Medium
- **Module:** Tests / Billing
- **Symptoms:** `test_can_create_invoice_and_payment_and_audit_log_is_written` created a payment but never refreshed the invoice to check that `paid`, `balance`, and `status` were updated by `Payment::boot()`.
- **Fix summary:** Added `$invoice->refresh()` and assertions for `paid = 5000`, `balance = 5000`, `status = InvoiceStatus::Partial`.
- **Files:** `tests/Feature/BillingTest.php`
- **Status:** Fixed

### BUG-20260224-064 — Missing tests for existing-batch receive and expired-batch FEFO exclusion
- **Severity:** Medium
- **Module:** Tests / Pharmacy
- **Symptoms:** No test coverage for: (a) calling `receiveToBatch()` on an already-existing batch number, (b) FEFO allocation skipping expired batches.
- **Fix summary:** Added `test_receive_to_batch_adds_stock_to_existing_batch` and `test_fefo_skips_expired_batches` to `PharmacyInventoryTest`.
- **Files:** `tests/Feature/PharmacyInventoryTest.php`
- **Status:** Fixed

### BUG-20260224-065 — Missing foreign key indexes (SQLite performance)
- **Severity:** Low
- **Module:** Database / Performance
- **Symptoms:** PostgreSQL auto-creates indexes for foreign keys, but SQLite (used in dev/test) does not. Queries joining on FK columns performed full table scans on SQLite.
- **Fix summary:** Created migration that adds explicit indexes on all FK columns across 20+ tables, with idempotent checks to skip if already present.
- **Files:** `database/migrations/2026_02_24_140002_add_missing_foreign_key_indexes.php` (new)
- **Status:** Fixed

---

## Bugs Found in Audit Round 2

### BUG-20260224-067 — DrugResource eager-load sum includes expired/inactive batches
- **Severity:** High
- **Module:** Pharmacy
- **Symptoms:** The Drug listing table shows inflated stock-on-hand numbers because the eager-loaded `withSum('batches', 'quantity_on_hand')` query sums ALL batches, including expired and inactive ones. This contradicts the fix in BUG-052 which correctly filters the `Drug::getTotalOnHandAttribute()` accessor.
- **Root cause:** `DrugResource::table()` used an unfiltered `->withSum('batches', 'quantity_on_hand')`. The table column prefers the eager-loaded value over the model accessor, so the incorrect sum was displayed.
- **Fix summary:** Replaced the unfiltered `withSum` with a subquery-based `withSum` that applies the same filters as the model accessor: `is_active = true`, `expiry_date IS NULL OR expiry_date > now()`.
- **Files:** `app/Filament/Resources/DrugResource.php`
- **Status:** Fixed

### BUG-20260224-068 — CreateDispensation hardcodes string status instead of enum
- **Severity:** Medium
- **Module:** Pharmacy
- **Symptoms:** `CreateDispensation::mutateFormDataBeforeCreate()` sets `$data['status'] = 'draft'` as a raw string. While Laravel's enum cast can accept strings, this bypasses type safety and could cause `===` comparison failures with `DispensationStatus::Draft`.
- **Root cause:** The code was written before enum casts were introduced in BUG-029 and was not updated.
- **Fix summary:** Changed `$data['status'] = 'draft'` to `$data['status'] = DispensationStatus::Draft` and added the missing `use App\Enums\DispensationStatus` import.
- **Files:** `app/Filament/Resources/DispensationResource/Pages/CreateDispensation.php`
- **Status:** Fixed

### BUG-20260224-069 — QueueDepartmentResource allows dangerous bulk delete
- **Severity:** High
- **Module:** Queue
- **Symptoms:** The QueueDepartment table exposes a bulk delete action. Deleting departments that have associated counters, tickets, and daily sequences causes foreign key violations and orphaned records. BUG-059 removed bulk delete from other resources but missed this one.
- **Root cause:** `DeleteBulkAction` was left in the `bulkActions` array.
- **Fix summary:** Replaced the bulk actions group with an empty `->bulkActions([])`.
- **Files:** `app/Filament/Resources/QueueDepartmentResource.php`
- **Status:** Fixed

### BUG-20260224-070 — QueueCounterResource allows dangerous bulk delete
- **Severity:** High
- **Module:** Queue
- **Symptoms:** Same as BUG-069 but for queue counters. Bulk deleting counters with active tickets or history breaks the queue system.
- **Root cause:** `DeleteBulkAction` was left in the `bulkActions` array.
- **Fix summary:** Replaced the bulk actions group with an empty `->bulkActions([])`.
- **Files:** `app/Filament/Resources/QueueCounterResource.php`
- **Status:** Fixed

### BUG-20260224-071 — EditDrugBatch allows deleting batches with stock movement history
- **Severity:** Critical
- **Module:** Pharmacy
- **Symptoms:** The DrugBatch edit page exposes a delete action with no guard. Deleting a batch that has stock movement records would destroy audit trails and make stock history inconsistent. This is especially dangerous for batches that have been dispensed to patients.
- **Root cause:** `Actions\DeleteAction::make()` was present without any visibility or safety checks.
- **Fix summary:** Added `->visible()` check that only shows delete when the batch has zero `StockMovement` records. Added `->before()` callback with `abort_if` as a server-side safety net.
- **Files:** `app/Filament/Resources/DrugBatchResource/Pages/EditDrugBatch.php`
- **Status:** Fixed

### BUG-20260224-072 — EditDrug allows deleting drugs with batches/movements
- **Severity:** Critical
- **Module:** Pharmacy
- **Symptoms:** The Drug edit page allows deleting a drug that has associated batches, stock movements, and dispensation history. This would cascade-delete or orphan critical pharmacy records.
- **Root cause:** `Actions\DeleteAction::make()` was unguarded.
- **Fix summary:** Added `->visible()` check that only shows delete when the drug has zero `DrugBatch` records. Added `->before()` callback with `abort_if` safety.
- **Files:** `app/Filament/Resources/DrugResource/Pages/EditDrug.php`
- **Status:** Fixed

### BUG-20260224-073 — EditQueueTicket still has delete action (contradicts BUG-059)
- **Severity:** High
- **Module:** Queue
- **Symptoms:** BUG-059 removed `DeleteBulkAction` from `QueueTicketResource`, but the individual `DeleteAction` on the edit page was not removed. Queue tickets are historical records tied to the audit trail and should never be deleted — they represent patient visit queue history.
- **Root cause:** Only the bulk action was removed in BUG-059; the individual edit-page delete was missed.
- **Fix summary:** Removed `Actions\DeleteAction::make()` entirely from `getHeaderActions()`, leaving only the empty array.
- **Files:** `app/Filament/Resources/QueueTicketResource/Pages/EditQueueTicket.php`
- **Status:** Fixed

### BUG-20260224-074 — EditQueueDepartment allows delete without checking children
- **Severity:** High
- **Module:** Queue
- **Symptoms:** Deleting a queue department that has associated queue counters or tickets causes FK constraint violations and orphaned records. No guard was present.
- **Root cause:** `Actions\DeleteAction::make()` had no visibility/safety checks.
- **Fix summary:** Added `->visible()` check requiring zero `QueueTicket` records for the department. Added `->before()` abort safety.
- **Files:** `app/Filament/Resources/QueueDepartmentResource/Pages/EditQueueDepartment.php`
- **Status:** Fixed

### BUG-20260224-075 — EditQueueCounter allows delete without checking children
- **Severity:** High
- **Module:** Queue
- **Symptoms:** Deleting a queue counter that has been referenced in ticket call/serve events causes FK constraint violations. No guard was present.
- **Root cause:** `Actions\DeleteAction::make()` had no visibility/safety checks.
- **Fix summary:** Added `->visible()` check requiring zero `QueueTicket` records for the counter. Added `->before()` abort safety.
- **Files:** `app/Filament/Resources/QueueCounterResource/Pages/EditQueueCounter.php`
- **Status:** Fixed

### BUG-20260224-076 — PatientContact missing Auditable trait (PHI data)
- **Severity:** High
- **Module:** Patient EHR / Compliance
- **Symptoms:** `PatientContact` stores emergency contact information for patients (names, phone numbers, relationships). This is PHI-adjacent data that per HIPAA-inspired compliance rules (ai-context.md §3.5) must be audit-logged. BUG-037 added Auditable to `PatientAllergy` and `PatientChronicCondition` but missed `PatientContact`.
- **Root cause:** Model was overlooked during the BUG-037 audit fix.
- **Fix summary:** Added `use App\Traits\Auditable` import and `use Auditable` trait to `PatientContact`.
- **Files:** `app/Models/PatientContact.php`
- **Status:** Fixed

### BUG-20260224-077 — DispensationItem missing Auditable trait (financial records)
- **Severity:** High
- **Module:** Pharmacy / Compliance
- **Symptoms:** `DispensationItem` records which drugs were dispensed, at what price, and in what quantity. Changes to these records affect financial reconciliation. Without Auditable, modifications (quantity changes, price edits) go unlogged.
- **Root cause:** Model was not included in earlier audit compliance sweeps.
- **Fix summary:** Added `use Auditable` trait and import to `DispensationItem`.
- **Files:** `app/Models/DispensationItem.php`
- **Status:** Fixed

### BUG-20260224-078 — InvoiceItem missing Auditable trait (financial records)
- **Severity:** High
- **Module:** Billing / Compliance
- **Symptoms:** `InvoiceItem` records billing line items (service descriptions, amounts, quantities). Changes to invoice items directly affect financial records. Without Auditable, edits to amounts/descriptions are not tracked.
- **Root cause:** Model was not included in earlier audit compliance sweeps.
- **Fix summary:** Added `use Auditable` trait and import to `InvoiceItem`.
- **Files:** `app/Models/InvoiceItem.php`
- **Status:** Fixed

### BUG-20260224-079 — LabTestCatalog missing Auditable trait (clinical config)
- **Severity:** Medium
- **Module:** Lab / Compliance
- **Symptoms:** `LabTestCatalog` defines lab tests, their costs, TAT, and sample requirements. Changes to test definitions (especially costs or sample types) affect clinical workflows and billing. Without Auditable, such configuration changes are invisible.
- **Root cause:** Not included in earlier audit sweeps.
- **Fix summary:** Added `use Auditable` trait and import to `LabTestCatalog`.
- **Files:** `app/Models/LabTestCatalog.php`
- **Status:** Fixed

### BUG-20260224-080 — LabTestParameter missing Auditable trait (clinical config)
- **Severity:** High
- **Module:** Lab / Compliance
- **Symptoms:** `LabTestParameter` defines normal ranges and reference values for lab results. If a normal range is modified (e.g., changing a glucose threshold), this directly affects clinical interpretation. Without Auditable, such changes are untraceable — a serious clinical risk.
- **Root cause:** Not included in earlier audit sweeps.
- **Fix summary:** Added `use Auditable` trait and import to `LabTestParameter`.
- **Files:** `app/Models/LabTestParameter.php`
- **Status:** Fixed

### BUG-20260224-081 — QueueTicket call action shows counters from all departments
- **Severity:** Medium
- **Module:** Queue
- **Symptoms:** When calling a queue ticket, the counter selection dropdown shows ALL active counters across all departments. A ticket for "Pharmacy Queue" would show counters from "OPD Queue," "Lab Queue," etc. This is confusing and error-prone — staff could accidentally call a patient to the wrong department's counter.
- **Root cause:** The `Select` field for `queue_counter_id` in the Call action queried `QueueCounter::where('is_active', true)` without filtering by the ticket's `queue_department_id`.
- **Fix summary:** Added `->where('queue_department_id', $record->queue_department_id)` to the counter query, and also ensured only active counters are shown.
- **Files:** `app/Filament/Resources/QueueTicketResource.php`
- **Status:** Fixed

### BUG-20260224-082 — Enum labels use hardcoded strings instead of __() for i18n
- **Severity:** Medium
- **Module:** Pharmacy / Queue / i18n
- **Symptoms:** Three enum classes (`DispensationStatus`, `QueueTicketStatus`, `StockMovementType`) return hardcoded English strings in their `getLabel()` methods instead of using the `__()` translation helper. Per project rules (ai-context.md §3.6), all user-facing strings must go through `__()` or `trans()`.
- **Root cause:** Enums were written with literal English labels and not updated when the i18n rule was established.
- **Fix summary:** Wrapped all `getLabel()` return values in `__()` calls across all three enum files.
- **Files:** `app/Enums/DispensationStatus.php`, `app/Enums/QueueTicketStatus.php`, `app/Enums/StockMovementType.php`
- **Status:** Fixed

### BUG-20260224-083 — SetupController race condition allows duplicate admin creation
- **Severity:** High
- **Module:** Setup / Security
- **Symptoms:** The `store()` method performs a non-atomic check (`User::count() > 0`) before creating the admin user. If two concurrent POST requests hit `/setup` simultaneously, both can pass the check and each create an admin user, resulting in duplicate admin accounts with identical `employee_id` values.
- **Root cause:** No synchronization between the read (`User::count()`) and the write (`User::create()`). The existing `DB::transaction()` only wraps the user creation, not the preceding check.
- **Fix summary:** Added `Cache::lock('intracare.setup', 30)` with a non-blocking `->get()` to acquire an atomic lock before proceeding. Added a second `User::count() > 0` check inside the lock (double-check locking pattern). The lock is released in a `finally` block to guarantee cleanup. Added `use Illuminate\Support\Facades\Cache` import.
- **Files:** `app/Http/Controllers/SetupController.php`
- **Status:** Fixed
