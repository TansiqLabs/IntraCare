# IntraCare — Hospital Management System (HMS)

> **AI Context File** — Read this file at the start of every session to restore full project context.
> Last updated: 2026-02-25

---

## 1. Project Overview

| Attribute         | Value                                                                 |
|-------------------|-----------------------------------------------------------------------|
| **Name**          | IntraCare HMS                                                         |
| **Type**          | On-premise, offline-first, web-based Hospital Management System       |
| **Deployment**    | Local intranet (LAN) — 7-story hospital                               |
| **Internet**      | **None.** Zero external internet dependency at runtime.               |
| **Interface**     | Web-browser only (No Electron)                                        |
| **Primary Users** | Admin, Doctor, Nurse, Pathologist, Pharmacist, Receptionist           |

---

## 2. Tech Stack

| Layer            | Technology                                                         |
|------------------|--------------------------------------------------------------------|
| **Framework**    | Laravel 12 (12.x latest stable)                                   |
| **Admin Panel**  | FilamentPHP v3                                                     |
| **CSS**          | Tailwind CSS (custom views, prints, responsive layouts)            |
| **Database**     | PostgreSQL                                                         |
| **Search**       | Meilisearch (via Laravel Scout) — runs locally                     |
| **Queue/Cache**  | Redis (local)                                                      |
| **Printing**     | Raw thermal POS printing (ESC/POS) via browser                     |
| **Barcode**      | Barcode scanners acting as HID keyboard input                      |

---

## 3. Strict Rules & Standards

### 3.1 Security
- **OWASP Top-10** compliance on every endpoint.
- No deprecated, outdated, or vulnerable Composer/NPM packages.
- CSRF, XSS, SQL-injection protection enforced by default.
- All sensitive fields encrypted at rest (`casts: 'encrypted'`).
- Password policy: bcrypt with cost 12; enforce minimum complexity.
- Session: HTTP-only, Secure (when TLS enabled), SameSite=Lax cookies.
- Rate-limiting on login & sensitive routes.

### 3.2 HIPAA-Inspired Compliance
- **Audit Trail**: Every CRUD operation on patient/medical data MUST be logged immutably (who, what, when, old_value, new_value, IP).
- Audit log table is append-only; no UPDATE/DELETE allowed at application level.
- Access to PHI (Protected Health Information) restricted by RBAC.
- Session timeout after configurable idle period (default 15 min).

### 3.3 Medical Coding
- Support **ICD-10 / ICD-11** diagnosis code references.
- Codes stored in a dedicated seedable lookup table.

### 3.4 UI/UX & Responsive Design
- **100% mobile-responsive** — tablets and smartphones on the intranet.
- Tailwind CSS used for all custom views, queue displays, print templates.
- FilamentPHP panels must also be responsive.

### 3.5 Theme
- **Dark/Light mode toggle** available globally.
- **Dark mode is the default.**
- Persisted per-user in `user_preferences` or browser `localStorage`.

### 3.6 Localization (i18n)
- Default language: **English**.
- Use standard Laravel `lang/` directory structure.
- Admin can add/switch languages without code changes.
- All UI strings must use `__()` / `trans()` helpers — no hard-coded text.

### 3.7 Printing
- Thermal POS receipt printing (invoices, tokens, labels).
- A4/Letter PDF generation for lab reports, prescriptions.
- Barcode generation on labels (Code-128 / QR).

---

## 4. Core Modules

### 4.1 Patient EHR (Electronic Health Record)
- Patient registration with demographics, photo, emergency contacts.
- Medical history (allergies, chronic conditions, surgeries).
- Visit records (OPD encounters) linked to doctor, diagnosis, prescriptions.
- Real-time patient search powered by Meilisearch.
- ICD-10/ICD-11 coding on diagnoses.

### 4.2 Pathology / Lab Workflow
**Flow:** Doctor prescribes tests → Billing/Payment → Invoice & Token generated → Sample Collection → Report Entry → Report Delivery.

- **Test Catalog**: Categories → Sub-categories → Tests → Parameters.
- **Dynamic Parameters**: Each test has N parameters with name, unit, normal range (age/gender-aware), method.
- **Sample Types**: Blood, Urine, Stool, Swab, etc.
- **Report Entry**: Technician enters results; Pathologist verifies/approves.
- **Barcode Labels**: Printed at sample collection with patient + test info.

### 4.3 Queue & Token Management
- **Token Generation**: Sequential per-department per-day, auto-reset daily.
- **Floor-wise Live Queue Display**: Full-screen Tailwind CSS pages (no JS framework needed; Livewire polling or SSE).
- **Status Flow**: Waiting → In-Progress → Completed / No-Show.
- **Counters**: Multiple serving counters per department.

### 4.4 Pharmacy
- Drug master list with generic name, brand, formulation, strength.
- Inventory management with batch tracking.
- **Barcode scanning** for dispensing (HID keyboard input).
- **Expiry tracking** with configurable alert thresholds.
- **Low-stock alerts** (per-drug reorder level).
- Linked to OPD prescriptions for dispensing workflow.

### 4.5 Double-Entry Accounting
- **Chart of Accounts** (Assets, Liabilities, Equity, Revenue, Expenses).
- **Journal Entries** with mandatory double-entry validation (debits = credits).
- **General Ledger** with running balances.
- **Auto-sync**: Lab invoices, Pharmacy sales, OPD billing automatically generate journal entries.
- **Reports**: Trial Balance, Profit & Loss, Balance Sheet, Cash Flow.
- **Fiscal Year** support with period locking.

### 4.6 RBAC (Role-Based Access Control)
| Role            | Access Scope                                              |
|-----------------|-----------------------------------------------------------|
| **Admin**       | Full system access, user management, settings, audit logs |
| **Doctor**      | OPD visits, prescriptions, lab orders, patient EHR (own)  |
| **Nurse**       | Vitals entry, queue management, limited EHR view          |
| **Pathologist** | Lab workflow, report entry/verification                   |
| **Pharmacist**  | Drug dispensing, inventory, pharmacy billing               |
| **Receptionist**| Patient registration, billing, token generation           |

- Uses **Spatie Laravel-Permission** (roles + permissions).
- Permissions are granular: `view-patients`, `edit-lab-report`, `approve-lab-report`, etc.
- FilamentPHP Shield plugin for admin-panel permission management.

---

## 5. Architecture Decisions

| Decision                     | Choice                                                    |
|------------------------------|-----------------------------------------------------------|
| **Multi-tenancy**            | Single-tenant (one hospital)                              |
| **Soft Deletes**             | Yes, on all critical models                               |
| **UUIDs**                    | Primary keys use `ULID` (ordered, sortable, no collision) |
| **Timestamps**               | All tables have `created_at`, `updated_at`                |
| **Audit**                    | Custom `audit_logs` table (append-only)                   |
| **Money**                    | Stored as `bigint` (cents/paisa) — no float               |
| **Timezone**                 | Server-local timezone; stored as UTC concept not needed (offline) |
| **File Storage**             | Local disk, year/month dirs (WordPress-style) — no cloud  |
| **Queue Driver**             | Redis (for background jobs: reports, alerts)              |
| **Broadcasting**             | Reverb / Pusher-compatible local server or polling        |

---

## 6. Directory & Naming Conventions

```
app/
├── Enums/              # PHP 8.1+ backed enums
├── Models/             # Eloquent models (one per table)
├── Filament/
│   ├── Admin/          # Admin panel resources
│   ├── Doctor/         # Doctor panel (if separate)
│   └── ...
├── Services/           # Business logic services
├── Actions/            # Single-purpose action classes
├── Observers/          # Model observers (audit logging)
├── Policies/           # Authorization policies
└── Traits/             # Shared model traits (Auditable, etc.)
```

- **Models**: Singular PascalCase (`Patient`, `LabTest`).
- **Tables**: Plural snake_case (`patients`, `lab_tests`).
- **Foreign Keys**: `{singular_table}_id` (`patient_id`).
- **Pivot Tables**: Alphabetical order (`doctor_patient`).

---

## 7. Session Notes

> Use this section to append per-session progress notes.

### Session 1 — 2026-02-24
- Created `ai-context.md`.
- Designed foundational DB schema (Users, RBAC, Patient EHR, Pathology/Lab).
- Schema approved by owner.
- Scaffolded Laravel 12.52.0 project (upgraded from spec'd Laravel 11, approved).
- Installed: FilamentPHP v3.3.49, Spatie Permission v6.24, Laravel Scout v10.24, Meilisearch PHP v1.16, Filament Shield v3.9.
- Created 15 PHP Backed Enums in `app/Enums/`.
- Created 21 migration files (Users, RBAC, Audit, Patients, EHR, Lab, Billing).
- Created 20 Eloquent Models in `app/Models/` with relationships.
- Created `Auditable` trait (HIPAA audit logging) + `HasUlid` trait.
- Created `RolesAndPermissionsSeeder` (6 roles, 30+ permissions).
- Created `AdminUserSeeder` (admin@intracare.local / password).
- Configured `.env` for PostgreSQL, Redis (session/cache/queue), Meilisearch.
- Configured FilamentPHP AdminPanel (dark mode default, Sky theme, responsive).
- Created English lang files (`general`, `patient`, `visit`, `lab`, `billing`).
- **Pending**: PostgreSQL database creation (sudo needed), migrations, `db:seed`.

### Session 2 — 2026-02-24 (continued)
- **AWS/Cloud removal**: Stripped all AWS, S3, SES, Postmark, Resend, Slack references from `config/services.php`, `config/filesystems.php`, `.env`, `.env.example`.
- **Local DB Backup**: Created `app/Console/Commands/DatabaseBackup.php` — `pg_dump` to `.sql.gz`, scheduled daily at 02:00, configurable retention (30 days default). Backups stored at `BACKUP_PATH` env variable.
- **File Upload Organization**: Created `app/Helpers/FileUploadPath.php` — WordPress-style year/month paths (e.g., `uploads/2026/01`, `patients/{id}/2026/01`).
- **Setup Wizard**: Created `app/Http/Controllers/SetupController.php`, `app/Http/Middleware/EnsureInstalled.php`, `resources/views/setup/index.blade.php`. First-time admin account created via `/setup` web route. Seeds roles/permissions automatically. Updates `APP_NAME` in `.env`.
- **Production Hardening**: `app/Providers/AppServiceProvider.php` — force HTTPS in production, strict model binding, lazy loading prevention, slow query logging (>500ms), offline-compatible password defaults (no `Password::uncompromised()`).
- **Custom Config**: Created `config/intracare.php` — hospital_name, MR prefix, backup settings, file upload limits, session timeout, lab barcode/auto-verify settings.
- **Scheduled Tasks**: `routes/console.php` — `db:backup` daily 02:00, Scout imports (Patient, LabTestCatalog) daily 03:00, `auth:clear-resets` daily.
- **Offline-first Fix**: Replaced Tailwind CDN in setup Blade view with `@vite()` directive. Removed `Password::uncompromised()` (requires internet).
- **.env Sync**: Updated `.env.example` — `LOG_STACK=daily`, `SESSION_ENCRYPT=true`, `BACKUP_PATH`, Scout/Meilisearch vars.
- **README.md**: Replaced default Laravel README with comprehensive IntraCare documentation — prerequisites, installation steps, daily operations, backup guide, troubleshooting.
- **Frontend Build**: `npm install` + `npm run build` — Vite + Tailwind CSS v4 compiled successfully (43 KB CSS, 37 KB JS).
- **lang/en/setup.php**: Created localization strings for setup wizard.
- **Pending**: PostgreSQL database creation (sudo password required), `php artisan migrate`, `php artisan db:seed`, then visit `/setup` to create admin.

