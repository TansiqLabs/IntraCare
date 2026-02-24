#!/usr/bin/env bash
set -euo pipefail

say() { printf "\n\033[1;36m==>\033[0m %s\n" "$1"; }
warn() { printf "\n\033[1;33m[warn]\033[0m %s\n" "$1"; }

die() {
  printf "\n\033[1;31m[error]\033[0m %s\n" "$1" >&2
  exit 1
}

command -v php >/dev/null 2>&1 || die "PHP is not installed (required)."
command -v composer >/dev/null 2>&1 || die "Composer is not installed (required)."
command -v npm >/dev/null 2>&1 || warn "npm was not found. Frontend build will fail until Node.js/npm is installed."

say "IntraCare: first-time setup"

if [[ ! -f .env ]]; then
  say "Creating .env from .env.example"
  cp .env.example .env
fi

say "Running installer (composer script)"
composer run setup

say "Done ✅"
printf "\nOpen: http://localhost:8000/setup\n\n"
