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
- **Laravel 11** with **Livewire** as the primary frontend framework
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

## CAP TRACK Database Schema

### Core Models Implemented
- **Program** - Programs with services, contracts, and work plans
- **Service** - Program-specific services with start/end dates
- **Contract** - Funding contracts with documents and terms
- **Participant** - Individual records with CSBG Module 4 Section C compliance
- **Household** - Family units with income tracking and poverty level calculations
- **WorkPlan** - Annual work plans per program
- **WorkPlanGoal** - Goals mapped to services with target vs actual tracking
- **PovertyGuideline** - Federal poverty guidelines for 2025 (monthly/annual)

### Role-Based Access Control
- **Role** - User roles (admin, program manager, staff, etc.)
- **Permission** - Granular permissions by category
- **UserRole** - Many-to-many user role assignments
- **UserProgram** - Program-specific access control

### Key Features
- Automatic household code generation (7-digit alphanumeric)
- CSBG compliance with all required demographic fields
- Poverty level calculations using 2025 federal guidelines
- JSON fields for complex data (income sources, benefits, documents)
- Full Eloquent relationships between all models