# Release Notes - 2026-05-20

## Title

Phase 1 Foundation + Tenant DB Automation + Domain Routing (Task 4)

## Highlights

- Completed Phase 1 core architecture foundations:
  - Base Action / DTO / Service / Repository abstractions
  - Domain exception hierarchy
  - Core unit tests
- Implemented module system:
  - Module discovery from `Modules/*/module.json`
  - Enable/disable and `saas_only` handling
  - Cached module config and provider bootstrapping
- Implemented multi-tenancy foundation:
  - Central tenant model and creation flow
  - Runtime tenant context management
  - Tenant isolation tests
- Switched tenancy strategy to database-per-tenant:
  - Dynamic tenant connection switching
  - Tenant DB metadata stored per tenant
  - PostgreSQL as default tenant driver
- Added tenant provisioning automation:
  - Auto-create tenant DB on tenant creation
  - Auto-run tenant migrations and seeders
  - Rollback behavior on provisioning failure
  - Artisan commands:
    - `tenant:create`
    - `tenants:migrate`
    - `tenants:seed`
- Implemented Task 4 domain routing:
  - Custom domain + project subdomain resolution
  - Resolution priority: custom domain first
  - Safe unknown-domain 404 behavior
  - Tenant context bootstrapping through domain middleware
  - Domain mapping migration/model/repository/service/action/DTO
  - `GET /whoami` debug route behind domain-resolution middleware
  - Feature test coverage for all critical scenarios

## Verification

- Full test suite executed successfully in Docker:
  - `docker exec php-fpm php artisan test`
- Domain resolution feature tests:
  - `docker exec php-fpm php artisan test tests/Feature/Tenancy/DomainResolutionTest.php`

## Notes

- Local shell may fail `php artisan` directly if PHP is not in host PATH; use Docker commands.
- Ensure PostgreSQL user has `CREATE DATABASE` permission for tenant provisioning.
