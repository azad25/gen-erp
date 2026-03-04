# Events, Jobs & Background Processing

## Table of Contents
- [Overview](#overview)
- [Events](#events)
- [Event Listeners](#event-listeners)
- [Background Jobs](#background-jobs)
- [Model Observers](#model-observers)
- [Queue Configuration](#queue-configuration)
- [Broadcasting](#broadcasting)
- [Best Practices](#best-practices)

---

## Overview

Gen-ERP uses Laravel's event system and queue infrastructure for asynchronous processing, real-time notifications, and decoupled business logic. The application follows event-driven architecture patterns to maintain separation of concerns and enable scalable background processing.

### Key Components

1. **Events** - Domain events that represent significant business occurrences
2. **Listeners** - Handlers that respond to events
3. **Jobs** - Queued tasks for background processing
4. **Observers** - Model lifecycle hooks
5. **Broadcasting** - Real-time event broadcasting via Laravel Reverb

### Architecture Pattern

```
User Action → Controller → Service → Event Dispatched
                                          ↓
                                    Event Listeners
                                          ↓
                                    Background Jobs
                                          ↓
                                    Queue Workers
```

---

## Events

Gen-ERP implements domain events to decouple business logic and enable reactive programming patterns.

### Event Structure

All events follow Laravel's event conventions and use the `Dispatchable` trait:

```php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventName
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ModelType $model,
        public readonly array $additionalData = []
    ) {}
}
```


### Core Application Events

#### 1. CreditNoteApplied

**File:** `app/Events/CreditNoteApplied.php`

Fired when a credit note is applied to an invoice, triggering automatic journal entry reversal.

```php
namespace App\Events;

use App\Domain\Customer\Models\CreditNote;
use App\Domain\Invoice\Models\Invoice;

class CreditNoteApplied
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CreditNote $creditNote,
        public readonly Invoice $invoice,
    ) {}
}
```

**Listeners:**
- `CreateCreditNoteReversal` - Creates reversal journal entry

**Usage:**
```php
event(new CreditNoteApplied($creditNote, $invoice));
```

#### 2. LowStockAlert

**File:** `app/Events/LowStockAlert.php`

Broadcast event when product stock falls below threshold. Implements `ShouldBroadcast` for real-time notifications.

```php
namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class LowStockAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $companyId,
        public readonly int $branchId,
        public readonly int $productId,
        public readonly string $productName,
        public readonly int $currentQty,
        public readonly int $threshold,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("company.{$this->companyId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stock.low';
    }
}
```

**Broadcasting:**
- Channel: `company.{companyId}`
- Event Name: `stock.low`
- Real-time notification to company users

**Usage:**
```php
event(new LowStockAlert(
    companyId: 1,
    branchId: 5,
    productId: 123,
    productName: 'Widget A',
    currentQty: 5,
    threshold: 10
));
```

#### 3. POSSaleCompleted

**File:** `app/Events/POSSaleCompleted.php`

Broadcast event when a POS sale is completed. Notifies both company and branch channels.

```php
class POSSaleCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $companyId,
        public readonly int $branchId,
        public readonly int $saleId,
        public readonly string $saleNumber,
        public readonly int $totalAmount,
        public readonly string $paymentMethod,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("company.{$this->companyId}"),
            new PrivateChannel("branch.{$this->branchId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pos.sale.completed';
    }
}
```

**Broadcasting:**
- Channels: `company.{companyId}`, `branch.{branchId}`
- Event Name: `pos.sale.completed`
- Real-time sale notifications

#### 4. ModelSaved

**File:** `app/Events/ModelSaved.php`

Generic event fired when any model using `DispatchesModelEvents` trait is saved.

```php
class ModelSaved
{
    use Dispatchable;

    public function __construct(
        public readonly string $entityType,
        public readonly Model $entity,
    ) {}
}
```

**Listeners:**
- `EvaluateAlertRules` - Checks if model changes trigger alert rules

**Usage:**
```php
// Automatically dispatched by DispatchesModelEvents trait
event(new ModelSaved('customer', $customer));
```

#### 5. ImportProgressUpdated

**File:** `app/Events/ImportProgressUpdated.php`

Broadcast event for real-time import progress updates.

```php
class ImportProgressUpdated implements ShouldBroadcast
{
    public function __construct(
        public readonly int $companyId,
        public readonly int $importJobId,
        public readonly int $processedRows,
        public readonly int $totalRows,
        public readonly int $createdRows,
        public readonly int $failedRows,
        public readonly string $status,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("company.{$this->companyId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'import.progress';
    }
}
```


### Domain Events

Domain events are organized by business domain and follow DDD principles.

#### Invoice Domain Events

**Namespace:** `App\Domain\Invoice\Events`

| Event | Description | Listeners |
|-------|-------------|-----------|
| `InvoiceCreated` | New invoice created | - |
| `InvoiceSent` | Invoice sent to customer | `SendInvoiceNotification` |
| `InvoiceCancelled` | Invoice cancelled | - |

#### SalesOrder Domain Events

**Namespace:** `App\Domain\SalesOrder\Events`

| Event | Description | Listeners |
|-------|-------------|-----------|
| `SalesOrderConfirmed` | Order confirmed | `NotifyOrderConfirmation` |
| `SalesOrderCancelled` | Order cancelled | - |

#### Customer Domain Events

**Namespace:** `App\Domain\Customer\Events`

| Event | Description | Listeners |
|-------|-------------|-----------|
| `CustomerTransactionRecorded` | Transaction recorded | `UpdateCustomerBalance` |

#### Product Domain Events

**Namespace:** `App\Domain\Product\Events`

| Event | Description | Listeners |
|-------|-------------|-----------|
| `ProductCreated` | New product created | - |

#### Notification Domain Events

**Namespace:** `App\Domain\Notification\Events`

| Event | Description | Listeners |
|-------|-------------|-----------|
| `SystemAlertFired` | System alert triggered | `HandleNotifiableEvent` |

---

## Event Listeners

Event listeners handle the business logic triggered by events. Listeners can be synchronous or queued.

### Listener Structure

```php
namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;

class ListenerName implements ShouldQueue
{
    public function __construct(
        private readonly ServiceClass $service
    ) {}

    public function handle(EventClass $event): void
    {
        // Handle event logic
    }
}
```

### Core Listeners

#### 1. CreateCreditNoteReversal

**File:** `app/Listeners/CreateCreditNoteReversal.php`

Automatically creates a reversal journal entry when a credit note is applied to an invoice.

```php
namespace App\Listeners;

use App\Domain\Accounting\Services\PostingService;
use App\Events\CreditNoteApplied;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateCreditNoteReversal implements ShouldQueue
{
    public function __construct(
        private readonly PostingService $postingService,
    ) {}

    public function handle(CreditNoteApplied $event): void
    {
        $creditNote = $event->creditNote;
        $invoice = $event->invoice;

        // Find original invoice journal entry
        $originalJournal = JournalEntry::withoutGlobalScopes()
            ->where('company_id', $invoice->company_id)
            ->where('reference_type', 'invoice')
            ->where('reference_id', $invoice->id)
            ->where('status', 'posted')
            ->first();

        if ($originalJournal === null) {
            Log::warning('No posted journal entry found for invoice');
            return;
        }

        // Check if already reversed
        if ($originalJournal->reversed_by_id !== null) {
            return;
        }

        // Create reversal
        $idempotencyKey = "credit-note-reversal-{$creditNote->id}-{$originalJournal->id}";
        $description = "Reversal for Credit Note {$creditNote->credit_note_number}";

        $reversal = $this->postingService->reverse(
            original: $originalJournal,
            idempotencyKey: $idempotencyKey,
            description: $description,
            reversedBy: $creditNote->created_by,
        );

        Log::info('Successfully created reversal', [
            'original_journal_id' => $originalJournal->id,
            'reversal_journal_id' => $reversal->id,
        ]);
    }
}
```

**Features:**
- Queued execution (implements `ShouldQueue`)
- Idempotency protection
- Automatic retry on failure
- Comprehensive logging

#### 2. EvaluateAlertRules

**File:** `app/Listeners/EvaluateAlertRules.php`

Evaluates alert rules when models are saved.

```php
namespace App\Listeners;

use App\Events\ModelSaved;
use App\Services\AlertRulesService;

class EvaluateAlertRules
{
    public function __construct(
        private readonly AlertRulesService $alertRulesService,
    ) {}

    public function handle(ModelSaved $event): void
    {
        $this->alertRulesService->evaluate($event->entityType, $event->entity);
    }
}
```

**Usage:**
- Triggered by `ModelSaved` event
- Checks configured alert rules
- Sends notifications if rules match


### Event-Listener Registration

**File:** `app/Providers/EventServiceProvider.php`

```php
protected $listen = [
    // Credit Note Events
    \App\Events\CreditNoteApplied::class => [
        \App\Listeners\CreateCreditNoteReversal::class,
    ],

    // Notification Events
    \App\Domain\Notification\Events\SystemAlertFired::class => [
        \App\Domain\Notification\Listeners\HandleNotifiableEvent::class,
    ],

    // Invoice Events
    \App\Domain\Invoice\Events\InvoiceSent::class => [
        \App\Domain\Invoice\Listeners\SendInvoiceNotification::class,
    ],

    // SalesOrder Events
    \App\Domain\SalesOrder\Events\SalesOrderConfirmed::class => [
        \App\Domain\SalesOrder\Listeners\NotifyOrderConfirmation::class,
    ],

    // Customer Events
    \App\Domain\Customer\Events\CustomerTransactionRecorded::class => [
        \App\Domain\Customer\Listeners\UpdateCustomerBalance::class,
    ],
];
```

### Event Subscribers

Event subscribers allow grouping multiple event handlers in a single class:

```php
protected $subscribe = [
    \App\Domain\HR\Listeners\ProjectTaskEventListener::class,
];
```

---

## Background Jobs

Background jobs handle time-consuming tasks asynchronously to improve application responsiveness.

### Job Structure

```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class JobName implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    public int $backoff = 60;

    public function __construct(
        public readonly ModelType $model
    ) {
        $this->onQueue('queue-name');
    }

    public function handle(ServiceClass $service): void
    {
        // Job logic
    }
}
```


### Core Background Jobs

#### 1. ProcessImportJob

**File:** `app/Jobs/ProcessImportJob.php`

Processes bulk CSV/Excel/TXT/DOCX imports in the background with progress tracking.

```php
class ProcessImportJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600; // 1 hour

    public function __construct(
        public readonly ImportJob $importJob,
    ) {
        $this->onQueue('imports');
    }

    public function uniqueId(): string
    {
        return 'import-'.$this->importJob->id;
    }

    public function handle(ImportService $importService): void
    {
        // Parse file (CSV, Excel, TXT, DOCX)
        $rows = $this->parseFile($this->importJob->file_path);
        
        // Mark as processing
        $importService->markProcessing($this->importJob, count($rows));
        
        // Process each row
        foreach ($rows as $index => $row) {
            try {
                $this->processRow($this->importJob->entity_type, $row, $this->importJob->company_id);
                $importService->recordSuccess($this->importJob);
            } catch (\Throwable $e) {
                $importService->recordFailure($this->importJob, [
                    'row' => $index + 2,
                    'message' => $e->getMessage()
                ]);
            }
            
            // Broadcast progress every 10 rows
            if ($index % 10 === 0) {
                event(new ImportProgressUpdated(...));
            }
        }
        
        $importService->markCompleted($this->importJob);
    }
}
```

**Features:**
- Unique job (prevents duplicate imports)
- Supports CSV, Excel, TXT, DOCX formats
- Real-time progress broadcasting
- Error tracking per row
- Automatic retry on failure
- 1-hour timeout

**Supported Entity Types:**
- `products` - Product imports
- `customers` - Customer imports
- `suppliers` - Supplier imports
- `employees` - Employee imports

**Queue:** `imports`

#### 2. RecordAuditLog

**File:** `app/Jobs/RecordAuditLog.php`

Creates immutable audit log records for compliance and tracking.

```php
class RecordAuditLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

    public function handle(): void
    {
        try {
            if (!$this->companyId) return;

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
        } catch (\Throwable $e) {
            Log::error('Audit log recording failed', [
                'event' => $this->event,
                'auditable' => $this->auditableType.'#'.$this->auditableId,
            ]);
        }
    }
}
```

**Features:**
- Fails silently (doesn't break main request)
- Captures old/new values
- Records user, IP, user agent
- Immutable records

**Queue:** `audit`


#### 3. SendNotificationJob

**File:** `app/Jobs/SendNotificationJob.php`

Sends notification emails asynchronously with retry logic.

```php
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $toEmail,
        public readonly string $toName,
        public readonly string $subject,
        public readonly string $body,
        public readonly string $eventKey,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        try {
            Mail::send('emails.notification', [
                'body' => $this->body,
                'eventKey' => $this->eventKey,
            ], function ($message) {
                $message->to($this->toEmail, $this->toName)
                    ->subject($this->subject);
            });
        } catch (\Throwable $e) {
            Log::error('SendNotificationJob failed', [
                'to' => $this->toEmail,
                'event' => $this->eventKey,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

**Features:**
- 3 retry attempts
- 60-second backoff between retries
- Comprehensive error logging

**Queue:** `notifications`

#### 4. Other Background Jobs

| Job | Description | Queue | Timeout |
|-----|-------------|-------|---------|
| `FilterableCustomFieldJob` | Process custom field filters | `default` | 300s |
| `ImportProductsJob` | Import products from external source | `imports` | 1800s |
| `RunHookHandlerJob` | Execute webhook handlers | `webhooks` | 300s |
| `RunSyncJob` | Sync data with external systems | `sync` | 600s |
| `SendInvitationEmail` | Send user invitation emails | `notifications` | 60s |
| `SendLockoutNotificationJob` | Notify admins of account lockouts | `notifications` | 60s |
| `SyncDeviceJob` | Sync device data | `sync` | 300s |

---

## Model Observers

Model observers listen to Eloquent model lifecycle events and perform actions automatically.

### Observer Structure

```php
namespace App\Observers;

use App\Models\ModelName;

class ModelNameObserver
{
    public function creating(ModelName $model): void
    {
        // Before model is created
    }

    public function created(ModelName $model): void
    {
        // After model is created
    }

    public function updating(ModelName $model): void
    {
        // Before model is updated
    }

    public function updated(ModelName $model): void
    {
        // After model is updated
    }

    public function deleting(ModelName $model): void
    {
        // Before model is deleted
    }

    public function deleted(ModelName $model): void
    {
        // After model is deleted
    }
}
```

### Core Observers

#### 1. CustomFieldDefinitionObserver

**File:** `app/Observers/CustomFieldDefinitionObserver.php`

Invalidates custom field definition cache when definitions change.

```php
namespace App\Observers;

use App\Domain\Shared\Models\CustomFieldDefinition;
use Illuminate\Support\Facades\Cache;

class CustomFieldDefinitionObserver
{
    public function created(CustomFieldDefinition $definition): void
    {
        $this->clearCache($definition);
    }

    public function updated(CustomFieldDefinition $definition): void
    {
        $this->clearCache($definition);
    }

    public function deleted(CustomFieldDefinition $definition): void
    {
        $this->clearCache($definition);
    }

    private function clearCache(CustomFieldDefinition $definition): void
    {
        Cache::forget("custom_fields:{$definition->company_id}:{$definition->entity_type}");
    }
}
```

**Purpose:**
- Ensures custom field definitions are always fresh
- Clears cache on create, update, delete
- Company and entity-type specific cache keys


#### 2. EntityAliasObserver

**File:** `app/Observers/EntityAliasObserver.php`

Invalidates entity alias cache when aliases change.

```php
namespace App\Observers;

use App\Domain\Shared\Models\EntityAlias;
use Illuminate\Support\Facades\Cache;

class EntityAliasObserver
{
    public function created(EntityAlias $alias): void
    {
        $this->clearCache($alias);
    }

    public function updated(EntityAlias $alias): void
    {
        $this->clearCache($alias);
    }

    public function deleted(EntityAlias $alias): void
    {
        $this->clearCache($alias);
    }

    private function clearCache(EntityAlias $alias): void
    {
        Cache::forget("entity_aliases:{$alias->company_id}");
    }
}
```

**Purpose:**
- Maintains fresh entity alias mappings
- Company-specific cache invalidation
- Supports dynamic entity naming

### Observer Registration

Observers are registered in `AppServiceProvider`:

```php
namespace App\Providers;

use App\Domain\Shared\Models\CustomFieldDefinition;
use App\Domain\Shared\Models\EntityAlias;
use App\Observers\CustomFieldDefinitionObserver;
use App\Observers\EntityAliasObserver;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        CustomFieldDefinition::observe(CustomFieldDefinitionObserver::class);
        EntityAlias::observe(EntityAliasObserver::class);
    }
}
```

---

## Queue Configuration

**File:** `config/queue.php`

### Default Connection

```php
'default' => env('QUEUE_CONNECTION', 'database'),
```

### Available Connections

| Connection | Driver | Use Case |
|------------|--------|----------|
| `sync` | Synchronous | Development, testing |
| `database` | Database | Default production setup |
| `redis` | Redis | High-performance production |
| `sqs` | AWS SQS | Cloud-based queuing |
| `beanstalkd` | Beanstalkd | Alternative queue backend |
| `deferred` | Deferred | Laravel 12 deferred execution |
| `background` | Background | Laravel 12 background execution |
| `failover` | Failover | Automatic fallback (database → deferred) |

### Database Queue Configuration

```php
'database' => [
    'driver' => 'database',
    'connection' => env('DB_QUEUE_CONNECTION'),
    'table' => env('DB_QUEUE_TABLE', 'jobs'),
    'queue' => env('DB_QUEUE', 'default'),
    'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
    'after_commit' => false,
],
```

### Redis Queue Configuration

```php
'redis' => [
    'driver' => 'redis',
    'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
    'block_for' => null,
    'after_commit' => false,
],
```

### Job Batching

```php
'batching' => [
    'database' => env('DB_CONNECTION', 'sqlite'),
    'table' => 'job_batches',
],
```

### Failed Jobs

```php
'failed' => [
    'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
    'database' => env('DB_CONNECTION', 'sqlite'),
    'table' => 'failed_jobs',
],
```


### Queue Names

Gen-ERP uses multiple named queues for priority management:

| Queue Name | Priority | Purpose | Jobs |
|------------|----------|---------|------|
| `default` | Normal | General background tasks | FilterableCustomFieldJob |
| `imports` | Low | Bulk data imports | ProcessImportJob, ImportProductsJob |
| `audit` | Low | Audit logging | RecordAuditLog |
| `notifications` | High | Email notifications | SendNotificationJob, SendInvitationEmail |
| `webhooks` | Normal | Webhook processing | RunHookHandlerJob |
| `sync` | Normal | External system sync | RunSyncJob, SyncDeviceJob |

### Running Queue Workers

**Development (single worker):**
```bash
php artisan queue:work
```

**Production (specific queue):**
```bash
php artisan queue:work --queue=notifications,default,imports,audit
```

**With Supervisor (recommended):**
```ini
[program:gen-erp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/gen-erp/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --queue=notifications,default,imports,audit
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/gen-erp/storage/logs/worker.log
stopwaitsecs=3600
```

**Horizon (Redis only):**
```bash
php artisan horizon
```

---

## Broadcasting

Gen-ERP uses Laravel Reverb for real-time event broadcasting.

### Broadcasting Configuration

**File:** `config/broadcasting.php`

```php
'default' => env('BROADCAST_CONNECTION', 'reverb'),

'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'host' => env('REVERB_HOST', '0.0.0.0'),
            'port' => env('REVERB_PORT', 8080),
            'scheme' => env('REVERB_SCHEME', 'http'),
        ],
    ],
],
```

### Broadcast Events

Events that implement `ShouldBroadcast` are automatically broadcast:

```php
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class EventName implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'event.name';
    }

    public function broadcastWith(): array
    {
        return [
            'data' => $this->data,
        ];
    }
}
```

### Channel Types

**Private Channels:**
```php
new PrivateChannel("company.{$companyId}")
new PrivateChannel("branch.{$branchId}")
new PrivateChannel("user.{$userId}")
```

**Presence Channels:**
```php
new PresenceChannel("chat.{$roomId}")
```

### Frontend Listening

**JavaScript (Laravel Echo):**
```javascript
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
})

// Listen to private channel
Echo.private(`company.${companyId}`)
    .listen('.stock.low', (event) => {
        console.log('Low stock alert:', event)
        showNotification(`Low stock: ${event.productName}`)
    })
    .listen('.pos.sale.completed', (event) => {
        console.log('POS sale completed:', event)
        refreshDashboard()
    })
    .listen('.import.progress', (event) => {
        console.log('Import progress:', event)
        updateProgressBar(event.processedRows, event.totalRows)
    })
```


---

## Best Practices

### Event Design

1. **Immutable Events**
   ```php
   // Good: readonly properties
   public function __construct(
       public readonly Invoice $invoice,
       public readonly int $amount
   ) {}
   
   // Bad: mutable properties
   public Invoice $invoice;
   public int $amount;
   ```

2. **Descriptive Names**
   ```php
   // Good
   InvoiceSent, SalesOrderConfirmed, LowStockAlert
   
   // Bad
   InvoiceEvent, OrderEvent, StockEvent
   ```

3. **Domain-Specific Events**
   ```php
   // Good: Domain namespace
   namespace App\Domain\Invoice\Events;
   
   // Bad: Generic namespace
   namespace App\Events;
   ```

### Listener Design

1. **Single Responsibility**
   ```php
   // Good: One listener per action
   class SendInvoiceNotification implements ShouldQueue
   {
       public function handle(InvoiceSent $event): void
       {
           // Only send notification
       }
   }
   
   // Bad: Multiple responsibilities
   class HandleInvoiceSent implements ShouldQueue
   {
       public function handle(InvoiceSent $event): void
       {
           // Send notification
           // Update analytics
           // Log to external service
           // etc.
       }
   }
   ```

2. **Queue Long-Running Listeners**
   ```php
   // Good: Queued for external API calls
   class SendInvoiceNotification implements ShouldQueue
   {
       public function handle(InvoiceSent $event): void
       {
           Mail::send(...);
       }
   }
   
   // Bad: Synchronous external call
   class SendInvoiceNotification
   {
       public function handle(InvoiceSent $event): void
       {
           Mail::send(...); // Blocks request
       }
   }
   ```

3. **Idempotency**
   ```php
   // Good: Idempotent listener
   public function handle(CreditNoteApplied $event): void
   {
       if ($originalJournal->reversed_by_id !== null) {
           return; // Already processed
       }
       
       $idempotencyKey = "credit-note-reversal-{$creditNote->id}";
       $this->postingService->reverse(..., $idempotencyKey);
   }
   ```

### Job Design

1. **Unique Jobs**
   ```php
   // Good: Prevents duplicate processing
   class ProcessImportJob implements ShouldBeUnique, ShouldQueue
   {
       public function uniqueId(): string
       {
           return 'import-'.$this->importJob->id;
       }
   }
   ```

2. **Timeout Configuration**
   ```php
   // Good: Appropriate timeout
   class ProcessImportJob implements ShouldQueue
   {
       public int $timeout = 3600; // 1 hour for large imports
   }
   
   class SendNotificationJob implements ShouldQueue
   {
       public int $timeout = 60; // 1 minute for emails
   }
   ```

3. **Retry Strategy**
   ```php
   // Good: Exponential backoff
   class SendNotificationJob implements ShouldQueue
   {
       public int $tries = 3;
       public int $backoff = 60; // 60 seconds between retries
   }
   
   // Alternative: Custom backoff
   public function backoff(): array
   {
       return [60, 300, 900]; // 1min, 5min, 15min
   }
   ```

4. **Error Handling**
   ```php
   // Good: Graceful failure
   public function handle(): void
   {
       try {
           $this->processData();
       } catch (\Throwable $e) {
           Log::error('Job failed', [
               'job' => static::class,
               'error' => $e->getMessage(),
           ]);
           
           // Re-throw for retry
           throw $e;
       }
   }
   ```

### Observer Design

1. **Cache Invalidation**
   ```php
   // Good: Clear cache on model changes
   class CustomFieldDefinitionObserver
   {
       public function created(CustomFieldDefinition $definition): void
       {
           Cache::forget("custom_fields:{$definition->company_id}");
       }
   }
   ```

2. **Avoid Heavy Operations**
   ```php
   // Good: Dispatch job for heavy work
   public function created(Product $product): void
   {
       ProcessProductImages::dispatch($product);
   }
   
   // Bad: Heavy operation in observer
   public function created(Product $product): void
   {
       $this->imageService->processAllImages($product); // Blocks request
   }
   ```

### Broadcasting Best Practices

1. **Private Channels**
   ```php
   // Good: Company-scoped channel
   public function broadcastOn(): array
   {
       return [
           new PrivateChannel("company.{$this->companyId}"),
       ];
   }
   ```

2. **Selective Data**
   ```php
   // Good: Only broadcast necessary data
   public function broadcastWith(): array
   {
       return [
           'id' => $this->id,
           'status' => $this->status,
           'message' => $this->message,
       ];
   }
   
   // Bad: Broadcast entire model
   public function broadcastWith(): array
   {
       return $this->toArray(); // May expose sensitive data
   }
   ```

3. **Event Naming**
   ```php
   // Good: Descriptive event name
   public function broadcastAs(): string
   {
       return 'stock.low';
   }
   
   // Bad: Generic name
   public function broadcastAs(): string
   {
       return 'event';
   }
   ```

---

## Troubleshooting

### Queue Not Processing

**Check queue worker:**
```bash
# Check if worker is running
ps aux | grep "queue:work"

# Check worker logs
tail -f storage/logs/worker.log
```

**Restart queue worker:**
```bash
php artisan queue:restart
```

### Failed Jobs

**View failed jobs:**
```bash
php artisan queue:failed
```

**Retry failed job:**
```bash
php artisan queue:retry {job-id}
```

**Retry all failed jobs:**
```bash
php artisan queue:retry all
```

**Flush failed jobs:**
```bash
php artisan queue:flush
```

### Broadcasting Not Working

**Check Reverb server:**
```bash
php artisan reverb:start
```

**Check Echo configuration:**
```javascript
// Verify environment variables
console.log(import.meta.env.VITE_REVERB_APP_KEY)
console.log(import.meta.env.VITE_REVERB_HOST)
```

**Check channel authorization:**
```php
// routes/channels.php
Broadcast::channel('company.{companyId}', function ($user, $companyId) {
    return $user->company_id === (int) $companyId;
});
```

---

**Last Updated:** March 4, 2026  
**Version:** 1.0.0  
**Maintainer:** Gen-ERP Development Team
