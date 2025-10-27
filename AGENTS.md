# Repository Guidelines

## Project Structure & Module Organization
- `app/` holds domain logic: controllers under `Http/Controllers`, Livewire components, and supporting DTOs/Services.
- `resources/views` and `resources/js` provide Blade templates and Vite assets; Tailwind sources live in `resources/css`.
- `routes/web.php` and `routes/api.php` define entry points; `routes/console.php` handles scheduled artisan tasks.
- `database/migrations`, `factories`, and `seeders` track schema and demo data; exports persist in `storage/app/public`.
- `tests/Feature` and `tests/Unit` contain Pest specs with shared helpers in `tests/Pest.php`.
- `public/` exposes the Laravel front controller and compiled assets for Apache.

## Build, Test, and Development Commands
- `composer install` and `npm install` bootstrap PHP and Node dependencies.
- `cp .env.example .env && php artisan key:generate` seeds local environment secrets.
- `composer dev` runs Laravel serve, the queue listener, log streaming, and Vite in parallel.
- `php artisan migrate --seed` applies migrations and seeders; add `--database=` for alternate connections.
- `npm run build` compiles production assets; `npm run dev` keeps the asset pipeline hot while editing.
- `php artisan config:clear` refreshes caches after changing `.env` or config files.

## Coding Style & Naming Conventions
- PHP follows PSR-12 with 4-space indentation; run `./vendor/bin/pint` before committing.
- Livewire/Volt components use PascalCase class names and kebab-case Blade filenames (e.g., `resources/views/livewire/program-management.blade.php`).
- JavaScript modules in `resources/js` export ES modules with 4-space indents; prefer descriptive camelCase symbols.
- Environment variables use upper snake case within `.env`; custom config files mirror their service name.

## Testing Guidelines
- Run `php artisan test` (Pest) for the suite; `./vendor/bin/pest --group=feature` narrows scope while iterating.
- Name new specs `*Test.php` and favor Pest `it()`/`describe()` blocks that read as sentences.
- Use database refresh helpers (`uses(RefreshDatabase::class)`) when mutations occur; seed reference data via factories or dedicated seeders.

## Commit & Pull Request Guidelines
- Commit messages follow Conventional Commits (`feat:`, `fix:`, `chore:`) using action-oriented imperatives.
- PRs include a concise summary, related ticket links, schema or queue changes flagged, and screenshots or logs for UI updates.
- Confirm migrations and long-running jobs ship with rollback or monitoring notes in the description.

## Configuration & Security Notes
- Never commit `.env` or credentials; keep secrets, exports, and queue logs inside `storage/`.
- Queue workers depend on the configured driver—mirror production settings before testing job-heavy features.
- After deploying, run `php artisan config:clear && php artisan migrate --force` to sync configuration and schema.
