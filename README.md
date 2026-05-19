# Enterprise SaaS Starter Kit

Enterprise-ready starter kit built with Laravel 12, PHP 8.4, Filament 4, PostgreSQL, Redis, and modular multi-tenancy.

## Requirements

- Docker Desktop 4+
- Make
- Git

## Quick Start

```bash
git clone <repo>
cd saas-starter-kit
make install
```

Windows PowerShell:

```powershell
git clone <repo>
cd saas-starter-kit
.\scripts\dev.ps1 -Task install
```

## Available URLs After Install

- App: http://localhost
- Super Admin Panel: http://localhost/central
- Mailpit: http://localhost:8025
- CloudBeaver (Database UI): http://localhost:8978
- MinIO Console: http://localhost:9001
- Horizon: http://localhost/horizon
- Pulse: http://localhost/pulse
- Telescope: http://localhost/telescope

## SaaS Mode vs Standalone

```env
SAAS_MODE=true   # Multi-tenant SaaS with billing
SAAS_MODE=false  # Single company, no billing
```

## Switching AI Provider

```env
AI_PROVIDER=claude    # Anthropic Claude (default)
AI_PROVIDER=openai    # OpenAI GPT-4o
AI_PROVIDER=gemini    # Google Gemini
```

## Enable or Disable Modules

Edit `config/modules.php` to toggle modules on/off per environment.

## Planning

### Phase 1: Core Platform

- Core base classes (`Action`, `Service`, `DTO`, `Repository`)
- Module manager + module bootstrapping
- Multi-tenant foundation with tenant isolation

### Phase 2: Domain Routing

- Project subdomain support (`{project}.{central_domain}`)
- Custom domain mapping per tenant/project
- Domain verification flow and DNS validation checks
- SSL/TLS readiness (proxy or managed certificate strategy)

### Phase 3: SaaS Modules

- Billing gateways + webhooks
- AI provider abstraction
- Notifications, audit logs, media, settings

### Phase 4: Hardening

- Performance and cache strategy
- Security checks and policy coverage
- CI/CD quality gates and deployment playbooks

## Project Subdomain and Custom Domain

- `Project Subdomain`: each project can be exposed as a subdomain like `project-a.example.com`.
- `Custom Domain`: each project can map one or more external domains like `app.client.com`.
- Routing priority: custom domain first, then project subdomain fallback.
- Every domain resolution must map to the correct tenant/project context before any query.
- Add integration tests for:
  - subdomain resolves correct project
  - custom domain resolves correct project
  - unknown domain returns safe 404/not-found tenant context

## Common Make Commands

| Target | Description |
|------|---------|
| `make install` | Full fresh install |
| `make up` | Start containers |
| `make down` | Stop containers |
| `make restart` | Restart containers |
| `make build` | Build images with no cache |
| `make shell` | Open shell in `php-fpm` |
| `make tinker` | Run Laravel Tinker |
| `make migrate` | Run migrations |
| `make fresh` | Fresh migrations + seed |
| `make seed` | Run seeders |
| `make test` | Run parallel test suite |
| `make test-coverage` | Run tests with coverage |
| `make pint` | Run Pint formatter |
| `make phpstan` | Run PHPStan analysis |
| `make horizon` | Run Horizon command |
| `make logs` | Tail compose logs |
| `make ps` | Show running services |
| `make prune` | Prune Docker resources |

Windows PowerShell equivalent:

- `.\scripts\dev.ps1 -Task up`
- `.\scripts\dev.ps1 -Task down`
- `.\scripts\dev.ps1 -Task test`
- `.\scripts\dev.ps1 -Task pint`
- `.\scripts\dev.ps1 -Task phpstan`

## Running Tests

```bash
make test
make test-coverage
make pint
make phpstan
```

## Local Database Port

- PostgreSQL is mapped to host port `5433` to avoid common local conflicts.
- CloudBeaver connection values:
  - Host: `postgres`
  - Port: `5432`
  - Database: value of `DB_DATABASE` in `.env` (default: `saas_db`)
  - Username: value of `DB_USERNAME` in `.env` (default: `saas_user`)
  - Password: value of `DB_PASSWORD` in `.env` (default: `saas_password`)

- Direct PostgreSQL client values from host:
  - Host: `127.0.0.1`
  - Port: `5433`
  - Database: value of `DB_DATABASE` in `.env` (default: `saas_db`)
  - System: `PostgreSQL`
  - Username: value of `DB_USERNAME` in `.env` (default: `saas_user`)
  - Password: value of `DB_PASSWORD` in `.env` (default: `saas_password`)

## Default Credentials

- Super Admin: `admin@example.com` / `password`

## AI History

- Project keeps AI execution history under `ai history/`.
- AI role must read history before starting a task and append today's file after finishing.
- Daily file format: `ai history/YYYY-MM-DD.md`.
- Reusable template: `ai history/_template.md`.

## Implementation Status

### Completed Tasks

1. Phase 1 Task 1: Core base architecture classes
- `BaseAction`, `BaseDTO`, `BaseService`, `BaseRepository`, repository contract, and domain exceptions.
- Unit coverage for core base classes.

2. Phase 1 Task 2: Module system
- Module loader/manager/service provider.
- `config/modules.php` and enable/disable logic with cache.
- Feature tests for module enablement, saas-only behavior, and provider boot.

3. Phase 1 Task 3: Multi-tenancy foundation
- Central `Tenant` model, tenant creation action/repository/event/DTO.
- `TenancyManager` database-per-tenant runtime switching.
- Tenant provisioning service + automation commands:
  - `tenant:create`
  - `tenants:migrate`
  - `tenants:seed`
- Feature tests for tenant creation and tenant isolation.

4. Phase 2 Task 4 (started and implemented): Domain routing
- Domain mapping model/migration/repository/service/action/DTO.
- Domain resolution middleware with strict order:
  1) custom domain
  2) project subdomain
  3) safe 404 fallback
- `/whoami` debug endpoint behind `tenancy.resolve-domain`.
- Feature tests for subdomain/custom domain/collision priority/unknown domain.

### Current Position

- Current progress is at **Phase 2 domain routing implementation** with test coverage in place.
- Tenant database strategy is **PostgreSQL per tenant**.

### TODO (Next Practical Steps)

1. Hook real project/domain onboarding workflow from admin/UI to create `domain_mappings` automatically.
2. Add domain ownership verification flow (DNS/HTTP verification) before activating custom domains.
3. Add wildcard/dev DNS guidance and local host routing notes for real browser domain testing.
4. Add production-safe tenant database provisioning guardrails (permissions, retry policy, observability).
5. Continue with Phase 3 modules:
- Billing
- AI module standardization
- Notifications / audit logs / media / settings
