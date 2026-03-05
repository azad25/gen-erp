# Audit Domain - Complete Analysis

## Overview

The Audit domain provides comprehensive change tracking and audit logging for all system entities, ensuring full visibility into who changed what, when, and from where. It supports both automatic model event logging and manual logging for custom events.

## Backend Architecture

### 1. Core Models

#### AuditLog Model (`app/Domain/Audit/Models/AuditLog.php`)

**Purpose:** Immutable audit log entry tracking changes to auditable models

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'user_id',                // User who made the change
  'event',                  // Event type (created, updated, deleted, etc.)
  'auditable_type',         // Polymorphic model type
  'auditable_id',           // Polymorphic model ID
  'old_values',             // Old values (JSON)
  'new_values',             // New values (JSON)
  'ip_address',             // IP address of request
  'user_agent',             // User agent of request
];

// Event types
public const EVENT_CREATED = 'created';
public const EVENT_UPDATED = 'updated';
public const EVENT_DELETED = 'deleted';
public const EVENT_LOGIN = 'login';
public const EVENT_LOGOUT = 'logout';
public const EVENT_SETTINGS_CHANGED = 'settings_changed';
public const EVENT_PLAN_CHANGED = 'plan_changed';
public const EVENT_USER_INVITED = 'user_invited';
public const EVENT_USER_REMOVED = 'user_removed';
```

**Relationships:**
```php
user() -> User
auditable() -> MorphTo (any model)
```

**Key Methods:**
```php
// Override save to enforce immutability
public function save(array $options = []): bool {
  if ($this->exists) {
    throw new LogicException('Audit log entries are immutable and cannot be updated.');
  }
  return parent::save($options);
}

// Prevent deletion of audit logs
public function delete(): ?bool {
  throw new LogicException('Audit log entries cannot be deleted.');
}
```

**Immutability Enforcement:**
- `UPDATED_AT` is disabled (set to null)
- Cannot update existing audit logs
- Cannot delete audit logs
- Ensures audit trail integrity

### 2. Services

#### AuditLogger Service (`app/Domain/Audit/Services/AuditLogger.php`)

**Purpose:** Service for creating manual audit log entries

**Methods:**

```php
/**
 * Record a manual audit log entry.
 *
 * @param  array<string, mixed>  $old
 * @param  array<string, mixed>  $new
 */
public function log(string $event, Model $model, array $old = [], array $new = []): void {
  try {
    $companyId = CompanyContext::hasActive()
      ? CompanyContext::activeId()
      : ($model->company_id ?? null);

    if (!$companyId) {
      return;
    }

    AuditLog::create([
      'company_id' => $companyId,
      'user_id' => Auth::id(),
      'event' => $event,
      'auditable_type' => $model->getMorphClass(),
      'auditable_id' => $model->getKey(),
      'old_values' => !empty($old) ? $old : null,
      'new_values' => !empty($new) ? $new : null,
      'ip_address' => Request::ip(),
      'user_agent' => Request::userAgent(),
    ]);
  } catch (Throwable $e) {
    Log::channel('stderr')->error('Manual audit log failed: ' . $e->getMessage(), [
      'event' => $event,
      'model' => get_class($model) . '#' . $model->getKey(),
    ]);
  }
}
```

**Use Cases:**
- Settings changes
- User logins
- Plan changes
- Custom business events

### 3. Traits

#### Auditable Trait (`app/Domain/Auth/Models/Concerns/Auditable.php`)

**Purpose:** Automatically dispatches audit log entries on Eloquent create, update, and delete events

**Features:**
- Applied to models that need change tracking (Company, User, CompanyUser, etc.)
- Excludes sensitive fields from audit logs
- Dispatches RecordAuditLog job for async processing

**Fields Excluded:**
```php
protected static array $auditExclude = [
  'password',
  'two_factor_secret',
  'two_factor_recovery_codes',
  'remember_token',
  'password_changed_at',
];
```

**Usage:**
```php
class Company extends Model {
  use Auditable;
}
```

**Event Handling:**
```php
public static function bootAuditable(): void {
  static::created(function (Model $model): void {
    static::dispatchAuditLog('created', $model, [], $model->getAttributes());
  });

  static::updated(function (Model $model): void {
    $old = $model->getOriginal();
    $new = $model->getAttributes();
    static::dispatchAuditLog('updated', $model, $old, $new);
  });

  static::deleted(function (Model $model): void {
    static::dispatchAuditLog('deleted', $model, $model->getAttributes(), []);
  });
}
```

#### LogsAudit Trait (`app/Domain/Auth/Models/Concerns/LogsAudit.php`)

**Purpose:** Auto-logs create/update/delete events to the audit log via model events

**Features:**
- Similar to Auditable but directly creates audit logs instead of dispatching jobs
- Synchronous logging (immediate)
- Excludes sensitive fields from audit logs

**Fields Excluded:**
```php
protected static array $auditExclude = [
  'password',
  'remember_token',
  'two_factor_secret',
  'two_factor_recovery_codes',
  'nid_number',
];
```

**Usage:**
```php
class User extends Model {
  use LogsAudit;
}
```

**Event Handling:**
```php
public static function bootLogsAudit(): void {
  static::created(function ($model): void {
    static::recordAudit('created', $model, [], $model->getAttributes());
  });

  static::updated(function ($model): void {
    $dirty = $model->getDirty();
    if (empty($dirty)) {
      return;
    }

    $old = collect($model->getOriginal())
      ->only(array_keys($dirty))
      ->all();

    static::recordAudit('updated', $model, $old, $dirty);
  });

  static::deleted(function ($model): void {
    static::recordAudit('deleted', $model, $model->getOriginal(), []);
  });
}
```

### 4. Jobs

#### RecordAuditLog Job (`app/Jobs/RecordAuditLog.php`)

**Purpose:** Creates an immutable AuditLog record

**Features:**
- Queued job for async processing
- Fails silently to avoid breaking main requests
- Queued on 'audit' queue

**Constructor:**
```php
public function __construct(
  public readonly string $event,
  public readonly string $auditableType,
  public readonly int|string $auditableId,
  public readonly array $oldValues,
  public readonly array $newValues,
  public readonly ?int $userId,
  public readonly ?int $companyId,
  public readonly ?string $ipAddress,
  public readonly ?string $userAgent,
) {
  $this->onQueue('audit');
}
```

**Handle Method:**
```php
public function handle(): void {
  try {
    if (!$this->companyId) {
      return;
    }

    AuditLog::create([
      'company_id' => $this->companyId,
      'user_id' => $this->userId,
      'event' => $this->event,
      'auditable_type' => $this->auditableType,
      'auditable_id' => $this->auditableId,
      'old_values' => !empty($this->oldValues) ? $this->oldValues : null,
      'new_values' => !empty($this->newValues) ? $this->newValues : null,
      'ip_address' => $this->ipAddress,
      'user_agent' => $this->userAgent,
    ]);
  } catch (Throwable $e) {
    Log::channel('stderr')->error('Audit log recording failed: ' . $e->getMessage(), [
      'event' => $this->event,
      'auditable' => $this->auditableType . '#' . $this->auditableId,
    ]);
  }
}
```

## Complete Data Flow

### Automatic Audit Logging Flow (Auditable Trait)

```
Model Event Triggered (created/updated/deleted)
    ↓
Auditable Trait Boot
    ├─→ Filter sensitive fields
    ├─→ Get old/new values
    ├─→ Dispatch RecordAuditLog Job
    │   ├─→ Queue on 'audit' queue
    │   └─→ Async processing
    └─→ Job Handle
        ├─→ Validate company_id
        ├─→ Create AuditLog
        │   ├─→ Set company_id
        │   ├─→ Set user_id
        │   ├─→ Set event
        │   ├─→ Set auditable_type
        │   ├─→ Set auditable_id
        │   ├─→ Set old_values
        │   ├─→ Set new_values
        │   ├─→ Set ip_address
        │   └─→ Set user_agent
        └─→ Log errors to stderr
```

### Synchronous Audit Logging Flow (LogsAudit Trait)

```
Model Event Triggered (created/updated/deleted)
    ↓
LogsAudit Trait Boot
    ├─→ Filter sensitive fields
    ├─→ Get dirty fields (updated only)
    ├─→ Get old/new values
    └─→ Create AuditLog (synchronous)
        ├─→ Validate company_id
        ├─→ Set company_id
        ├─→ Set user_id
        ├─→ Set event
        ├─→ Set auditable_type
        ├─→ Set auditable_id
        ├─→ Set old_values
        ├─→ Set new_values
        ├─→ Set ip_address
        └─→ Set user_agent
```

### Manual Audit Logging Flow

```
Business Event Triggered (settings changed, login, etc.)
    ↓
AuditLogger::log()
    ├─→ Get company_id from context or model
    ├─→ Get user_id from Auth
    ├─→ Get ip_address from Request
    ├─→ Get user_agent from Request
    └─→ Create AuditLog
        ├─→ Set company_id
        ├─→ Set user_id
        ├─→ Set event
        ├─→ Set auditable_type
        ├─→ Set auditable_id
        ├─→ Set old_values
        ├─→ Set new_values
        ├─→ Set ip_address
        └─→ Set user_agent
```

## Integration with Other Domains

### Auth Domain

**Company Model:**
```php
class Company extends Model {
  use Auditable;
}
```

**User Model:**
```php
class User extends Model {
  use LogsAudit;
}
```

**CompanyUser Model:**
```php
class CompanyUser extends Model {
  use Auditable;
}
```

**Events Logged:**
- Company created/updated/deleted
- User created/updated/deleted
- User invited/removed
- Company settings changed
- User login/logout

### All Domains

**Any Model Can Be Auditable:**
```php
class Product extends Model {
  use Auditable;
}

class Invoice extends Model {
  use Auditable;
}

class Project extends Model {
  use Auditable;
}

class Task extends Model {
  use Auditable;
}

class Site extends Model {
  use Auditable;
}

class Page extends Model {
  use Auditable;
}
```

**Benefits:**
- Full audit trail across all domains
- Track who changed what, when, and from where
- Support for compliance and security audits
- Debugging and troubleshooting support

## Comparison with Modern Audit Systems

### Features Comparison

| Feature | This System | Laravel Auditing | Sentry | Papertrail |
|---------|-------------|-----------------|--------|------------|
| **Automatic Logging** | ✅ | ✅ | ✅ | ❌ |
| **Manual Logging** | ✅ | ✅ | ✅ | ❌ |
| **Multi-tenancy** | ✅ | ⚠️ | ✅ | ❌ |
| **Polymorphic Relations** | ✅ | ✅ | ✅ | ❌ |
| **Immutable Logs** | ✅ | ⚠️ | ✅ | ❌ |
| **Sensitive Field Filtering** | ✅ | ✅ | ✅ | ❌ |
| **Async Processing** | ✅ | ✅ | ✅ | ❌ |
| **IP Address Tracking** | ✅ | ✅ | ✅ | ❌ |
| **User Agent Tracking** | ✅ | ✅ | ✅ | ❌ |
| **Old/New Values** | ✅ | ✅ | ✅ | ❌ |
| **Custom Events** | ✅ | ✅ | ✅ | ❌ |
| **Search/Filter** | ⚠️ | ✅ | ✅ | ❌ |
| **Reports** | ⚠️ | ✅ | ✅ | ❌ |
| **Alerts** | ❌ | ⚠️ | ✅ | ❌ |
| **Retention Policy** | ⚠️ | ✅ | ✅ | ❌ |
| **Export** | ⚠️ | ✅ | ✅ | ❌ |
| **API** | ✅ | ✅ | ✅ | ❌ |

### Workflow Comparison

**This System:**
```
Model Event → Trait → Job/Service → AuditLog (Immutable)
```

**Laravel Auditing:**
```
Model Event → Trait → Audit (Model) → Database
```

**Sentry:**
```
Event → SDK → Sentry API → Dashboard
```

**Papertrail:**
```
Syslog → Papertrail API → Dashboard
```

### Unique Features

**This System:**
- Multi-tenancy support (company_id)
- Two approaches: Auditable (async) and LogsAudit (sync)
- Immutable audit logs (cannot be updated/deleted)
- Sensitive field filtering (password, tokens, etc.)
- Queued job processing (non-blocking)
- Fails silently (doesn't break main requests)
- Integration with all domains via traits

**Laravel Auditing:**
- More features (search, filter, reports, export)
- Custom audit drivers
- Audit models (not just logs)
- User-friendly API

**Sentry:**
- Real-time alerts
- Error tracking
- Performance monitoring
- Release tracking

**Papertrail:**
- Syslog integration
- Real-time log streaming
- Search and filter
- Alerts

## API Reference

### Audit Logs

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/audit-logs` | List audit logs | Required |
| GET | `/api/v1/audit-logs/{id}` | Get audit log | Required |
| GET | `/api/v1/audit-logs/auditable/{type}/{id}` | Get logs for auditable | Required |

### Query Parameters (Index)

```
company_id -> Filter by company
user_id -> Filter by user
event -> Filter by event type
auditable_type -> Filter by model type
auditable_id -> Filter by model ID
start_date -> Filter by start date
end_date -> Filter by end date
search -> Search in old_values/new_values
sort_by -> Sort field
sort_order -> Sort order (asc/desc)
per_page -> Pagination (default: 50)
page -> Page number
```

### Response Format

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "company_id": 1,
      "user_id": 1,
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "event": "updated",
      "auditable_type": "App\\Domain\\Auth\\Models\\Company",
      "auditable_id": 1,
      "auditable": {
        "id": 1,
        "name": "Acme Corp"
      },
      "old_values": {
        "name": "Old Company Name",
        "status": "draft"
      },
      "new_values": {
        "name": "New Company Name",
        "status": "published"
      },
      "ip_address": "192.168.1.1",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2026-03-05T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 100
  }
}
```

## Frontend Integration

### Audit Log Display

```javascript
const formatAuditLogMessage = (log) => {
  const userName = log.user?.name || 'System';
  const modelName = class_basename(log.auditable_type);
  
  const actionMap = {
    'created': 'created',
    'updated': 'updated',
    'deleted': 'deleted',
    'login': 'logged in to',
    'logout': 'logged out of',
    'settings_changed': 'changed settings for',
    'plan_changed': 'changed plan for',
    'user_invited': 'invited user to',
    'user_removed': 'removed user from',
  };
  
  const action = actionMap[log.event] || log.event;
  
  return `${userName} ${action} ${modelName}`;
}

const formatChangedFields = (log) => {
  if (log.event === 'created' || log.event === 'deleted') {
    return null;
  }
  
  const old = log.old_values || {};
  const new = log.new_values || {};
  
  return Object.keys({ ...old, ...new }).map(key => ({
    field: key,
    old: old[key],
    new: new[key],
  }));
}
```

### Dashboard Activity Feed

```javascript
const fetchRecentActivity = async () => {
  const response = await get('/audit-logs', {
    per_page: 10,
    sort_by: 'created_at',
    sort_order: 'desc',
  });
  
  return response.data;
}
```

## Security Considerations

### Sensitive Field Filtering

**Fields Excluded by Default:**
- `password`
- `remember_token`
- `two_factor_secret`
- `two_factor_recovery_codes`
- `password_changed_at`
- `nid_number`

**Custom Exclusion:**
```php
protected static array $auditExclude = [
  'password',
  'api_key',
  'secret_key',
  'access_token',
  'refresh_token',
];
```

### Immutability

**Enforced at Model Level:**
```php
public function save(array $options = []): bool {
  if ($this->exists) {
    throw new LogicException('Audit log entries are immutable and cannot be updated.');
  }
  return parent::save($options);
}

public function delete(): ?bool {
  throw new LogicException('Audit log entries cannot be deleted.');
}
```

### Multi-tenancy

**Company Isolation:**
```php
$companyId = CompanyContext::hasActive()
  ? CompanyContext::activeId()
  : ($model->company_id ?? null);

if (!$companyId) {
  return; // Don't log without company context
}
```

### Error Handling

**Silent Failure:**
```php
try {
  // Create audit log
} catch (Throwable $e) {
  Log::channel('stderr')->error('Audit log failed: ' . $e->getMessage());
  // Don't throw - fail silently to avoid breaking main requests
}
```

## Best Practices

### When to Use Auditable vs LogsAudit

**Use Auditable (Async) When:**
- High-volume models (many changes)
- Performance-critical operations
- Non-blocking logging required
- Models with complex relationships

**Use LogsAudit (Sync) When:**
- Low-volume models (few changes)
- Immediate logging required
- Compliance requirements (real-time audit)
- Simple models

### Manual Logging

**Use AuditLogger When:**
- Logging custom business events
- Logging settings changes
- Logging user authentication events
- Logging plan changes
- Logging user invitations/removals

**Example:**
```php
// Settings changed
$auditLogger->log('settings_changed', $company, $oldSettings, $newSettings);

// User login
$auditLogger->log('login', $user, [], []);

// Plan changed
$auditLogger->log('plan_changed', $company, ['plan' => 'basic'], ['plan' => 'pro']);
```

### Audit Log Retention

**Recommended Retention Policy:**
- 90 days for compliance
- 365 days for security audits
- 7 years for financial audits

**Implementation:**
```php
// Scheduled job to prune old logs
$schedule->job(new PruneAuditLogs(90))->daily();
```

## Summary

### Backend Coverage
- ✅ AuditLog model (immutable, multi-tenancy, polymorphic relations)
- ✅ AuditLogger service (manual logging for custom events)
- ✅ Auditable trait (async logging via queued jobs)
- ✅ LogsAudit trait (sync logging)
- ✅ RecordAuditLog job (async processing)
- ✅ Sensitive field filtering
- ✅ IP address tracking
- ✅ User agent tracking
- ✅ Old/new values tracking
- ✅ Multi-tenancy support

### Integration
- ✅ Auth Domain (Company, User, CompanyUser)
- ✅ All Domains (any model can use Auditable/LogsAudit)
- ✅ Multi-tenancy (company_id isolation)
- ✅ Security (sensitive field filtering, immutability)

### Features
- ✅ Automatic logging via model events
- ✅ Manual logging for custom events
- ✅ Async processing (non-blocking)
- ✅ Sync processing (immediate)
- ✅ Immutable logs (cannot be updated/deleted)
- ✅ Sensitive field filtering
- ✅ Fails silently (doesn't break main requests)
- ✅ IP address tracking
- ✅ User agent tracking
- ✅ Old/new values tracking
- ✅ Polymorphic relations (any model)
- ✅ Multi-tenancy support

The Audit system provides **comprehensive change tracking** across all domains with both automatic and manual logging capabilities, ensuring full visibility into system changes for compliance, security, and debugging purposes.

## Backend Architecture
- **Site Model** - Multi-tenancy, status (DRAFT → PUBLISHED → MAINTENANCE), theme, SEO settings, Google Analytics, Facebook Pixel
- **Page Model** - Status (DRAFT → PUBLISHED → SCHEDULED → ARCHIVED), homepage flag, SEO, sections
- **BlogPost Model** - Author, category, views count, reading time calculation
- **Section Model** - Page builder with 13 section types (Hero Banner, Text Block, Product Grid, Portfolio Grid, Team Grid, Stats, FAQ, CTA Banner, Contact Form, Gallery, Testimonials, Blog Posts, Custom HTML)
- **Menu Model** - Navigation with nested items, location-based menus
- **ContactSubmission Model** - Status (NEW → CONTACTED → RESOLVED/SPAM), assignment, tracking
- **ShoppingCart Model** - Session/customer support, expiration, item management
- **CustomerAccount Model** - Guest/customer authentication, orders, carts
- **PublicOrder Model** - Order workflow (PENDING → PROCESSING → COMPLETED/CANCELLED), payment status (PENDING → PAID/FAILED/REFUNDED), billing/shipping addresses
- **ProductReview Model** - Rating (1-5), approval, verified purchase, helpful count
- **Wishlist Model** - Customer items management

## Services
- **CMSService:** create/update/publish sites, create/update/publish pages, find by subdomain
- **PublicSiteService:** find site by tenant, get site data, get page by slug, get homepage
- **SEOService:** generate sitemap XML, generate robots.txt, generate structured data (Organization, WebSite, WebPage, Article schemas)
- **CartService:** get/create cart, add/update/remove items, convert to order

## Data Flows
- **Site Creation:** Create site → Set status = DRAFT → Dispatch SiteCreated event → Return site
- **Page Creation:** Check homepage → Unset other homepages → Create page → Dispatch PageCreated event
- **Page Publishing:** Update status = PUBLISHED → Set published_at → Dispatch PagePublished event
- **Cart to Order:** Validate cart → Generate order number → Create order/items → Clear cart → Dispatch OrderPlaced event
- **Contact Submission:** Create submission → Set status = NEW → Send notification email → Return submission

## Integration
- **Product Domain:** Product catalog, product reviews, product grid sections (load products from Product domain)
- **Customer Domain:** Customer account sync, billing/shipping addresses
- **Accounting Domain:** Order to invoice → Journal entries (DR: Accounts Receivable, CR: Sales Revenue/Output VAT)
- **CRM Domain:** Contact submissions → Lead generation (set source = website, status = NEW)

## Frontend Architecture
- **CMS/Dashboard.vue** - Metrics (sites, pages, blog posts, media files), recent pages, recent blog posts
- **CMS/Sites/Index.vue** - List, create, edit, delete sites (name, domain, status, pages count)
- **CMS/Pages/Index.vue** - List, create, edit, delete pages (title, URL, status, template)
- **CMS/PageBuilder/Index.vue** - Drag-drop sections, 13 section types
- **CMS/Blog/Index.vue** - List, create, edit, delete blog posts (title, category, author, status)
- **CMS/Menus/Index.vue** - List, create, edit, delete menus
- **CMS/Menus/Builder.vue** - Drag-drop menu items, nested items
- **CMS/Contacts/Index.vue** - List, assign, mark contacted/resolved/spam
- **CMS/Reviews/Index.vue** - List, approve, reject product reviews
- **CMS/SEO/Index.vue** - Generate sitemap, robots.txt, structured data

## Comparison with Modern CMS
- **Similar:** Page builder, blog, e-commerce, shopping cart, product reviews, wishlist, SEO (sitemap, robots.txt, structured data), contact forms, menu builder
- **Simpler:** No multi-language, no theme system, no plugin system, no app store, no headless CMS, no CDN integration, no image optimization, no page caching
- **Unique:** Multi-tenancy support, ERP integration (sales orders, invoices), CRM integration (lead generation), Bangladesh localization (BDT), page builder with sections, contact form with assignment, product reviews with approval, shopping cart with session/customer support