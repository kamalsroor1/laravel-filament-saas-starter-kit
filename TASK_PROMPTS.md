# Task Prompts — Per Phase
# Use these when you want to run Claude Opus on one task at a time
# Each prompt is self-contained — paste it directly

---

## PROMPT: Task 1 — Core Base Classes

```
You are a senior Laravel 12 architect.
Read CLAUDE.md and .clauderules before writing anything.

Rules:
- declare(strict_types=1) on every file
- final on every class
- readonly on DTOs
- PestPHP for tests

Generate these files completely:

app/Core/Actions/BaseAction.php
app/Core/DTOs/BaseDTO.php
app/Core/Services/BaseService.php
app/Core/Repositories/BaseRepository.php
app/Core/Repositories/Contracts/RepositoryInterface.php
app/Core/Exceptions/DomainException.php
app/Core/Exceptions/ValidationException.php
app/Core/Exceptions/NotFoundException.php

Requirements:
- BaseAction: abstract, abstract handle() method, constructor injection
- BaseDTO: abstract readonly, fromArray() and toArray() methods
- BaseService: abstract, auto-injects LoggerInterface
- BaseRepository: abstract, $model property, implements RepositoryInterface
- RepositoryInterface: findById, findAll, create, update, delete
- Exceptions chain: ValidationException extends DomainException extends RuntimeException

After each file, write its PestPHP test.
```

---

## PROMPT: Task 2 — Module System

```
You are a senior Laravel 12 architect.
Read CLAUDE.md and .clauderules before writing anything.

Generate the Module System that powers enable/disable of all modules.

Files to create:
app/Core/Module/ModuleManager.php
app/Core/Module/ModuleLoader.php
app/Core/Module/ModuleServiceProvider.php
app/Core/Module/Contracts/ModuleInterface.php
app/Core/Module/DTOs/ModuleConfigDTO.php
config/modules.php

ModuleManager must:
- Read all Modules/*/module.json files
- Cache results in Redis (key: 'modules:config', ttl: 3600)
- Expose: isEnabled(string $alias): bool
- Expose: getAll(): Collection<ModuleConfigDTO>
- Expose: getEnabled(): Collection<ModuleConfigDTO>
- Expose: boot(): void — registers enabled module providers

ModuleConfigDTO maps module.json:
{
  "name": "Billing",
  "alias": "billing",
  "enabled": true,
  "providers": ["Modules\\Billing\\Providers\\BillingServiceProvider"],
  "requires": ["Tenancy"],
  "saas_only": true
}

config/modules.php structure:
return [
    'saas_enabled' => env('SAAS_MODE', true),
    'modules' => [
        'tenancy'       => true,
        'billing'       => true,
        'notifications' => true,
        'api'           => true,
        'webhooks'      => true,
        'ai'            => false,
        'audit_logs'    => true,
        'settings'      => true,
        'media'         => true,
    ],
];

Test scenarios:
1. isEnabled returns true for enabled module
2. isEnabled returns false for disabled module
3. getEnabled() only returns enabled modules
4. Boot registers providers for enabled modules only
5. saas_only modules are disabled when SAAS_MODE=false
```

---

## PROMPT: Task 3 — Multi-Tenancy Foundation

```
You are a senior Laravel 12 architect.
Read CLAUDE.md and .clauderules before writing anything.

Set up Multi-Tenancy using Single Database strategy with tenant_id isolation.
Package: stancl/tenancy

Files to create:
app/Core/Tenancy/Traits/BelongsToTenant.php
app/Core/Tenancy/Scopes/TenantScope.php
app/Core/Tenancy/Middleware/InitializeTenancy.php
app/Core/Tenancy/Contracts/TenantAware.php
app/Core/Tenancy/TenancyManager.php
app/Central/Models/Tenant.php
app/Central/DTOs/CreateTenantDTO.php
app/Central/Actions/CreateTenantAction.php
app/Central/Repositories/TenantRepository.php
app/Central/Events/TenantCreated.php
database/migrations/xxxx_create_tenants_table.php

Rules:
- BelongsToTenant trait: boot() applies TenantScope, creating() sets tenant_id
- TenantScope: filters WHERE tenant_id = TenancyManager::currentId()
- TenancyManager: singleton, setCurrentTenant(), getCurrentTenant(), runForTenant(Tenant, Closure)
- Tenant model: NOT scoped (central model), ULID pk, fillable name/domain/status
- Status: PHP 8.4 enum TenantStatus { Active, Suspended, Trial, Cancelled }
- CreateTenantAction: creates tenant, seeds default roles, fires TenantCreated
- Migration: tenants table — id ulid PK, name, email, domain unique, status, settings jsonb, trial_ends_at, created_at, updated_at

Critical test to include:
it('tenant scope prevents cross-tenant data access', function () {
    // Create two tenants with their own users
    // Verify user from tenant A is NOT visible when in tenant B context
    // This test must pass before any other work continues
});
```

---

## PROMPT: Task 7 — Billing Module

```
You are a senior Laravel 12 architect.
Read CLAUDE.md and .clauderules before writing anything.

Generate the Billing Module with full payment gateway abstraction.

This module is saas_only: true — skip entirely if SAAS_MODE=false.

Files to create:
Modules/Billing/module.json
Modules/Billing/src/Contracts/PaymentGatewayInterface.php
Modules/Billing/src/Gateways/StripeGateway.php
Modules/Billing/src/Gateways/PaymobGateway.php
Modules/Billing/src/Gateways/TapGateway.php
Modules/Billing/src/Factory/PaymentGatewayFactory.php
Modules/Billing/src/DTOs/ChargeDTO.php
Modules/Billing/src/DTOs/SubscriptionDTO.php
Modules/Billing/src/DTOs/PaymentResultDTO.php
Modules/Billing/src/Actions/CreateSubscriptionAction.php
Modules/Billing/src/Actions/CancelSubscriptionAction.php
Modules/Billing/src/Actions/HandleWebhookAction.php
Modules/Billing/src/Services/BillingService.php
Modules/Billing/src/Models/Plan.php
Modules/Billing/src/Models/Subscription.php
Modules/Billing/src/Models/Invoice.php
Modules/Billing/src/Events/SubscriptionActivated.php
Modules/Billing/src/Events/InvoicePaid.php
Modules/Billing/src/Events/PaymentFailed.php
Modules/Billing/database/migrations/ (3 migrations)
config/billing.php

PaymentGatewayInterface methods:
- charge(ChargeDTO): PaymentResultDTO
- subscribe(SubscriptionDTO): PaymentResultDTO
- cancel(string $subscriptionId): bool
- refund(RefundDTO): PaymentResultDTO
- handleWebhook(Request): void
- verifyWebhookSignature(Request): bool

StripeGateway: full implementation
PaymobGateway: implementation with Paymob API (https://accept.paymob.com/api)
TapGateway: implementation with Tap Payments API (https://api.tap.company/v2)

HandleWebhookAction requirements:
- Verify signature first, reject if invalid (403)
- Check idempotency: if webhook_id already in webhook_logs → skip, return 200
- Log every incoming webhook regardless of outcome
- Process in queue (dispatch WebhookProcessingJob)
- Fire domain events based on webhook type

Critical tests:
1. Same webhook ID processed twice = only one record created
2. Invalid signature = rejected
3. SubscriptionActivated event fires on successful subscription webhook
```

---

## PROMPT: Task 8 — AI Module

```
You are a senior Laravel 12 architect.
Read CLAUDE.md and .clauderules before writing anything.
The provider implementations are already defined in .clauderules — use them exactly.

Generate the AI Module.

Files to create:
Modules/AI/module.json
Modules/AI/src/Providers/AIServiceProvider.php
Modules/AI/src/Contracts/AIProviderInterface.php
Modules/AI/src/Providers/ClaudeProvider.php      ← from .clauderules
Modules/AI/src/Providers/OpenAIProvider.php      ← from .clauderules
Modules/AI/src/Providers/GeminiProvider.php      ← from .clauderules
Modules/AI/src/Factory/AIProviderFactory.php     ← from .clauderules
Modules/AI/src/DTOs/AIRequestDTO.php             ← from .clauderules
Modules/AI/src/DTOs/AIResponseDTO.php            ← from .clauderules
Modules/AI/src/Actions/GenerateCompletionAction.php
Modules/AI/src/Actions/GenerateEmbeddingAction.php
Modules/AI/src/Services/AIService.php
Modules/AI/src/Jobs/ProcessAIRequestJob.php
Modules/AI/database/migrations/xxxx_create_ai_requests_table.php
config/ai.php                                    ← from .clauderules

Additional requirements:
- AIService wraps provider + logs every request to ai_requests table
- ai_requests table: id, tenant_id, user_id, provider, model, prompt (text), 
  response (text), prompt_tokens, completion_tokens, cost_usd decimal(10,6), 
  duration_ms, created_at
- ProcessAIRequestJob: implements TenantAware, queued on 'ai' queue
- GenerateCompletionAction: calls AIService, returns AIResponseDTO
- GenerateEmbeddingAction: calls embed(), returns float[]

Packages to install:
composer require openai-php/laravel
composer require anthropics/anthropic-sdk-php
composer require google-gemini-php/laravel

Test: switching AI_PROVIDER env var changes which provider is resolved.
```

---

## PROMPT: Task 15 — Docker + CI/CD

```
You are a senior DevOps/Laravel engineer.
Read CLAUDE.md before writing anything.

Generate complete Docker and CI/CD configuration for the Enterprise SaaS Starter Kit.

Files to create:
docker-compose.yml           (development)
docker-compose.prod.yml      (production, multi-stage)
docker/nginx/default.conf
docker/php/Dockerfile        (PHP 8.4-fpm, non-root user)
docker/php/php.ini
docker/supervisor/supervisord.conf
.github/workflows/tests.yml
.github/workflows/deploy.yml
.env.example

Docker services needed:
- nginx (ports 80:80, 443:443)
- php-fpm (php 8.4, non-root user www-data)
- postgres (16-alpine, volume: postgres_data)
- redis (7-alpine, volume: redis_data)
- horizon (same image as php-fpm, cmd: php artisan horizon)
- scheduler (same image, cmd: php artisan schedule:work)
- reverb (same image, cmd: php artisan reverb:start --port=8080)
- minio (latest, ports 9000:9000, 9001:9001)
- mailpit (latest, ports 1025:1025, 8025:8025)

Dockerfile requirements:
- Multi-stage: builder stage installs composer deps, final stage is lean
- Non-root user (uid 1000)
- PHP extensions: pdo_pgsql, redis, zip, gd, intl, bcmath, pcntl, sockets
- Opcache configured for production
- No dev tools in production image

GitHub Actions tests.yml:
- Trigger: push and pull_request on any branch
- Services: postgres 16, redis 7
- Steps: checkout → setup php 8.4 → composer install → copy .env.testing → 
  php artisan key:generate → run pint (dry-run) → run phpstan → run pest

GitHub Actions deploy.yml:
- Trigger: push to main only
- Steps: build docker image → push to ghcr.io → SSH deploy (docker compose pull + up -d)

.env.example must include all variables from CLAUDE.md plus:
APP_URL, DB_*, REDIS_*, MAIL_*, AWS_* (for MinIO), 
REVERB_*, STRIPE_*, PAYMOB_*, TAP_*, 
ANTHROPIC_API_KEY, OPENAI_API_KEY, GEMINI_API_KEY,
SENTRY_LARAVEL_DSN
```
