# Master Prompt — Enterprise SaaS Starter Kit
# Give this entire prompt to Claude Opus to generate all remaining files

---

You are a senior Laravel 12 architect.
Your task is to generate all foundational files for an Enterprise SaaS Starter Kit.

Read and strictly follow every rule in `CLAUDE.md` and `.clauderules` before writing any code.

---

## Project Stack
- PHP 8.4 + Laravel 12
- Filament 4 (UI layer only)
- PostgreSQL + Redis
- stancl/tenancy (Single DB, tenant_id isolation)
- PestPHP for all tests
- Docker (already configured)

## Non-negotiable rules
- declare(strict_types=1) on every file
- final on every class unless designed for extension
- readonly on all DTO properties
- No business logic in Controllers or Filament
- No direct DB:: calls — use Repositories
- No direct SDK calls for AI or Payments — use Interfaces
- Every Action gets a PestPHP test
- All important operations fire Events

---

## TASK LIST — generate in this exact order

Generate each task completely before moving to the next.
For each task: list files → write full code → write test → note events/migrations needed.

---

### TASK 1 — Core Base Classes

Create the following base abstract classes:

**Files to create:**
```
app/Core/Actions/BaseAction.php
app/Core/DTOs/BaseDTO.php
app/Core/Services/BaseService.php
app/Core/Repositories/BaseRepository.php
app/Core/Repositories/Contracts/RepositoryInterface.php
app/Core/Exceptions/DomainException.php
app/Core/Exceptions/ValidationException.php
app/Core/Exceptions/NotFoundException.php
```

Rules:
- BaseAction: abstract class with abstract handle() method, constructor injection ready
- BaseDTO: abstract readonly class with fromArray() and toArray() methods
- BaseService: abstract class with $logger property auto-injected
- BaseRepository: abstract class with $model property, implements RepositoryInterface
- RepositoryInterface: defines findById, findAll, create, update, delete
- All exceptions extend DomainException which extends \RuntimeException

---

### TASK 2 — Module System

Create the module manager that reads module.json files and enables/disables modules.

**Files to create:**
```
app/Core/Module/ModuleManager.php
app/Core/Module/ModuleLoader.php
app/Core/Module/ModuleServiceProvider.php
app/Core/Module/Contracts/ModuleInterface.php
app/Core/Module/DTOs/ModuleConfigDTO.php
config/modules.php
```

Rules:
- ModuleManager: reads all Modules/*/module.json, checks enabled flag, caches results in Redis
- ModuleLoader: boots all enabled modules, registers their ServiceProviders
- ModuleServiceProvider: base provider that all module providers extend
- ModuleInterface: defines getName(), getAlias(), isEnabled(), getProviders()
- ModuleConfigDTO: maps module.json to a typed object
- config/modules.php: master switch per module + global saas_enabled flag

ModuleManager must expose:
```php
isEnabled(string $alias): bool
getAll(): Collection
getEnabled(): Collection
boot(): void
```

Test: verify enable/disable logic reads from config correctly.

---

### TASK 3 — Multi-Tenancy Foundation

Set up stancl/tenancy with Single DB strategy.

**Files to create:**
```
app/Core/Tenancy/Traits/BelongsToTenant.php
app/Core/Tenancy/Scopes/TenantScope.php
app/Core/Tenancy/Middleware/InitializeTenancy.php
app/Core/Tenancy/Middleware/EnsureTenantContext.php
app/Core/Tenancy/Contracts/TenantAware.php
app/Core/Tenancy/TenancyManager.php
app/Central/Models/Tenant.php
app/Central/DTOs/CreateTenantDTO.php
app/Central/Actions/CreateTenantAction.php
app/Central/Actions/SuspendTenantAction.php
app/Central/Repositories/TenantRepository.php
app/Central/Events/TenantCreated.php
app/Central/Events/TenantSuspended.php
database/migrations/xxxx_create_tenants_table.php
```

Rules:
- BelongsToTenant trait: auto-applies TenantScope, auto-sets tenant_id on create
- TenantScope: global scope that filters by current tenant_id from context
- TenantAware interface: for Queue jobs that must carry tenant context
- TenancyManager: sets/gets current tenant, provides runForTenant(Tenant, Closure)
- Tenant model: central model (not scoped), has id (ULID), name, domain, status enum
- CreateTenantAction: creates tenant, fires TenantCreated event
- Migration: tenants table with ulid PK, name, domain, status, settings jsonb, timestamps

Test: tenant isolation — user from tenant A cannot see data from tenant B.

---

### TASK 4 — Authentication System

**Files to create:**
```
app/Tenant/Models/User.php
app/Tenant/DTOs/RegisterUserDTO.php
app/Tenant/DTOs/LoginDTO.php
app/Tenant/Actions/RegisterUserAction.php
app/Tenant/Actions/LoginAction.php
app/Tenant/Actions/LogoutAction.php
app/Tenant/Actions/Enable2FAAction.php
app/Tenant/Actions/Verify2FAAction.php
app/Tenant/Services/AuthService.php
app/Tenant/Services/TwoFactorService.php
app/Tenant/Repositories/UserRepository.php
app/Tenant/Events/UserRegistered.php
app/Tenant/Events/UserLoggedIn.php
app/Tenant/Events/TwoFactorEnabled.php
database/migrations/xxxx_create_users_table.php
database/migrations/xxxx_create_sessions_table.php
```

Rules:
- User model: uses BelongsToTenant, HasRoles (spatie), has ULID pk
- RegisterUserAction: validates uniqueness per tenant, hashes password, fires UserRegistered
- LoginAction: authenticates, checks 2FA if enabled, logs device
- TwoFactorService: generates TOTP secret, verifies codes using TOTP standard
- Sessions: store device info (user_agent, ip, last_active)

Test: registration fires event, login with wrong password fails, 2FA verification works.

---

### TASK 5 — Roles & Permissions

**Files to create:**
```
app/Core/Auth/Enums/SystemRole.php
app/Core/Auth/Enums/SystemPermission.php
app/Central/Seeders/SuperAdminSeeder.php
app/Tenant/Seeders/DefaultRolesSeeder.php
app/Tenant/Actions/AssignRoleAction.php
app/Tenant/Actions/SyncPermissionsAction.php
```

Rules:
- SystemRole enum: SUPER_ADMIN, TENANT_ADMIN, TENANT_MEMBER, API_USER
- SystemPermission enum: all permissions as cases (tenants.create, billing.manage, etc.)
- SuperAdminSeeder: creates super admin user with all permissions
- DefaultRolesSeeder: creates default roles for new tenants (runs on TenantCreated)
- AssignRoleAction: assigns role to user within tenant context

---

### TASK 6 — Filament Panels Setup

**Files to create:**
```
app/Central/Filament/Providers/CentralPanelProvider.php
app/Tenant/Filament/Providers/TenantPanelProvider.php
app/Central/Filament/Resources/TenantResource.php
app/Central/Filament/Pages/Dashboard.php
app/Tenant/Filament/Pages/Dashboard.php
```

Rules:
- CentralPanelProvider: panel ID 'central', domain-based, only SuperAdmin role
- TenantPanelProvider: panel ID 'tenant', subdomain-based, per-tenant navigation
- TenantResource: table (name, domain, status, created_at), actions (suspend, activate)
- Both panels: UI ONLY — all actions delegate to Action classes
- Navigation items: hidden when module is disabled (check ModuleManager)

---

### TASK 7 — Billing Module

**Files to create:**
```
Modules/Billing/module.json
Modules/Billing/src/Providers/BillingServiceProvider.php
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
Modules/Billing/database/migrations/xxxx_create_plans_table.php
Modules/Billing/database/migrations/xxxx_create_subscriptions_table.php
Modules/Billing/database/migrations/xxxx_create_invoices_table.php
config/billing.php
```

Rules:
- PaymentGatewayInterface: charge, subscribe, cancel, refund, handleWebhook
- StripeGateway: full implementation using stripe-php SDK
- PaymobGateway: stub with TODO comments for Paymob API
- TapGateway: stub with TODO comments for Tap API
- HandleWebhookAction: verifies signature, processes idempotently, logs all events
- Subscriptions: tenant_id FK, plan_id FK, status enum, trial_ends_at, ends_at
- BillingService: setupFreeTrial(Tenant), isActive(Tenant), getPlan(Tenant)

Test: subscription creation fires event, webhook idempotency (same webhook twice = one record).

---

### TASK 8 — AI Module

**Files to create:**
```
Modules/AI/module.json
Modules/AI/src/Providers/AIServiceProvider.php
Modules/AI/src/Contracts/AIProviderInterface.php
Modules/AI/src/Providers/ClaudeProvider.php
Modules/AI/src/Providers/OpenAIProvider.php
Modules/AI/src/Providers/GeminiProvider.php
Modules/AI/src/Factory/AIProviderFactory.php
Modules/AI/src/DTOs/AIRequestDTO.php
Modules/AI/src/DTOs/AIResponseDTO.php
Modules/AI/src/Actions/GenerateCompletionAction.php
Modules/AI/src/Actions/GenerateEmbeddingAction.php
Modules/AI/src/Services/AIService.php
Modules/AI/src/Jobs/ProcessAIRequestJob.php
Modules/AI/database/migrations/xxxx_create_ai_requests_table.php
config/ai.php
```

Rules:
- Use code from .clauderules exactly as specified for the 3 providers
- AIService: wraps provider, logs every request+response to ai_requests table
- ProcessAIRequestJob: async AI processing, implements TenantAware
- ai_requests table: tenant_id, provider, model, prompt_tokens, completion_tokens, cost, duration_ms

---

### TASK 9 — Notifications Module

**Files to create:**
```
Modules/Notifications/module.json
Modules/Notifications/src/Providers/NotificationsServiceProvider.php
Modules/Notifications/src/Contracts/NotificationChannelInterface.php
Modules/Notifications/src/Channels/DatabaseChannel.php
Modules/Notifications/src/Channels/EmailChannel.php
Modules/Notifications/src/Services/NotificationService.php
Modules/Notifications/src/Actions/SendNotificationAction.php
Modules/Notifications/src/Actions/MarkAsReadAction.php
Modules/Notifications/src/Models/NotificationLog.php
Modules/Notifications/database/migrations/xxxx_create_notification_logs_table.php
```

Rules:
- NotificationChannelInterface: send(NotifiableInterface, NotificationDTO): void
- NotificationService: resolves channels, sends, logs delivery status
- DatabaseChannel: stores in notification_logs with tenant scope
- notification_logs: tenant_id, user_id, type, channel, data jsonb, read_at, sent_at

---

### TASK 10 — Settings Module

**Files to create:**
```
Modules/Settings/module.json
Modules/Settings/src/Providers/SettingsServiceProvider.php
Modules/Settings/src/Settings/GeneralSettings.php
Modules/Settings/src/Settings/BillingSettings.php
Modules/Settings/src/Settings/NotificationSettings.php
Modules/Settings/src/Services/SettingsService.php
Modules/Settings/src/Actions/UpdateSettingsAction.php
Modules/Settings/filament/Pages/GeneralSettingsPage.php
```

Rules:
- Use spatie/laravel-settings for all settings classes
- GeneralSettings: app_name, logo, timezone, locale
- BillingSettings: trial_days, default_currency, tax_rate
- NotificationSettings: email_enabled, sms_enabled, push_enabled
- All settings: tenant-scoped (different values per tenant)
- SettingsService: getCached(), clearCache(), update()
- GeneralSettingsPage: Filament page, calls UpdateSettingsAction only

---

### TASK 11 — API Module

**Files to create:**
```
Modules/API/module.json
Modules/API/src/Providers/APIServiceProvider.php
Modules/API/routes/api.php
Modules/API/src/Middleware/TenantApiAuth.php
Modules/API/src/Middleware/ApiRateLimit.php
Modules/API/src/Controllers/BaseApiController.php
Modules/API/src/Resources/BaseApiResource.php
Modules/API/src/Actions/CreateApiTokenAction.php
Modules/API/src/Actions/RevokeApiTokenAction.php
Modules/API/src/DTOs/ApiResponseDTO.php
```

Rules:
- All routes under /api/v1/ prefix
- TenantApiAuth: validates Sanctum token, sets tenant context
- ApiRateLimit: per-tenant rate limiting using Redis
- BaseApiController: standard response methods (success, error, paginated)
- ApiResponseDTO: { data, meta, errors, status } consistent structure
- Versioning: routes/api/v1/, easy to add v2 later

---

### TASK 12 — Webhooks Module

**Files to create:**
```
Modules/Webhooks/module.json
Modules/Webhooks/src/Providers/WebhooksServiceProvider.php
Modules/Webhooks/src/Models/WebhookEndpoint.php
Modules/Webhooks/src/Models/WebhookDelivery.php
Modules/Webhooks/src/Services/WebhookDispatcher.php
Modules/Webhooks/src/Jobs/DispatchWebhookJob.php
Modules/Webhooks/src/Actions/CreateEndpointAction.php
Modules/Webhooks/src/Actions/RetryDeliveryAction.php
Modules/Webhooks/database/migrations/xxxx_create_webhook_endpoints_table.php
Modules/Webhooks/database/migrations/xxxx_create_webhook_deliveries_table.php
```

Rules:
- WebhookDispatcher: listens to domain events, dispatches to tenant endpoints
- DispatchWebhookJob: HTTP POST with HMAC-SHA256 signature header, 3 retries with backoff
- WebhookDelivery: logs request payload, response code, response body, duration
- Signature: X-Webhook-Signature: sha256=<hmac>

---

### TASK 13 — AuditLogs Module

**Files to create:**
```
Modules/AuditLogs/module.json
Modules/AuditLogs/src/Providers/AuditLogsServiceProvider.php
Modules/AuditLogs/src/Listeners/LogTenantActivity.php
Modules/AuditLogs/src/Listeners/LogBillingActivity.php
Modules/AuditLogs/src/Listeners/LogAuthActivity.php
Modules/AuditLogs/src/Services/AuditService.php
Modules/AuditLogs/filament/Resources/AuditLogResource.php
```

Rules:
- Use spatie/laravel-activitylog
- LogTenantActivity: listens to TenantCreated, TenantSuspended
- LogBillingActivity: listens to SubscriptionActivated, InvoicePaid, PaymentFailed
- LogAuthActivity: listens to UserLoggedIn, UserRegistered
- AuditLogResource: Filament table, read-only, filterable by event type and date

---

### TASK 14 — Feature Flags

**Files to create:**
```
app/Shared/Features/AppFeatures.php
app/Shared/Features/TenantFeatures.php
app/Shared/Middleware/CheckFeatureFlag.php
```

Rules:
- Use Laravel Pennant
- AppFeatures: system-wide flags (ai_tools, advanced_reports, beta_dashboard)
- TenantFeatures: per-tenant flags resolved from tenant settings
- CheckFeatureFlag middleware: 403 if feature disabled for current tenant

---

### TASK 15 — Docker + CI/CD Files

**Files to create:**
```
docker-compose.yml
docker-compose.prod.yml
docker/nginx/default.conf
docker/php/Dockerfile
docker/php/php.ini
docker/supervisor/supervisord.conf
.github/workflows/tests.yml
.github/workflows/deploy.yml
.env.example
```

Rules for docker-compose.yml services:
- nginx: ports 80/443, depends on php-fpm
- php-fpm: php 8.4-fpm, non-root user, mounts app code
- postgres: 16-alpine, persistent volume
- redis: 7-alpine, persistent volume
- horizon: same image as php-fpm, runs php artisan horizon
- scheduler: same image, runs php artisan schedule:work
- reverb: same image, runs php artisan reverb:start
- minio: latest, ports 9000/9001
- mailpit: latest, ports 1025/8025

Rules for GitHub Actions:
- tests.yml: on push/PR, runs pint + phpstan + pest
- deploy.yml: on main branch, builds Docker image, pushes to registry

---

## Output format for each task

```
## Task N — [Name]

### Files
- path/to/file.php

### [filename.php]
```php
[full code]
```

### Test: [TestName]
```php
[full PestPHP test]
```

### Migration (if needed)
```php
[migration code]
```

### Notes
- Any events fired
- Any config/env vars needed
- Any package to install
```

---

## After all tasks are done

Generate a final `README.md` with:
1. Installation steps
2. Environment setup
3. Running in SaaS mode vs Standalone mode
4. Module enable/disable instructions
5. AI provider switching instructions
6. Running tests
