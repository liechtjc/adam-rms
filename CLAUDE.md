# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AdamRMS is an advanced Rental Management System for Theatre, AV & Broadcast. It provides comprehensive asset management, project tracking, client management, and billing capabilities for rental businesses. Licensed under AGPLv3.

## Technology Stack

- **Backend**: PHP 8.0+ with Twig 3.7 templating
- **Database**: MySQL 8.0 via custom `adam-rms/mysqli-database-class` wrapper
- **Key Libraries**: `moneyphp/money` (currency), `firebase/php-jwt` (auth), `aws/aws-sdk-php` (S3 storage), `robmorgan/phinx` (migrations)
- **Infrastructure**: Docker (PHP 8.3-Apache), GitHub Actions CI/CD

## Git Workflow

This project is a fork of [adam-rms/adam-rms](https://github.com/adam-rms/adam-rms).
We maintain the ability to sync with upstream when needed.

### Branch Structure
- **`main`** - Pure mirror of upstream adam-rms/adam-rms (NO custom changes here)
- **`stage`** - Our customizations and development (staging environment)
- **`production`** - Live deployment (production environment)

### Syncing with Upstream (On-Demand Only)

**Only sync when explicitly needed** (e.g., to get a specific upstream feature or important bug fix):
```bash
# Sync main with upstream (only when explicitly requested)
git checkout main
git fetch upstream
git merge upstream/main
git push origin main

# Merge upstream updates into our custom work
git checkout stage
git merge main
# Resolve conflicts if any (our customizations may conflict with upstream changes)
git push origin stage

# After testing on stage, update production
git checkout production
git merge stage
git push origin production
```

**Important:** Do NOT automatically sync with upstream. Only pull updates when:
- A critical security fix is released upstream
- A specific upstream feature is needed
- Explicitly decided to incorporate upstream changes

### Feature Development

All custom development happens on `stage` or feature branches from `stage`:
```bash
# Create feature branch from stage
git checkout stage
git pull origin stage
git checkout -b feature/description

# Develop and commit
git add .
git commit -m "Description of changes"
git push origin feature/description

# Merge back to stage
git checkout stage
git merge feature/description
git push origin stage

# Cleanup
git branch -d feature/description
git push origin --delete feature/description
```

### Environment Promotion

#### Deploy to Stage (Testing)
All custom features go to stage first:
```bash
git checkout stage
git push origin stage
```
- Triggers automated deployment to staging environment
- Test thoroughly before promoting to production

#### Deploy to Production (Live)
Only after successful testing on stage:
```bash
git checkout production
git merge stage
git push origin production
```
- Triggers automated deployment to production environment

### Deployment Configuration
- Docker compose files in `deploy/` directory
  - `deploy/docker-compose.stage.yml` - Staging environment configuration
  - `deploy/docker-compose.prod.yml` - Production environment configuration
- Automated deployment system watches `stage` and `production` branches
- See DEPLOYMENT_NOTES.md for infrastructure-specific details

### Branch Naming Conventions
- Features: `feature/description` (branched from `stage`)
- Bug fixes: `fix/description` (branched from `stage`)
- Hotfixes: `hotfix/description` (branched from `production` in emergencies)

### Important Rules
⚠️ **NEVER commit custom changes to `main`** - it must stay pure for upstream sync  
⚠️ **ALL custom work happens on `stage`** or feature branches from `stage`  
⚠️ **ALWAYS test on `stage` before promoting to `production`**  
⚠️ **NEVER automatically sync with upstream** - only on explicit request  
⚠️ **When syncing upstream, always test thoroughly on `stage` before `production`**

### Hotfix Workflow (Emergency Only)
If production is broken and needs immediate fix:
```bash
# Create hotfix from production
git checkout production
git checkout -b hotfix/critical-issue
# Make fix and test
git commit -m "Hotfix: critical issue"

# Merge to production
git checkout production
git merge hotfix/critical-issue
git push origin production

# Backport to stage
git checkout stage
git merge production
git push origin stage

# Cleanup
git branch -d hotfix/critical-issue

## Development Commands

```bash
# Start development environment (auto-runs migrations and seeds)
docker-compose -f .devcontainer/docker-compose.yml up

# Access points:
# - App: http://localhost:8080
# - phpMyAdmin: http://localhost:8082
# - Mailpit: http://localhost:8083

# Database migrations
php vendor/bin/phinx migrate
php vendor/bin/phinx seed:run

# Install dependencies
composer install
```

## Architecture

### Request Flow

- **Web Pages**: `*.php` controller → `headSecure.php` → sets `$PAGEDATA` → renders `*.twig`
- **API Endpoints**: `api/**/*.php` → `apiHeadSecure.php` → `finish()` with JSON response
- **Public Pages**: Login pages use `head.php` (no auth required)

### Global Variables

- `$DBLIB` - Database wrapper for parameterized queries
- `$AUTH` - Authentication/authorization with user data and permissions
- `$TWIG` - Template engine instance
- `$CONFIG` - Application configuration
- `$PAGEDATA` - Data array for Twig templates
- `$bCMS` - Business logic helpers (audit logging, S3, sanitization)

### Directory Structure

- `src/` - Main application code
  - `api/` - RESTful API endpoints (~28 modules)
  - `common/` - Shared initialization and utilities
    - `libs/Auth/` - Permission system (serverActions.php, instanceActions.php)
    - `libs/bCMS/` - Business logic helpers
  - Feature modules: `assets/`, `project/`, `clients/`, `instances/`, etc.
- `db/migrations/` - Phinx database migrations (40+)

## Database Patterns

```php
// Always use $DBLIB for parameterized queries
$DBLIB->where("projects.instances_id", $AUTH->data['instance']['instances_id']);
$DBLIB->where("projects_deleted", 0);  // Always check soft delete
$DBLIB->join("clients", "projects.clients_id=clients.clients_id", "LEFT");
$projects = $DBLIB->get("projects", null, ["projects_id", "projects_name"]);

// INSERT
$DBLIB->insert("tableName", ["column1" => $value1]);

// UPDATE
$DBLIB->where("id", $id);
$DBLIB->update("tableName", ["column" => $newValue]);
```

## API Response Format

```php
// Success
finish(true, false, ["data" => $result]);

// Error
finish(false, ["code" => "ERROR_CODE", "message" => "Human readable error"]);
```

## Permission System (Two-Tier)

### Server Permissions
Global permissions across all instances. Defined in `src/common/libs/Auth/serverActions.php`.

```php
if (!$AUTH->serverPermissionCheck("USERS:EDIT")) die($TWIG->render('404.twig', $PAGEDATA));
```

### Instance Permissions
Specific to individual instances/businesses. Defined in `src/common/libs/Auth/instanceActions.php`.

```php
if (!$AUTH->instancePermissionCheck("ASSETS:CREATE")) die($TWIG->render('404.twig', $PAGEDATA));

// API endpoint pattern
if (!$AUTH->instancePermissionCheck("PROJECTS:EDIT"))
    finish(false, ["code" => "AUTH-ERROR", "message" => "No auth for action"]);
```

### Adding New Permissions

Add to `instanceActions.php` or `serverActions.php`:

```php
'CUSTOM:NEW_FEATURE:VIEW' => [
    'Category' => 'Custom',
    'Table' => 'New Feature',
    'Type' => 'View',
    'Combined Text Description' => 'Custom - New Feature: View',
    'Supported Token Types' => ["web-session"],
],
```

## Critical Patterns

### Multi-tenancy (Always Required)
```php
$DBLIB->where("instances_id", $AUTH->data['instance']['instances_id']);
```

### Soft Deletes (Always Check)
```php
$DBLIB->where("table_deleted", 0);
```

### Money Handling
```php
use Money\Currency;
use Money\Money;

$amount = new Money($valueInCents, new Currency($AUTH->data['instance']['instances_config_currency']));
$formatted = apiMoney($amount);
```

### Audit Logging
```php
$bCMS->auditLog($actionType, $table, $data, $userid, $useridTo, $projectid, $targetid);
```

## Naming Conventions

- **Database columns**: `snake_case` with table prefix (e.g., `projects_id`, `users_userid`)
- **PHP variables**: `camelCase`
- **Classes**: `PascalCase`
- **Constants**: `UPPERCASE`

## Code Review Checklist

- Authentication and authorization checks present
- Instance scoping on all queries (`instances_id`)
- Soft delete flag checks (`*_deleted = 0`)
- Parameterized queries via `$DBLIB` (no SQL concatenation)
- Proper error handling with `finish()` for APIs
- UK English spelling (enforced by Reviewdog)
