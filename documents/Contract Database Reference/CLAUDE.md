# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 9 application for managing contracts, programs, funders, and report due dates. It was initially generated using QuickAdminPanel and includes authentication, role-based permissions, audit logging, and media handling.

## Key Commands

### Development
```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Set up environment configuration
cp .env.example .env
php artisan key:generate

# Run database migrations and seeders
php artisan migrate
php artisan db:seed

# Build frontend assets
npm run dev        # Development build
npm run production # Production build
npm run watch      # Watch for changes

# Start local development server
php artisan serve
```

### Testing
```bash
# Run PHPUnit tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run browser tests (Laravel Dusk)
php artisan dusk
```

### Database Commands
```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Run seeders
php artisan db:seed
```

### Cache Management
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache configuration and routes
php artisan config:cache
php artisan route:cache
```

## Architecture Overview

### MVC Structure
- **Models**: Located in `app/Models/`. Key models include:
  - `Contract`: Core entity with relationships to programs, funders, and report due dates
  - `User`, `Role`, `Permission`: Authentication and authorization
  - `Program`, `Funder`, `ReportDueDate`, `DueDateEmail`, `Report`: Business entities
  - All models use soft deletes and include the Auditable trait

- **Controllers**: Organized in `app/Http/Controllers/`:
  - `Admin/`: Administrative interface controllers with full CRUD operations
  - `Frontend/`: User-facing controllers with limited permissions
  - `Auth/`: Authentication controllers (login, registration, password reset)
  - Controllers use traits for common functionality (MediaUploadingTrait, CsvImportTrait)

- **Views**: Blade templates in `resources/views/`:
  - `admin/`: Admin panel views
  - `frontend/`: User interface views
  - `layouts/`: Layout templates (admin, frontend, app)
  - Uses AdminLTE v3 for admin interface, Tailwind CSS for frontend

### Key Features & Patterns

1. **Authentication & Authorization**
   - Uses Laravel Breeze for authentication
   - Role-based permissions system with Gates
   - Middleware: `auth`, `admin`, `ApprovalMiddleware`
   - Default registration role configurable in `config/panel.php`

2. **Media Handling**
   - Spatie Media Library for file uploads
   - Supports document attachments on Contracts and Reports
   - PDF processing capabilities (parsing, OCR with Tesseract)

3. **Data Import/Export**
   - CSV import functionality via CsvImportTrait
   - Contract export to text functionality
   - Spreadsheet processing with PhpSpreadsheet

4. **Audit Trail**
   - Auditable trait tracks all model changes
   - Audit logs viewable in admin panel

5. **API Integration**
   - Microsoft Graph integration for email (Laravel MSGraph Mail)
   - OAuth2 client support

### Database Schema
- Uses MySQL by default
- Key tables: contracts, programs, funders, report_due_dates, reports, users, roles, permissions
- Many-to-many relationships via pivot tables
- All tables include timestamps and soft deletes

### Frontend Stack
- Laravel Mix for asset compilation
- Tailwind CSS for styling
- Alpine.js for interactive components
- DataTables for data grids
- CKEditor for rich text editing

### Configuration
- Environment variables in `.env`
- Application config in `config/` directory
- Date/time formats defined in `config/panel.php`
- Multi-language support structure (currently English only)

## Important Conventions

1. All routes are grouped by access level (admin, frontend, auth)
2. Form requests handle validation (Store/Update requests)
3. Mass delete functionality on all CRUD resources
4. Global search functionality available
5. All dates use the format configured in `config/panel.php`
6. Media files stored via Spatie Media Library conventions