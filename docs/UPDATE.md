# Updating IntraCare (copy/paste checklist)

> Goal: update the app **without losing data**.

## 0) Backup (recommended)

- `php artisan db:backup`

## 1) Pull latest code

If you installed via Git:

- `git pull --ff-only`

If you installed from a ZIP release, replace the code folder with the new version **without** deleting your `.env` and `storage/`.

## 2) Update PHP dependencies

- `composer install --optimize-autoloader --no-dev`

Alternatively (one command):

- `composer run app:update`

## 3) Run migrations

- `php artisan migrate --force`

## 4) Rebuild frontend assets

- `npm install`
- `npm run build`

## 5) Clear/refresh caches

- `php artisan optimize`

## 6) Restart services

Restart your web server / PHP-FPM and queue workers (if used).

## Troubleshooting

- If something looks stale: `php artisan config:clear && php artisan cache:clear && php artisan view:clear`
