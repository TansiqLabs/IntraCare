# IntraCare HMS

**On-premise, offline-first Hospital Management System** built with Laravel 12, FilamentPHP, and PostgreSQL. Designed to run entirely on a local network (LAN) with zero internet dependency.

---

## Features

- **Patient EHR** — Demographics, contacts, allergies, chronic conditions, visit history
- **Outpatient (OPD)** — Visit registration, diagnosis (ICD-10/11), prescriptions
- **Pathology / Lab** — Department management, test catalog with parameters, sample tracking, result entry with reference ranges
- **Billing** — Invoices, line items, multi-method payments, automatic balance calculation
- **RBAC** — Role-based access control (Admin, Doctor, Nurse, Pathologist, Pharmacist, Receptionist) with granular permissions
- **HIPAA-compliant Audit Trail** — Append-only audit log for all PHI access/modification
- **Full-text Search** — Local Meilisearch-powered patient and test catalog search
- **Dark Mode** — Default dark theme with Sky accent throughout
- **Setup Wizard** — First-time admin account creation via web browser
- **Automated Backups** — Scheduled PostgreSQL dumps with configurable retention
- **File Organization** — WordPress-style year/month directory structure for uploads

---

## Tech Stack

| Component       | Technology              |
|-----------------|------------------------|
| Framework       | Laravel 12             |
| Admin Panel     | FilamentPHP v3         |
| Database        | PostgreSQL 16+         |
| Cache / Queue   | Redis                  |
| Search Engine   | Meilisearch (local)    |
| Frontend Build  | Vite + Tailwind CSS v4 |
| RBAC            | Spatie Permission v6   |
| Primary Keys    | ULID (26-char string)  |
| PHP Version     | 8.2+                   |

---

## Prerequisites

Install the following on your server/machine before proceeding:

### Ubuntu / Debian

```bash
# PHP 8.3 + required extensions
sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-pgsql php8.3-redis \
    php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath \
    php8.3-intl php8.3-gd php8.3-tokenizer

# PostgreSQL 16
sudo apt install -y postgresql-16 postgresql-client-16

# Redis
sudo apt install -y redis-server

# Node.js 20+ (for Vite build)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Composer (PHP package manager)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Meilisearch (local search engine)
curl -L https://install.meilisearch.com | sh
sudo mv ./meilisearch /usr/local/bin/
```

---

## Installation

### 1. Clone / Copy the project

```bash
cd /path/to/your/directory
# If using git:
git clone <repository-url> IntraCare
cd IntraCare
```

### 2. Install PHP dependencies

```bash
composer install --optimize-autoloader --no-dev
```

> For development, omit `--no-dev`.

### 3. Install Node.js dependencies & build assets

```bash
npm install
npm run build
```

### 4. Environment configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and update these values as needed:

```dotenv
APP_NAME=IntraCare
APP_URL=http://localhost        # or your LAN IP, e.g. http://192.168.1.100

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=intracare
DB_USERNAME=intracare
DB_PASSWORD=secret              # change in production!

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700

BACKUP_PATH=/path/to/IntraCare-Backups   # absolute path for DB backups
```

### 5. Create the PostgreSQL database

```bash
sudo -u postgres psql -c "CREATE USER intracare WITH PASSWORD 'secret' CREATEDB;"
sudo -u postgres psql -c "CREATE DATABASE intracare OWNER intracare;"
```

### 6. Run database migrations

```bash
php artisan migrate
```

### 7. Start required services

```bash
# Ensure PostgreSQL and Redis are running
sudo systemctl start postgresql
sudo systemctl start redis-server

# Start Meilisearch (in background)
meilisearch --http-addr 127.0.0.1:7700 --no-analytics &
```

### 8. Launch the application

**Development:**
```bash
php artisan serve
```

**Production (Nginx + PHP-FPM):**
Point your Nginx `root` to the `public/` directory. See [Laravel Deployment Docs](https://laravel.com/docs/12.x/deployment).

### 9. First-time setup

Open your browser and navigate to:

```
http://localhost:8000/setup
```

You will be prompted to:
1. Enter your **Hospital Name**
2. Create the **Admin account** (name, email, password)

After completing setup, you'll be redirected to the admin dashboard at `/admin`.

---

## Daily Operations

### Start all services (development)

```bash
# Terminal 1 — Meilisearch
meilisearch --http-addr 127.0.0.1:7700 --no-analytics

# Terminal 2 — Laravel
php artisan serve

# Terminal 3 — Queue worker (for background jobs)
php artisan queue:work redis --sleep=3 --tries=3

# Terminal 4 — Vite dev server (only during development)
npm run dev
```

### Run scheduled tasks

Add this cron entry to execute Laravel's scheduler every minute:

```bash
* * * * * cd /path/to/IntraCare && php artisan schedule:run >> /dev/null 2>&1
```

**Scheduled tasks include:**
- `02:00` — Automated PostgreSQL backup (`db:backup`)
- `03:00` — Search index refresh (Patient, LabTestCatalog)
- Daily — Clear expired password reset tokens

---

## Database Backups

Backups are stored as compressed `.sql.gz` files at the path configured in `BACKUP_PATH`.

```bash
# Manual backup
php artisan db:backup

# Backups are automatically cleaned after 30 days (configurable in config/intracare.php)
```

**Backup directory structure:**
```
/path/to/IntraCare-Backups/
├── intracare_2026-01-15_020000.sql.gz
├── intracare_2026-01-16_020000.sql.gz
└── ...
```

---

## File Uploads

Uploaded files are organized in a WordPress-style year/month directory structure:

```
storage/app/public/
├── uploads/
│   └── 2026/
│       ├── 01/
│       ├── 02/
│       └── ...
└── patients/
    └── {patient-ulid}/
        └── 2026/
            └── 01/
```

---

## Configuration

Application-specific settings are in `config/intracare.php`:

| Setting                      | Default       | Description                              |
|------------------------------|---------------|------------------------------------------|
| `hospital_name`              | env value     | Displayed throughout the application     |
| `mr_number_prefix`           | `MR`          | Prefix for patient MR numbers            |
| `backup.enabled`             | `true`        | Enable/disable automated backups         |
| `backup.retention_days`      | `30`          | Days to keep backups before cleanup      |
| `file_upload.max_size_kb`    | `5120` (5 MB) | Maximum file upload size                 |
| `session.lifetime_minutes`   | `15`          | Session timeout for security             |
| `lab.barcode_prefix`         | `LAB`         | Prefix for lab sample barcodes           |
| `lab.auto_verify`            | `false`       | Auto-verify lab results (requires auth)  |

---

## Roles & Permissions

| Role          | Description                                          |
|---------------|------------------------------------------------------|
| Admin         | Full system access, user management, configuration   |
| Doctor        | Patient records, visits, diagnoses, prescriptions    |
| Nurse         | View patients, record vitals, view prescriptions     |
| Pathologist   | Lab management, result entry, verification           |
| Pharmacist    | View prescriptions, manage dispensing                |
| Receptionist  | Patient registration, visit creation, billing        |

---

## Project Structure

```
IntraCare/
├── app/
│   ├── Console/Commands/     # Artisan commands (DatabaseBackup)
│   ├── Enums/                # PHP 8.1+ backed enums (15 enums)
│   ├── Helpers/              # FileUploadPath helper
│   ├── Http/
│   │   ├── Controllers/      # SetupController
│   │   └── Middleware/        # EnsureInstalled
│   ├── Models/               # 20 Eloquent models (ULID PKs)
│   ├── Providers/            # Service providers
│   └── Traits/               # Auditable, HasUlid
├── config/
│   ├── intracare.php         # Application-specific config
│   ├── filesystems.php       # Local + backup disks (no cloud)
│   └── ...
├── database/
│   ├── migrations/           # 21 migration files
│   └── seeders/              # Roles, permissions, demo data
├── lang/en/                  # English translations (6 files)
├── resources/views/setup/    # Setup wizard Blade view
├── routes/
│   ├── web.php               # Setup routes + root redirect
│   └── console.php           # Scheduled tasks
└── .env.example              # Environment template
```

---

## Security Notes

- **Offline-first**: No external API calls, no CDN dependencies, no telemetry
- **Session encryption** enabled by default (`SESSION_ENCRYPT=true`)
- **HTTPS** enforced in production (configure `APP_ENV=production`)
- **Strict model binding** prevents lazy loading N+1 queries
- **HIPAA audit trail** on all patient health information models
- **Password policy**: Minimum 8 characters, mixed case, numbers required
- **Redis sessions** with 15-minute timeout
- **CSRF protection** on all forms

---

## Troubleshooting

| Problem                          | Solution                                                    |
|----------------------------------|-------------------------------------------------------------|
| "Connection refused" on DB       | `sudo systemctl start postgresql`                           |
| Redis connection error           | `sudo systemctl start redis-server`                         |
| Search not working               | Start Meilisearch: `meilisearch --http-addr 127.0.0.1:7700` |
| Styles missing                   | Run `npm run build`                                         |
| Setup wizard not showing         | Clear cache: `php artisan cache:clear && php artisan config:clear` |
| Permission denied on backup path | `sudo mkdir -p /path/to/backups && sudo chown www-data:www-data /path/to/backups` |

---

## License

Proprietary — All rights reserved.
