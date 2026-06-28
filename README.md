# Core App

A **reusable starter/foundation** for freelance web development projects with frontend and admin backend.

**Goal:** Copy this project to start new client projects quickly without starting from scratch, reducing development time and improving margins.

> **AI-first development**: AI writes code/tests/docs - humans review before deploy
> Before any work, **read [`CLAUDE.md`](./CLAUDE.md)** - the supreme rules (10 invariants)

## Features

- **Authentication** with Laravel Fortify (login, register, password reset, email verification) — official React starter kit
- **RBAC** with Spatie Permission (roles & permissions)
- **User Management** CRUD in admin panel
- **Single-tenant** architecture (1 client = 1 deploy = 1 database)
- **SEO-friendly** frontend with Blade
- **Modern admin** with Inertia + React + TypeScript

## Tech Stack

- **Laravel 13** (PHP 8.3+)
- **Frontend** (`/...`) = Blade + Tailwind (SEO-friendly, easy deploy)
- **Admin** (`/admin/...`) = Inertia + React 19 + TypeScript + shadcn/ui
- **Database** = MySQL/MariaDB (DB-agnostic via Eloquent)
- **Dev** = Laravel Sail (Docker)
- **Deploy** = cPanel/VPS compatible

## Requirements

- PHP 8.3+
- Composer 2.x
- Node.js 20+ (for building assets)
- Docker & Docker Compose (for Sail)

## Quick Start (with Sail)

```bash
# 1. Clone the repository
git clone <repo-url> myproject
cd myproject

# 2. Copy environment file
cp .env.example .env

# 3. Configure for Sail (edit .env)
# Set these values:
# DB_CONNECTION=mariadb
# DB_HOST=mariadb
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=sail
# DB_PASSWORD=password
# (queue/cache/session already set to 'database' driver — cPanel-safe)

# 4. Install PHP dependencies and start Sail
composer install
./vendor/bin/sail up -d

# 5. Generate app key
./vendor/bin/sail artisan key:generate

# 6. Run migrations and seed data
./vendor/bin/sail artisan migrate --seed

# 7. Install and build frontend assets
./vendor/bin/sail npm install --legacy-peer-deps
./vendor/bin/sail npm run build

# 8. Access the application
# Default: http://localhost (or http://localhost:8080 if APP_PORT=8080)
```

## Development

```bash
# Start development environment
./vendor/bin/sail up -d

# Run frontend dev server (hot reload)
./vendor/bin/sail npm run dev

# Or run both Laravel and Vite together
./vendor/bin/sail composer dev
```

## Running Tests

```bash
# Run all tests
./vendor/bin/sail artisan test

# Run with coverage (if configured)
./vendor/bin/sail artisan test --coverage

# Run specific test file
./vendor/bin/sail artisan test tests/Feature/UserControllerTest.php
```

## Code Quality

```bash
# PHP code style (Laravel Pint)
./vendor/bin/sail pint

# Check PHP style without fixing
./vendor/bin/sail pint --test

# TypeScript/ESLint check
./vendor/bin/sail npm run build
```

## Default Credentials

After running `migrate --seed`, an initial admin user is created:

- **Email:** `admin@example.com`
- **Password:** `password`
- **Role:** `admin`
- **Login URL:** `/admin/login`

> After first login, create and manage other users via **User Management** (`/admin/users`).
> **Important:** Change these credentials in production!

## Project Structure

```
core/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Admin controllers (Inertia)
│   │   │   ├── Auth/           # Authentication controllers
│   │   │   └── Frontend/       # Frontend controllers (Blade)
│   │   ├── Middleware/
│   │   └── Requests/           # Form Request validation
│   └── Models/                 # Eloquent models
├── database/
│   ├── migrations/             # Database migrations
│   ├── factories/              # Model factories for testing
│   └── seeders/                # Database seeders
├── resources/
│   ├── js/
│   │   ├── components/         # Shared React components (+ ui/ = shadcn)
│   │   ├── layouts/            # Layout components
│   │   └── pages/
│   │       ├── admin/          # Admin pages (React/Inertia)
│   │       ├── auth/           # Auth pages (from starter kit)
│   │       └── settings/       # Settings pages (from starter kit)
│   └── views/                  # Blade templates (frontend)
├── routes/
│   ├── web.php                 # Frontend + dashboard routes
│   ├── admin.php               # Admin routes (/admin/...)
│   └── settings.php            # Settings routes (from starter kit)
├── tests/
│   ├── Feature/                # Feature tests
│   └── Unit/                   # Unit tests
├── docs/                       # Documentation
│   ├── architecture/           # Architecture docs + ADR
│   └── delivery/               # Delivery plans
├── CLAUDE.md                   # AI agent instructions (invariants)
└── README.md                   # This file
```

## Routes

| Route | Description |
|-------|-------------|
| `/` | Welcome page (Blade frontend) |
| `/admin/login` | Admin login (no public registration — ADR-0002) |
| `/admin` · `/admin/dashboard` | Admin dashboard |
| `/admin/users` | User management (CRUD + role assignment) |

## Key Invariants

1. **cPanel-safe** - No Redis/daemon processes required
2. **DB-agnostic** - Works with MySQL and MariaDB
3. **Single-tenant** - No tenant isolation, use RBAC for permissions
4. **Separate frontend/backend** - Blade for SEO frontend, Inertia+React for admin
5. **Soft delete** for master data
6. **Database-based** queue, cache, and session (no Redis dependency)

For full invariants, see [`CLAUDE.md`](./CLAUDE.md).

## Deploy to cPanel/VPS

1. Upload all files to your hosting
2. Point document root to `/public`
3. Configure `.env` with production values
4. Run `php artisan migrate --seed` (or without `--seed` for production)
5. Run `php artisan config:cache && php artisan route:cache`
6. Set up cron for scheduler (optional):
   ```
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```

## Documentation

| File | Description |
|------|-------------|
| [`CLAUDE.md`](./CLAUDE.md) | AI instructions + invariants |
| [`docs/architecture/`](./docs/architecture/) | Architecture, infrastructure, ADR |
| [`docs/delivery/`](./docs/delivery/) | Scaffold plan, tasks, DoD |

## License

This project is proprietary software.
