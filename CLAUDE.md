# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Testing
- `composer test` - Run full test suite with config clearing
- `php artisan test` - Run tests directly via Artisan
- Tests use Pest PHP framework with PHPUnit as the base
- Test database is configured to use SQLite in-memory for speed

### Development Server
- `composer dev` - Start complete development environment (server, queue, logs, vite)
- `php artisan serve` - Start Laravel development server only
- `npm run dev` - Start Vite development server for asset compilation
- `npm run build` - Build production assets

### Code Quality
- `php artisan pint` - Format PHP code using Laravel Pint (available via composer require-dev)

### Database
- `php artisan migrate` - Run database migrations
- Uses SQLite database located at `database/database.sqlite`

## Architecture Overview

### Framework Stack
- **Laravel 12** with **Livewire** as the primary frontend framework
- **Livewire Volt** for simplified component creation
- **Livewire Flux** for UI components
- **TailwindCSS v4** for styling
- **Vite** for asset bundling

### Key Architectural Patterns

#### Livewire Volt Integration
- Volt components are mounted in two locations via `VoltServiceProvider`:
  - `resources/views/livewire/` - Standard Livewire components
  - `resources/views/pages/` - Page-level Volt components
- Routes use `Volt::route()` for direct component routing (see `routes/web.php`)

#### Authentication Structure
- Complete authentication system using Livewire components
- Email verification, password reset, and registration flows
- Protected routes use `auth` and `verified` middleware
- Settings area with profile, password, and appearance management

#### Testing Setup
- Pest PHP as testing framework with Laravel plugin
- Feature tests for authentication flows and settings
- Test configuration automatically uses in-memory SQLite
- RefreshDatabase trait available but commented out in `tests/Pest.php`

### Project Structure Notes
- Controllers are minimal - most logic in Livewire components
- Views follow Livewire/Volt patterns with Blade templates
- Flux UI components provide consistent interface elements
- TailwindCSS v4 with Vite plugin for styling

### Development Workflow
- Use `composer dev` for full development stack
- SQLite database auto-created if missing
- Vite handles hot module replacement for CSS/JS
- Queue processing and logging included in dev command

## Project Context

This is **CAP TRACK** - a comprehensive data management platform for Community Action Partnership agencies. The system handles participant/household data, program services, and CSBG reporting requirements.

### Single Test Execution
- `php artisan test --filter TestName` - Run specific test class
- `php artisan test tests/Feature/specific_test.php` - Run specific test file