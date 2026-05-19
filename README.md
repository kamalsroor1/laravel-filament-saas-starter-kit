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

## Default Credentials

- Super Admin: `admin@example.com` / `password`
