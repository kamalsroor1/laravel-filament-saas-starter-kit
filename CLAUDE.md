# CLAUDE.md — Enterprise SaaS Starter Kit

> This file is the single source of truth for AI code assistants (Claude, Cursor, Copilot).
> Read this entire file before writing any code.

---

## Project Overview

Enterprise-Ready SaaS Starter Kit built with:
- **Laravel 12** + **PHP 8.4**
- **Filament 4** (UI layer only — no business logic)
- **PostgreSQL** + **Redis**
- **Modular Monolith** architecture
- **Multi-Tenancy** (Single DB, tenant_id isolation)

**Two modes:**
- `SAAS_MODE=true` → Multi-tenant SaaS with billing, subscriptions, subdomains
- `SAAS_MODE=false` → Standalone enterprise, single company, no billing

---

## Folder Structure

```
app/
├── Core/               # Base abstract classes, interfaces, traits
│   ├── Actions/        # BaseAction abstract
│   ├── DTOs/           # BaseDTO abstract
│   ├── Services/       # BaseService abstract
│   ├── Repositories/   # BaseRepository + RepositoryInterface
│   ├── Module/         # ModuleManager, ModuleServiceProvider
│   └── Exceptions/     # Base domain exceptions
│
├── Shared/             # Shared utilities used across modules
│   ├── Traits/
│   ├── Helpers/
│   ├── Enums/
│   └── ValueObjects/
│
├── Infrastructure/     # Technical concerns (no domain logic)
│   ├── Cache/
│   ├── Queue/
│   ├── Storage/
│   └── AI/             # AIProviderInterface + implementations
│
├── Central/            # SaaS-only: super admin, tenant management
│   ├── Actions/
│   ├── Models/
│   └── Services/
│
└── Tenant/             # Per-tenant domain logic
    ├── Actions/
    ├── Models/
    └── Services/

Modules/
├── SaaS/
├── Billing/
├── Tenancy/
├── Notifications/
├── API/
├── AI/
├── AuditLogs/
├── Webhooks/
├── Media/
└── Settings/

packages/               # Internal SDKs (extracted later)
├── core/
├── billing/
└── tenancy/
```

---

## Architecture Layers (strictly enforced)

```
HTTP Request
    ↓
Controller / Filament Resource   (no logic — only calls Actions)
    ↓
Action                           (one use case, one public method: handle())
    ↓
Service                          (reusable domain logic)
    ↓
DTO                              (typed data transfer, no methods)
    ↓
Repository                       (database access only)
    ↓
Model                            (Eloquent — no business logic)
```

### Rules — never break these

- **NEVER** put business logic in Controllers
- **NEVER** put business logic in Filament Resources, Pages, or Widgets
- **NEVER** call a Payment Gateway directly — always go through `PaymentGatewayInterface`
- **NEVER** call an AI Provider directly — always go through `AIProviderInterface`
- **NEVER** write a raw query without tenant scope (when tenancy is active)
- **ALWAYS** use DTOs to pass data between layers
- **ALWAYS** fire domain Events for important state changes
- **ALWAYS** process side effects (emails, webhooks, notifications) in Listeners, not in Actions

---

## Naming Conventions

| Type | Suffix | Example |
|---|---|---|
| Action | `Action` | `CreateTenantAction` |
| Service | `Service` | `BillingService` |
| DTO | `DTO` | `CreateTenantDTO` |
| Repository | `Repository` | `TenantRepository` |
| Interface | `Interface` | `PaymentGatewayInterface` |
| Event | past tense | `TenantCreated` |
| Listener | `Listener` | `SendWelcomeEmailListener` |
| Job | `Job` | `ProcessWebhookJob` |
| Policy | `Policy` | `TenantPolicy` |
| Request | `Request` | `CreateTenantRequest` |

---

## Module Structure

Every module follows this structure:

```
Modules/Billing/
├── module.json           # Module metadata + enable flag
├── routes/
│   ├── web.php
│   └── api.php
├── config/
│   └── billing.php
├── database/
│   ├── migrations/
│   └── seeders/
├── filament/
│   ├── Resources/        # UI ONLY — no logic
│   ├── Pages/
│   └── Widgets/
├── src/
│   ├── Actions/
│   ├── Services/
│   ├── DTOs/
│   ├── Repositories/
│   ├── Models/
│   ├── Events/
│   ├── Listeners/
│   ├── Jobs/
│   └── Providers/
│       └── BillingServiceProvider.php
├── tests/
│   ├── Feature/
│   └── Unit/
└── resources/
    ├── views/
    └── lang/
```

### module.json schema

```json
{
  "name": "Billing",
  "alias": "billing",
  "description": "Subscription billing and payment processing",
  "version": "1.0.0",
  "enabled": true,
  "providers": [
    "Modules\\Billing\\Providers\\BillingServiceProvider"
  ],
  "requires": ["Tenancy"],
  "saas_only": true
}
```

---

## Multi-Tenancy Rules

- Strategy: **Single Database** with `tenant_id` on every tenant-scoped table
- Package: `stancl/tenancy`
- **Every** model in the Tenant context must use `BelongsToTenant` trait
- **Every** migration for tenant data must include: `$table->foreignUlid('tenant_id')->index()`
- Global scope `TenantScope` is auto-applied — never bypass it
- Cache keys must be prefixed: `tenant_{id}_{key}`
- Queue jobs must carry tenant context via `TenantAware` interface

```php
// Correct — tenant context always set before any tenant operation
Tenancy::runForTenant($tenant, function () {
    // all queries here are automatically scoped
});
```

---

## AI Provider Architecture

Three providers supported. All go through the same interface.

```php
interface AIProviderInterface
{
    public function complete(AIRequestDTO $request): AIResponseDTO;
    public function stream(AIRequestDTO $request): Generator;
    public function embed(string $text): array;
}
```

Implementations:
- `OpenAIProvider` → uses `openai-php/laravel`
- `ClaudeProvider` → uses `anthropics/anthropic-sdk-php` (Anthropic)
- `GeminiProvider` → uses `google/generative-ai-php`
- `AnthropicProvider` → alias for ClaudeProvider (same SDK)

Resolved via: `AIProviderFactory::make(config('ai.default'))`

Config key: `AI_PROVIDER=claude` / `openai` / `gemini`

---

## Payment Gateway Architecture

```php
interface PaymentGatewayInterface
{
    public function charge(ChargeDTO $dto): PaymentResultDTO;
    public function subscribe(SubscriptionDTO $dto): SubscriptionResultDTO;
    public function cancel(string $subscriptionId): bool;
    public function refund(RefundDTO $dto): RefundResultDTO;
    public function handleWebhook(Request $request): void;
}
```

Implementations: `StripeGateway`, `PaddleGateway`, `PaymobGateway`, `TapGateway`

Resolved via: `PaymentGatewayFactory::make(config('billing.gateway'))`

---

## Event-Driven Pattern

All important operations must fire events:

```php
// In Action — fire event, never call listener directly
event(new TenantCreated($tenant));
event(new SubscriptionActivated($subscription));
event(new InvoicePaid($invoice));

// Listener handles side effects
class SendWelcomeEmailListener
{
    public function handle(TenantCreated $event): void
    {
        // send email, notify, track analytics
    }
}
```

---

## Filament Usage Rules

Filament is **UI layer only**:

```php
// WRONG — business logic in Filament Resource
public function create(): void
{
    $tenant = Tenant::create($this->form->getState()); // ❌
    Mail::to($tenant->email)->send(new WelcomeMail()); // ❌
}

// CORRECT — delegate to Action
public function create(): void
{
    $dto = CreateTenantDTO::fromArray($this->form->getState());
    app(CreateTenantAction::class)->handle($dto); // ✅
}
```

---

## Testing Standards

- Framework: **PestPHP**
- Every Action must have a Feature test
- Every Service must have a Unit test
- Tenant isolation must be tested explicitly

```php
// Tenant isolation test pattern
it('cannot access another tenant data', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    Tenancy::runForTenant($tenant1, function () use ($tenant2) {
        expect(User::find($tenant2->users->first()->id))->toBeNull();
    });
});
```

---

## Environment Variables (key ones)

```env
SAAS_MODE=true
AI_PROVIDER=claude           # claude | openai | gemini
BILLING_GATEWAY=stripe        # stripe | paddle | paymob | tap

# AI Keys
ANTHROPIC_API_KEY=
OPENAI_API_KEY=
GEMINI_API_KEY=

# Billing Keys
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
PAYMOB_API_KEY=
TAP_SECRET_KEY=
```

---

## Key Packages

```
spatie/laravel-permission      → Roles & Permissions
spatie/laravel-activitylog     → Activity Logs
spatie/laravel-settings        → Settings
spatie/laravel-medialibrary    → Media
stancl/tenancy                 → Multi-Tenancy
laravel/sanctum                → API Auth
laravel/horizon                → Queue Monitoring
laravel/pulse                  → Observability
laravel/pennant                → Feature Flags
filament/filament               → Admin Panels
openai-php/laravel             → OpenAI SDK
anthropics/anthropic-sdk-php   → Claude SDK
google/generative-ai-php       → Gemini SDK
```

---

## What NOT to generate

- Do not create Facade classes unless explicitly asked
- Do not use `DB::` directly — use Repositories
- Do not use `config()` inside Models — pass via constructor
- Do not create God classes — max 200 lines per class
- Do not skip DTOs — even for simple operations
- Do not add `dd()`, `dump()`, or `var_dump()` in committed code
