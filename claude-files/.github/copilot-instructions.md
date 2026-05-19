# GitHub Copilot Instructions
# Enterprise SaaS Starter Kit — Laravel 12

Always read `CLAUDE.md` before suggesting code.

## Architecture (strict)
- Controllers call Actions only
- Actions use Services and fire Events
- DTOs everywhere — no raw arrays between layers
- Repositories for all DB access
- Filament = UI only

## PHP style
- `declare(strict_types=1)` on every file
- `final` on every class
- `readonly` on all DTOs
- PHP 8.4 features: constructor promotion, enums, match, fibers

## AI Providers
Use `AIProviderInterface` — supports `claude`, `openai`, `gemini`
Never call SDKs directly.

## Multi-tenancy
Every tenant model needs `BelongsToTenant` trait.
Every tenant table needs `tenant_id` FK.
Never bypass `TenantScope`.

## Testing
PestPHP only. Every Action and Service needs a test.
