# Contributing to IntraCare

Thanks for taking the time to contribute! ❤️

## Ways to contribute

- Report bugs (with reproducible steps)
- Suggest improvements
- Submit pull requests
- Improve documentation

## Ground rules

- Be respectful and professional (see `CODE_OF_CONDUCT.md`)
- **Do not include PHI/PII** (patient data) in issues, screenshots, logs, or sample databases
- Keep changes focused and small when possible

## Development setup

### Requirements

- PHP 8.2+
- Composer
- Node.js 20+
- PostgreSQL 16+ (recommended)

### Quick start

1. Copy env and generate key

   - `cp .env.example .env`
   - `php artisan key:generate`

2. Install dependencies

   - `composer install`
   - `npm install`

3. Run migrations

   - `php artisan migrate`

4. Start the dev stack

   - `composer run dev`

## Code style

- PHP: run `vendor/bin/pint`
- Keep Laravel conventions (controllers thin, business logic in services)

## Tests

Run the test suite:

- `composer test`

Please add/adjust tests for bug fixes and behavior changes.

## Submitting a pull request

1. Fork the repo and create a feature branch
2. Make your changes (+ tests)
3. Ensure `composer test` passes
4. Open a PR with:
   - What changed
   - Why it changed
   - How it was tested

## Reporting bugs

- Prefer GitHub Issues
- Also feel free to add a log entry to `BUG_TRACKER.md` when fixing bugs
