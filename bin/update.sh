#!/usr/bin/env bash
set -euo pipefail

say() { printf "\n\033[1;36m==>\033[0m %s\n" "$1"; }
warn() { printf "\n\033[1;33m[warn]\033[0m %s\n" "$1"; }

die() {
  printf "\n\033[1;31m[error]\033[0m %s\n" "$1" >&2
  exit 1
}

backup=false
for arg in "$@"; do
  case "$arg" in
    --backup) backup=true ;;
    -h|--help)
      cat <<'EOF'
Usage: ./bin/update.sh [--backup]

--backup   run `php artisan db:backup` before updating
EOF
      exit 0
      ;;
  esac
done

command -v php >/dev/null 2>&1 || die "PHP is not installed (required)."
command -v composer >/dev/null 2>&1 || die "Composer is not installed (required)."
command -v npm >/dev/null 2>&1 || warn "npm was not found. Frontend build will fail until Node.js/npm is installed."

say "IntraCare: update"

if [[ "$backup" == "true" ]]; then
  say "Creating DB backup"
  php artisan db:backup || warn "Backup command failed (continuing)."
fi

if command -v git >/dev/null 2>&1 && [[ -d .git ]]; then
  say "Pulling latest code (git pull)"
  git pull --ff-only
else
  warn "Not a git clone (skipping git pull). If you installed from a ZIP, replace the code folder manually (keep .env + storage/)."
fi

say "Updating PHP dependencies"
composer install --optimize-autoloader --no-dev

say "Running database migrations"
php artisan migrate --force

say "Building frontend assets"
npm install
npm run build

say "Refreshing caches"
php artisan optimize

say "Done ✅"
