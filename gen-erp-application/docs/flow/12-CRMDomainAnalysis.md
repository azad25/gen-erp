# CRM Domain - Complete Analysis

## Overview

The CRM domain manages the complete customer relationship lifecycle from lead generation to deal closure, with pipeline-based opportunity tracking and activity management.

## Backend Architecture

### 1. Core Models

#### Lead Model (`app/Domain/CRM/Models/Lead.php`)

**Purpose:** Core CRM entity representing a potential customer

**Database Schema:**
```php
$fillable = [
  'uuid',                    // UUID
  'company_id',              // Multi-tenancy
  'assigned_to',             // Assigned user (FK)
  'created_by',              // Creator user (FK)
  'first_name',              // First name
  'last_name',               // Last name
  'email',                  // Email
  'phone',                  // Phone
  'company_name',            // Company name
  'job_title',              // Job title
  'address',                // Address
  'city',                   // City
  'state',                  // State
  'country',                // Country
  'postal_code',            // Postal code
  'status',                 // LeadStatus enum
  'source',                 // LeadSource enum
  'score',                  // Lead score (0-100)
  'estimated_value',        // Estimated deal value
  'currency',               // Currency
  'expected_close_date',   // Expected close date
  'last_contacted_at',      // Last contacted at
  'qualified_at',           // Qualified at
  'converted_at',           // Converted at
  'converted_to_customer_id', // Converted customer (FK)
  'custom_fields',           // Custom data (JSON)
  'notes',                  // Notes
];

// Auto-generates UUID on creation
$uuid = Str::uuid();
```

**Relationships:**
```php
company() -> Company
assignedTo() -> User
createdBy() -> User
convertedToCustomer() -> Customer
notes() -> LeadNote (hasMany)
activities() -> CrmActivity (hasMany)
tags() -> LeadTag (belongsToMany)
opportunities() -> Opportunity (hasMany)
```

**Key Accessors:**
```php
public function getFullNameAttribute(): string {
  return trim($this->first_name . ' ' . $this->last_name);
}

public function getIsQualifiedAttribute(): bool {
  return $this->status === LeadStatus::QUALIFIED;
}

public function getIsConvertedAttribute(): bool {
  return $this->status === LeadStatus::CONVERTED;
}
```

#### Opportunity Model (`app/Domain/CRM/Models/Opportunity.php`)

**Purpose:** Pipeline-based deal tracking

**Database Schema:**
```php
$fillable = [
  'uuid',                    // UUID
  'company_id',              // Multi-tenancy
  'pipeline_id',             // Pipeline (FK)
  'stage_id',                // PipelineStage (FK)
  'lead_id',                 // Lead (FK)
  'customer_id',             // Customer (FK)
  'assigned_to',             // Assigned user (FK)
  'created_by',              // Creator user (FK)
  'name',                   // Opportunity name
  'description',            // Description
  'amount',                 // Deal amount
  'currency',               // Currency
  'probability',            // Probability (0-100)
  'expected_close_date',   // Expected close date
  'actual_close_date',      // Actual close date
  'status',                 // Status (open, won, lost)
  'close_reason',           // Close reason
  'won_reason',             // Won reason
  'lost_reason',            // Lost reason
  'stage_order',            // Stage order
  'source',                 // Source
  'campaign',               // Campaign
  'products',               // Products (JSON)
  'discount_amount',        // Discount amount
  'tax_amount',             // Tax amount
  'total_amount',           // Total amount
  'last_activity_at',       // Last activity at
  'won_at',                 // Won at
  'lost_at',                // Lost at
  'days_in_stage',          // Days in stage
  'custom_fields',           // Custom data (JSON)
  'notes',                  // Notes
];

// Auto-generates UUID on creation
$uuid = Str::uuid();
```

**Relationships:**
```php
company() -> Company
pipeline() -> Pipeline
stage() -> PipelineStage
lead() -> Lead
customer() -> Customer
assignedTo() -> User
createdBy() -> User
activities() -> CrmActivity (hasMany)
```

#### Pipeline Model (`app/Domain/CRM/Models/Pipeline.php`)

**Purpose:** Sales pipeline configuration

**Database Schema:**
```php
$fillable = [
  'uuid',                    // UUID
  'company_id',              // Multi-tenancy
  'created_by',              // Creator user (FK)
  'name',                   // Pipeline name
  'description',            // Description
  'color',                  // Color
  'is_default',             // Is default pipeline
  'is_active',              // Is active
  'sort_order',             // Sort order
  'settings',               // Settings (JSON)
  'auto_move_stages',       // Auto move stages
  'default_probability',    // Default probability
  'opportunities_count',    // Opportunities count
  'total_value',            // Total value
  'won_value',              // Won value
  'lost_value',             // Lost value
  'conversion_rate',        // Conversion rate
];

// Auto-generates UUID on creation
$uuid = Str::uuid();
```

**Relationships:**
```php
company() -> Company
createdBy() -> User
stages() -> PipelineStage (hasMany)
opportunities() -> Opportunity (hasMany)
```

**Key Method:**
```php
public function createDefaultStages(): void {
  $stages = [
    ['name' => 'New', 'probability' => 10, 'sort_order' => 1],
    ['name' => 'Qualified', 'probability' => 30, 'sort_order' => 2],
    ['name' => 'Proposal', 'probability' => 60, 'sort_order' => 3],
    ['name' => 'Negotiation', 'probability' => 80, 'sort_order' => 4],
    ['name' => 'Closed Won', 'probability' => 100, 'sort_order' => 5, 'is_closed_won' => true],
  ];

  foreach ($stages as $stage) {
    PipelineStage::create(array_merge($stage, [
      'company_id' => $this->company_id,
      'pipeline_id' => $this->id,
    ]));
  }
}
```

#### PipelineStage Model (`app/Domain/CRM/Models/PipelineStage.php`)

**Purpose:** Stage within a pipeline

**Database Schema:**
```php
$fillable = [
  'uuid',                    // UUID
  'company_id',              // Multi-tenancy
  'pipeline_id',             // Pipeline (FK)
  'created_by',              // Creator user (FK)
  'name',                   // Stage name
  'description',            // Description
  'color',                  // Color
  'sort_order',             // Sort order
  'is_active',              // Is active
  'probability',            // Probability (0-100)
  'is_closed_won',          // Is closed won stage
  'is_closed_lost',         // Is closed lost stage
  'requires_reason',        // Requires reason
  'entry_actions',          // Entry actions (JSON)
  'exit_actions',           // Exit actions (JSON)
  'max_days_in_stage',      // Max days in stage
  'opportunities_count',    // Opportunities count
  'total_value',            // Total value
  'average_days',           // Average days
  'conversion_rate',        // Conversion rate
];

// Auto-generates UUID on creation
$uuid = Str::uuid();
```

**Relationships:**
```php
company() -> Company
pipeline() -> Pipeline
createdBy() -> User
opportunities() -> Opportunity (hasMany)
```

#### CrmActivity Model (`app/Domain/CRM/Models/CrmActivity.php`)

**Purpose:** Activities (calls, emails, meetings, tasks)

**Database Schema:**
```php
$fillable = [
  'uuid',                    // UUID
  'company_id',              // Multi-tenancy
  'user_id',                 // User (FK)
  'subject_type',            // Subject type (Lead, Opportunity, etc.)
  'subject_id',              // Subject ID
  'type',                   // ActivityType enum
  'title',                  // Title
  'description',            // Description
  'status',                 // Status
  'priority',               // Priority
  'scheduled_at',           // Scheduled at
  'started_at',             // Started at
  'completed_at',           // Completed at
  'duration_minutes',       // Duration minutes
  'planned_duration_minutes', // Planned duration
  'direction',              // Direction
  'outcome',                // Outcome
  'outcome_notes',          // Outcome notes
  'due_date',               // Due date
  'is_reminder',            // Is reminder
  'reminder_at',            // Reminder at
  'reminder_sent',          // Reminder sent
  'email_subject',          // Email subject
  'email_body',             // Email body
  'attachments',            // Attachments (JSON)
  'meeting_location',       // Meeting location
  'meeting_link',           // Meeting link
  'attendees',              // Attendees (JSON)
  'custom_fields',           // Custom data (JSON)
  'metadata',               // Metadata (JSON)
];

// Auto-generates UUID on creation
$uuid = Str::uuid();
```

**Relationships:**
```php
company() -> Company
user() -> User
subject() -> MorphTo (Lead, Opportunity, etc.)
```

#### CrmContact Model

**Purpose:** Contact information for leads/opportunities

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'lead_id',                 // Lead (FK)
  'opportunity_id',          // Opportunity (FK)
  'first_name',              // First name
  'last_name',               // Last name
  'email',                  // Email
  'phone',                  // Phone
  'mobile',                 // Mobile
  'address',                // Address
  'city',                   // City
  'state',                  // State
  'country',                // Country
  'postal_code',            // Postal code
  'job_title',              // Job title
  'company_name',            // Company name
  'notes',                  // Notes
];
```

### 2. Services

#### LeadService (`app/Domain/CRM/Services/LeadService.php`)

**Purpose:** Lead management

**Methods:**

```php
// Create a new lead
public function create(LeadData $data, int $companyId, int $createdBy): Lead {
  return DB::transaction(function () use ($data, $companyId, $createdBy): Lead {
    $leadData = array_merge($data->toArray(), [
      'company_id' => $companyId,
      'created_by' => $createdBy,
    ]);

    $lead = Lead::create($leadData);

    // Auto-assign if specified
    if ($data->assignedTo) {
      $lead->update(['assigned_to' => $data->assignedTo]);
    }

    // Create initial note if provided
    if ($data->notes) {
      $this->addNote($lead, $data->notes, $createdBy, ['type' => 'general']);
    }

    return $lead->fresh();
  });
}

// Get leads for a company with filters
public function getForCompany(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator {
  $query = Lead::forCompany($companyId)
    ->with(['assignedTo', 'createdBy', 'tags', 'convertedToCustomer']);

  // Apply filters
  if (isset($filters['status'])) {
    $query->byStatus(LeadStatus::from($filters['status']));
  }

  if (isset($filters['assigned_to'])) {
    $query->assignedTo($filters['assigned_to']);
  }

  if (isset($filters['source'])) {
    $query->bySource(LeadSource::from($filters['source']));
  }

  if (isset($filters['min_score'])) {
    $query->highScore($filters['min_score']);
  }

  if (isset($filters['search'])) {
    $search = $filters['search'];
    $query->where(function ($q) use ($search) {
      $q->where('first_name', 'like', "%{$search}%")
        ->orWhere('last_name', 'like', "%{$search}%")
        ->orWhere('email', 'like', "%{$search}%")
        ->orWhere('company_name', 'like', "%{$search}%");
    });
  }

  // Sorting
  $sortBy = $filters['sort_by'] ?? 'created_at';
  $sortOrder = $filters['sort_order'] ?? 'desc';
  $query->orderBy($sortBy, $sortOrder);

  return $query->paginate($perPage);
}

// Assign lead to user
public function assignTo(Lead $lead, int $userId): Lead {
  $lead->update(['assigned_to' => $userId]);
  return $lead->fresh();
}

// Update lead score
public function updateScore(Lead $lead, int $score): Lead {
  $lead->updateScore($score);
  return $lead->fresh();
}

// Qualify a lead
public function qualify(Lead $lead): Lead {
  $lead->qualify();
  return $lead->fresh();
}

// Convert lead to customer
public function convertToCustomer(Lead $lead, Customer $customer): Lead {
  $lead->convertToCustomer($customer);
  return $lead->fresh();
}

// Add note to lead
public function addNote(Lead $lead, string $content, int $userId, array $options = []): void {
  LeadNote::create([
    'lead_id' => $lead->id,
    'content' => $content,
    'created_by' => $userId,
    'type' => $options['type'] ?? 'general',
    'is_private' => $options['is_private'] ?? false,
  ]);
}
```

#### OpportunityService (`app/Domain/CRM/Services/OpportunityService.php`)

**Purpose:** Opportunity management

**Methods:**

```php
// Create a new opportunity
public function create(OpportunityData $data, int $companyId, int $createdBy): Opportunity {
  return DB::transaction(function () use ($data, $companyId, $createdBy): Opportunity {
    $opportunityData = array_merge($data->toArray(), [
      'company_id' => $companyId,
      'created_by' => $createdBy,
      'status' => 'open',
    ]);

    // Get stage probability if not provided
    if (!$data->probability) {
      $stage = PipelineStage::find($data->stageId);
      $opportunityData['probability'] = $stage?->probability ?? 10;
    }

    $opportunity = Opportunity::create($opportunityData);

    // Update pipeline and stage metrics
    $opportunity->pipeline->updateMetrics();
    $opportunity->stage->updateMetrics();

    return $opportunity->fresh();
  });
}

// Get opportunities for a company with filters
public function getForCompany(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator {
  $query = Opportunity::forCompany($companyId)
    ->with(['pipeline', 'stage', 'lead', 'customer', 'assignedTo', 'createdBy']);

  // Apply filters
  if (isset($filters['status'])) {
    $query->where('status', $filters['status']);
  }

  if (isset($filters['pipeline_id'])) {
    $query->inPipeline($filters['pipeline_id']);
  }

  if (isset($filters['stage_id'])) {
    $query->inStage($filters['stage_id']);
  }

  if (isset($filters['assigned_to'])) {
    $query->assignedTo($filters['assigned_to']);
  }

  if (isset($filters['min_amount'])) {
    $query->where('amount', '>=', $filters['min_amount']);
  }

  if (isset($filters['max_amount'])) {
    $query->where('amount', '<=', $filters['max_amount']);
  }

  if (isset($filters['search'])) {
    $search = $filters['search'];
    $query->where(function ($q) use ($search) {
      $q->where('name', 'like', "%{$search}%")
        ->orWhere('description', 'like', "%{$search}%");
    });
  }

  // Sorting
  $sortBy = $filters['sort_by'] ?? 'created_at';
  $sortOrder = $filters['sort_order'] ?? 'desc';
  $query->orderBy($sortBy, $sortOrder);

  return $query->paginate($perPage);
}

// Get opportunities for a pipeline
public function getForPipeline(int $pipelineId, int $companyId): Collection {
  return Opportunity::forCompany($companyId)
    ->inPipeline($pipelineId)
    ->with(['stage', 'assignedTo'])
    ->orderBy('stage_order')
    ->get();
}

// Get opportunities for a stage
public function getForStage(int $stageId, int $companyId): Collection {
  return Opportunity::forCompany($companyId)
    ->inStage($stageId)
    ->with(['pipeline', 'assignedTo'])
    ->get();
}

// Move opportunity to next stage
public function moveToNextStage(Opportunity $opportunity): Opportunity {
  $nextStage = $opportunity->stage->getNextStage();
  
  if (!$nextStage) {
    throw new \Exception('No next stage available');
  }

  $opportunity->update([
    'stage_id' => $nextStage->id,
    'probability' => $nextStage->probability,
  ]);

  $opportunity->stage->updateMetrics();
  $nextStage->updateMetrics();

  return $opportunity->fresh();
}

// Mark opportunity as won
public function markAsWon(Opportunity $opportunity, string $reason): Opportunity {
  return DB::transaction(function () use ($opportunity, $reason): Opportunity {
    $opportunity->update([
      'status' => 'won',
      'won_at' => now(),
      'won_reason' => $reason,
      'actual_close_date' => now(),
    ]);

    // Update pipeline metrics
    $opportunity->pipeline->updateMetrics();
    $opportunity->stage->updateMetrics();

    // Convert lead to customer if linked
    if ($opportunity->lead && !$opportunity->customer) {
      $customer = Customer::create([
        'company_id' => $opportunity->company_id,
        'name' => $opportunity->lead->full_name,
        'email' => $opportunity->lead->email,
        'phone' => $opportunity->lead->phone,
        'address' => $opportunity->lead->address,
      ]);

      $opportunity->lead->convertToCustomer($customer);
      $opportunity->update(['customer_id' => $customer->id]);
    }

    return $opportunity->fresh();
  });
}

// Mark opportunity as lost
public function markAsLost(Opportunity $opportunity, string $reason): Opportunity {
  return DB::transaction(function () use ($opportunity, $reason): Opportunity {
    $opportunity->update([
      'status' => 'lost',
      'lost_at' => now(),
      'lost_reason' => $reason,
      'actual_close_date' => now(),
    ]);

    // Update pipeline metrics
    $opportunity->pipeline->updateMetrics();
    $opportunity->stage->updateMetrics();

    return $opportunity->fresh();
  });
}
```

#### PipelineService (`app/Domain/CRM/Services/PipelineService.php`)

**Purpose:** Pipeline management

**Methods:**

```php
// Create a new pipeline
public function create(array $data, int $companyId, int $createdBy): Pipeline {
  return DB::transaction(function () use ($data, $companyId, $createdBy): Pipeline {
    $pipelineData = array_merge($data, [
      'company_id' => $companyId,
      'created_by' => $createdBy,
      'sort_order' => $this->getNextSortOrder($companyId),
    ]);

    $pipeline = Pipeline::create($pipelineData);

    // Create default stages if requested
    if ($data['create_default_stages'] ?? true) {
      $pipeline->createDefaultStages();
    }

    return $pipeline->fresh();
  });
}

// Get pipelines for a company
public function getForCompany(int $companyId): Collection {
  return Pipeline::forCompany($companyId)
    ->with(['stages' => function ($query) {
      $query->orderBy('sort_order');
    }])
    ->orderBy('sort_order')
    ->get();
}

// Get active pipelines
public function getActive(int $companyId): Collection {
  return Pipeline::forCompany($companyId)
    ->active()
    ->with(['stages' => function ($query) {
      $query->active()->orderBy('sort_order');
    }])
    ->orderBy('sort_order')
    ->get();
}

// Get default pipeline
public function getDefault(int $companyId): ?Pipeline {
  return Pipeline::forCompany($companyId)->default()->first();
}

// Set pipeline as default
public function setAsDefault(Pipeline $pipeline): Pipeline {
  return DB::transaction(function () use ($pipeline): Pipeline {
    // Remove default from other pipelines
    Pipeline::forCompany($pipeline->company_id)
      ->where('id', '!=', $pipeline->id)
      ->update(['is_default' => false]);

    // Set this pipeline as default
    $pipeline->update(['is_default' => true, 'is_active' => true]);

    return $pipeline->fresh();
  });
}

// Create a stage
public function createStage(Pipeline $pipeline, array $data, int $createdBy): PipelineStage {
  $stageData = array_merge($data, [
    'company_id' => $pipeline->company_id,
    'pipeline_id' => $pipeline->id,
    'created_by' => $createdBy,
    'sort_order' => $this->getNextStageSortOrder($pipeline),
  ]);

  return PipelineStage::create($stageData);
}

// Update stage
public function updateStage(PipelineStage $stage, array $data): PipelineStage {
  $stage->update($data);
  return $stage->fresh();
}

// Delete stage
public function deleteStage(PipelineStage $stage): bool {
  return DB::transaction(function () use ($stage): bool {
    // Check if stage has opportunities
    if ($stage->opportunities()->count() > 0) {
      throw new \Exception('Cannot delete stage with existing opportunities');
    }

    return $stage->delete();
  });
}
```

#### ActivityService (`app/Domain/CRM/Services/ActivityService.php`)

**Purpose:** Activity management

**Methods:**

```php
// Create a new activity
public function create(ActivityData $data, int $companyId, int $userId): CrmActivity {
  $activityData = array_merge($data->toArray(), [
    'company_id' => $companyId,
    'user_id' => $userId,
  ]);

  return CrmActivity::create($activityData);
}

// Get activities for a company with filters
public function getForCompany(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator {
  $query = CrmActivity::forCompany($companyId)
    ->with(['user', 'subject']);

  // Apply filters
  if (isset($filters['type'])) {
    $query->byType(ActivityType::from($filters['type']));
  }

  if (isset($filters['status'])) {
    $query->byStatus($filters['status']);
  }

  if (isset($filters['user_id'])) {
    $query->where('user_id', $filters['user_id']);
  }

  if (isset($filters['subject_type'])) {
    $query->where('subject_type', $filters['subject_type']);
  }

  if (isset($filters['subject_id'])) {
    $query->where('subject_id', $filters['subject_id']);
  }

  if (isset($filters['search'])) {
    $search = $filters['search'];
    $query->where(function ($q) use ($search) {
      $q->where('title', 'like', "%{$search}%")
        ->orWhere('description', 'like', "%{$search}%");
    });
  }

  // Sorting
  $sortBy = $filters['sort_by'] ?? 'scheduled_at';
  $sortOrder = $filters['sort_order'] ?? 'asc';
  $query->orderBy($sortBy, $sortOrder);

  return $query->paginate($perPage);
}

// Get activities for a subject
public function getForSubject(string $subjectType, int $subjectId, int $companyId): Collection {
  return CrmActivity::forCompany($companyId)
    ->where('subject_type', $subjectType)
    ->where('subject_id', $subjectId)
    ->with(['user'])
    ->orderBy('created_at', 'desc')
    ->get();
}

// Get activities for a user
public function getForUser(int $userId, int $companyId, array $filters = []): Collection {
  $query = CrmActivity::forCompany($companyId)
    ->where('user_id', $userId)
    ->with(['subject']);

  if (isset($filters['status'])) {
    $query->byStatus($filters['status']);
  }

  if (isset($filters['type'])) {
    $query->byType(ActivityType::from($filters['type']));
  }

  return $query->orderBy('scheduled_at', 'asc')->get();
}

// Get scheduled activities
public function getScheduled(int $companyId, ?\DateTime $date = null): Collection {
  $query = CrmActivity::forCompany($companyId)->scheduled();

  if ($date) {
    $query->whereDate('scheduled_at', $date);
  }

  return $query->with(['user', 'subject'])
    ->orderBy('scheduled_at', 'asc')
    ->get();
}

// Get overdue activities
public function getOverdue(int $companyId): Collection {
  return CrmActivity::forCompany($companyId)
    ->overdue()
    ->with(['user', 'subject'])
    ->orderBy('due_date', 'asc')
    ->get();
}

// Get due today activities
public function getDueToday(int $companyId): Collection {
  return CrmActivity::forCompany($companyId)
    ->dueToday()
    ->with(['user', 'subject'])
    ->orderBy('due_date', 'asc')
    ->get();
}

// Complete an activity
public function complete(CrmActivity $activity, array $data): CrmActivity {
  return DB::transaction(function () use ($activity, $data): CrmActivity {
    $activity->update([
      'status' => 'completed',
      'completed_at' => now(),
      'outcome' => $data['outcome'] ?? null,
      'outcome_notes' => $data['outcome_notes'] ?? null,
      'duration_minutes' => $data['duration_minutes'] ?? null,
    ]);

    // Update subject's last_activity_at
    if ($activity->subject) {
      $activity->subject->update(['last_activity_at' => now()]);
    }

    return $activity->fresh();
  });
}

// Reschedule an activity
public function reschedule(CrmActivity $activity, \DateTime $newDate): CrmActivity {
  $activity->update(['scheduled_at' => $newDate]);
  return $activity->fresh();
}
```

## Frontend Architecture

### 1. CRM/Dashboard/Index.vue

**Purpose:** CRM overview dashboard

**Metrics Displayed:**
- Total Leads (with delta)
- Active Opportunities (with delta)
- Pipeline Value (with delta)
- Conversion Rate (with delta)

**Charts:**
- Lead Trend (7d, 30d, 90d, 365d)
- Opportunity by Stage
- Revenue by Source

**Top Performers:**
- Deals Won
- Revenue
- Activities

**API Calls:**
```javascript
GET /api/v1/crm/dashboard - Dashboard metrics
GET /api/v1/crm/leads/trend - Trend data
GET /api/v1/crm/opportunities/pipeline - Pipeline data
```

### 2. CRM/Leads/Index.vue

**Purpose:** Manage leads

**Features:**
- List leads with columns:
  - Name
  - Email
  - Company
  - Status (Badge)
  - Source (Badge)
  - Score
  - Assigned To
- Actions:
  - View
  - Edit
  - Assign
  - Qualify
  - Convert
  - Delete
- Create/Edit modal with form
- Bulk actions modal

**Form Fields:**
- First Name (required)
- Last Name (required)
- Email (required)
- Phone
- Company Name
  - Job Title
  - Address
  - City
  - State
  - Country
  - Postal Code
  - Source
  - Estimated Value
  - Expected Close Date
  - Notes

**API Calls:**
```javascript
GET /api/v1/crm/leads - List leads
POST /api/v1/crm/leads - Create lead
PUT /api/v1/crm/leads/{id} - Update lead
DELETE /api/v1/crm/leads/{id} - Delete lead
POST /api/v1/crm/leads/{id}/assign - Assign lead
POST /api/v1/crm/leads/{id}/qualify - Qualify lead
POST /api/v1/crm/leads/{id}/convert - Convert to customer
POST /api/v1/crm/leads/{id}/score - Update score
```

### 3. CRM/Pipelines/Index.vue

**Purpose:** Manage pipelines

**Features:**
- List pipelines with columns:
  - Name
  - Opportunities Count
  - Total Value
  - Conversion Rate
  - Status (Active/Inactive)
- Actions:
  - View
  - Edit
  - Set Default
  - Activate
  - Deactivate
  - Delete

**API Calls:**
```javascript
GET /api/v1/crm/pipelines - List pipelines
POST /api/v1/crm/pipelines - Create pipeline
PUT /api/v1/crm/pipelines/{id} - Update pipeline
DELETE /api/v1/crm/pipelines/{id} - Delete pipeline
POST /api/v1/crm/pipelines/{id}/default - Set as default
POST /api/v1/crm/pipelines/{id}/activate - Activate
POST /api/v1/crm/pipelines/{id}/deactivate - Deactivate
```

### 4. CRM/Pipelines/KanbanBoard.vue

**Purpose:** Kanban board for opportunities

**Features:**
- Drag and drop opportunities between stages
- Visual pipeline view
- Stage metrics (count, value, conversion rate)
- Opportunity cards with key info

**API Calls:**
```javascript
GET /api/v1/crm/opportunities/pipeline/{id} - Get pipeline opportunities
POST /api/v1/crm/opportunities/{id}/move-stage - Move to next stage
POST /api/v1/crm/opportunities/{id}/won - Mark as won
POST /api/v1/crm/opportunities/{id}/lost - Mark as lost
```

### 5. CRM/Activities/Index.vue

**Purpose:** Manage activities

**Features:**
- List activities with columns:
  - Title
  - Type (Badge)
  - Status (Badge)
  - Scheduled Date
  - Assigned To
- Actions:
  - View
  - Edit
  - Complete
  - Reschedule
  - Delete
- Create/Edit modal with form

**Form Fields:**
- Title (required)
- Type (Call, Email, Meeting, Task)
- Status
  - Priority
  - Scheduled Date
  - Due Date
  - Duration
  - Description
  - Subject Type
  - Subject ID

**API Calls:**
```javascript
GET /api/v1/crm/activities - List activities
POST /api/v1/crm/activities - Create activity
PUT /api/v1/crm/activities/{id} - Update activity
DELETE /api/v1/crm/activities/{id} - Delete activity
POST /api/v1/crm/activities/{id}/complete - Complete activity
POST /api/v1/crm/activities/{id}/reschedule - Reschedule activity
```

## Complete Data Flow

### Lead Lifecycle Flow

```
Lead Created
    ↓
LeadService::create()
    ├─→ Create Lead
    │   ├─→ Set status = NEW
    │   ├─→ Set UUID
    │   ├─→ Assign to user (if specified)
    │   └─→ Create initial note (if provided)
    └─→ Return Lead
    ↓
Lead Contacted
    ↓
LeadService::update() or LeadService::assignTo()
    ├─→ Update status = CONTACTED
    ├─→ Update last_contacted_at
    └─→ Return Lead
    ↓
Lead Qualified
    ↓
LeadService::qualify()
    ├─→ Update status = QUALIFIED
    ├─→ Update qualified_at
    ├─→ Update score
    └─→ Return Lead
    ↓
Lead Converted to Customer
    ↓
LeadService::convertToCustomer()
    ├─→ Create Customer
    │   ├─→ Copy lead info
    │   └─→ Set customer details
    ├─→ Update Lead
    │   ├─→ Set status = CONVERTED
    │   ├─→ Set converted_at
    │   └─→ Set converted_to_customer_id
    └─→ Return Lead
```

### Opportunity Pipeline Flow

```
Opportunity Created
    ↓
OpportunityService::create()
    ├─→ Create Opportunity
    │   ├─→ Set status = OPEN
    │   ├─→ Set probability from stage
    │   ├─→ Set stage_order
    │   └─→ Link to lead/customer
    ├─→ Update pipeline metrics
    └─→ Update stage metrics
    ↓
Opportunity Moved to Next Stage
    ↓
OpportunityService::moveToNextStage()
    ├─→ Get next stage
    ├─→ Update stage_id
    ├─→ Update probability
    ├─→ Update stage_order
    ├─→ Update days_in_stage
    ├─→ Update old stage metrics
    └─→ Update new stage metrics
    ↓
Opportunity Won
    ↓
OpportunityService::markAsWon()
    ├─→ Update status = WON
    ├─→ Set won_at
    ├─→ Set won_reason
    ├─→ Set actual_close_date
    ├─→ Update pipeline metrics
    ├─→ Update stage metrics
    ├─→ Convert lead to customer (if linked)
    └─→ Return Opportunity
    ↓
Opportunity Lost
    ↓
OpportunityService::markAsLost()
    ├─→ Update status = LOST
    ├─→ Set lost_at
    ├─→ Set lost_reason
    ├─→ Set actual_close_date
    ├─→ Update pipeline metrics
    └─→ Update stage metrics
```

### Activity Flow

```
Activity Created
    ↓
ActivityService::create()
    ├─→ Create CrmActivity
    │   ├─→ Set UUID
    │   ├─→ Link to subject (Lead, Opportunity, etc.)
    │   ├─→ Set type (Call, Email, Meeting, Task)
    │   ├─→ Set status
    │   └─→ Set scheduled_at
    └─→ Return CrmActivity
    ↓
Activity Completed
    ↓
ActivityService::complete()
    ├─→ Update status = COMPLETED
    ├─→ Set completed_at
    ├─→ Set outcome
    ├─→ Set outcome_notes
    ├─→ Set duration_minutes
    ├─→ Update subject's last_activity_at
    └─→ Return CrmActivity
    ↓
Activity Rescheduled
    ↓
ActivityService::reschedule()
    ├─→ Update scheduled_at
    └─→ Return CrmActivity
```

## Integration with Other Domains

### Customer Domain

**Lead to Customer Conversion:**
```php
LeadService::convertToCustomer($lead, $customer)
  ├─→ Create Customer from lead info
  │   ├─→ Copy name, email, phone, address
  │   └─-> Set customer details
  ├─→ Update Lead
  │   ├─→ Set status = CONVERTED
  │   ├─→ Set converted_at
  │   └─→ Set converted_to_customer_id
  └─→ OpportunityService::markAsWon()
      ├─→ Update opportunity customer_id
      └─-> Create sales opportunity
```

### Sales Domain

**Opportunity to Sales Order:**
```
Opportunity Won
    ↓
Create Sales Order
    ├─→ Copy opportunity details
    ├─→ Copy products
    ├─-> Set customer_id
    └─→ Create SalesOrder

Sales Order Confirmed
    ↓
Create Invoice
    ├─-> Copy sales order details
    └─-> Create Invoice
```

### Accounting Domain

**Won Opportunity Journal Entry:**
```
Opportunity Won
    ↓
AccountingService::journalForOpportunity()
    ├─→ DR: Accounts Receivable (total_amount)
    ├─→ CR: Sales Revenue (subtotal)
    ├─-> CR: Output VAT Payable (tax_amount)
    └─-> Post journal entry
```

## Comparison with Modern ERPs

### Features Comparison

| Feature | This System | Odoo | Zoho |
|---------|-------------|------|------|
| **Lead Management** | ✅ | ✅ | ✅ |
| **Opportunity Tracking** | ✅ | ✅ | ✅ |
| **Pipeline Management** | ✅ | ✅ | ✅ |
| **Activity Tracking** | ✅ | ✅ | ✅ |
| **Lead Scoring** | ✅ | ✅ | ✅ |
| **Lead Qualification** | ✅ | ✅ | ✅ |
| **Lead to Customer Conversion** | ✅ | ✅ | ✅ |
| **Kanban Board** | ✅ | ✅ | ✅ |
| **Activity Types** | ✅ | ✅ | ✅ |
| **Email Integration** | ⚠️ Basic | ✅ | ✅ |
| **Calendar Integration** | ❌ | ✅ | ✅ |
| **Email Templates** | ⚠️ Basic | ✅ | ✅ |
| **Lead Import** | ✅ | ✅ | ✅ |
| **Bulk Actions** | ✅ | ✅ | ✅ |
| **Lead Tags** | ✅ | ✅ | ✅ |
| **Lead Notes** | ✅ | ✅ | ✅ |
| **Activity Reminders** | ✅ | ✅ | ✅ |
| **Activity Rescheduling** | ✅ | ✅ | ✅ |
| **Pipeline Metrics** | ✅ | ✅ | ✅ |
| **Stage Metrics** | ✅ | ✅ | ✅ |
| **Conversion Tracking** | ✅ | ✅ | ✅ |
| **Revenue Forecasting** | ⚠️ Basic | ✅ | ✅ |
| **Sales Forecasting** | ❌ | ✅ | ✅ |
| **Quotation Management** | ❌ | ✅ | ✅ |
| **Contract Management** | ❌ | ✅ | ✅ |
| **Multi-currency** | ⚠️ Limited | ✅ | ✅ |

### Workflow Comparison

**This System:**
```
Lead: NEW → CONTACTED → QUALIFIED → CONVERTED
Opportunity: OPEN → WON/LOST
Activity: SCHEDULED → IN_PROGRESS → COMPLETED
```

**Odoo:**
```
Lead: NEW → ASSIGNED → QUALIFIED → CONVERTED
Opportunity: NEW → NEGOTIATION → WON/LOST
Activity: SCHEDULED → IN_PROGRESS → COMPLETED
```

**Zoho:**
```
Lead: NEW → QUALIFIED → CONVERTED
Opportunity: NEW → NEGOTIATION → WON/LOST
Activity: SCHEDULED → IN_PROGRESS → COMPLETED
```

### Unique Features

**This System:**
- Simplified workflow
- UUID-based identification
- Pipeline metrics auto-update
- Stage metrics auto-update
- Lead scoring
- Activity reminders
- Kanban board
- Bangladesh localization (BDT)
- Multi-tenancy

**Odoo/Zoho:**
- Email integration
- Calendar integration
- Email templates
- Revenue forecasting
- Sales forecasting
- Quotation management
- Contract management
- Multi-currency support

## API Reference

### Leads

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/crm/leads` | List leads | Required |
| GET | `/api/v1/crm/leads/{id}` | Get lead | Required |
| POST | `/api/v1/crm/leads` | Create lead | Required |
| PUT | `/api/v1/crm/leads/{id}` | Update lead | Required |
| DELETE | `/api/v1/crm/leads/{id}` | Delete lead | Required |
| POST | `/api/v1/crm/leads/{id}/assign` | Assign lead | Required |
| POST | `/api/v1/crm/leads/{id}/qualify` | Qualify lead | Required |
| POST | `/api/v1/crm/leads/{id}/convert` | Convert to customer | Required |
| POST | `/api/v1/crm/leads/{id}/score` | Update score | Required |

### Opportunities

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/crm/opportunities` | List opportunities | Required |
| GET | `/api/v1/crm/opportunities/{id}` | Get opportunity | Required |
| POST | `/api/v1/crm/opportunities` | Create opportunity | Required |
| PUT | `/api/v1/crm/opportunities/{id}` | Update opportunity | Required |
| DELETE | `/api/v1/crm/opportunities/{id}` | Delete opportunity | Required |
| POST | `/api/v1/crm/opportunities/{id}/move-stage` | Move to next stage | Required |
| POST | `/api/v1/crm/opportunities/{id}/won` | Mark as won | Required |
| POST | `/api/v1/crm/opportunities/{id}/lost` | Mark as lost | Required |

### Pipelines

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/crm/pipelines` | List pipelines | Required |
| GET | `/api/v1/crm/pipelines/{id}` | Get pipeline | Required |
| POST | `/api/v1/crm/pipelines` | Create pipeline | Required |
| PUT | `/api/v1/crm/pipelines/{id}` | Update pipeline | Required |
| DELETE | `/api/v1/crm/pipelines/{id}` | Delete pipeline | Required |
| POST | `/api/v1/crm/pipelines/{id}/default` | Set as default | Required |
| POST | `/api/v1/crm/pipelines/{id}/activate` | Activate | Required |
| POST | `/api/v1/crm/pipelines/{id}/deactivate` | Deactivate | Required |

### Activities

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/crm/activities` | List activities | Required |
| GET | `/api/v1/crm/activities/{id}` | Get activity | Required |
| POST | `/api/v1/crm/activities` | Create activity | Required |
| PUT | `/api/v1/crm/activities/{id}` | Update activity | Required |
| DELETE | `/api/v1/crm/activities/{id}` | Delete activity | Required |
| POST | `/api/v1/crm/activities/{id}/complete` | Complete activity | Required |
| POST | `/api/v1/crm/activities/{id}/reschedule` | Reschedule activity | Required |

### Query Parameters (Index)

```
search -> Filter by name, email, company
status -> Filter by status
source -> Filter by source
assigned_to -> Filter by assigned user
min_score -> Filter by minimum score
date_from -> Filter by date from
date_to -> Filter by date to
sort_by -> Sort field
sort_order -> Sort order (asc/desc)
per_page -> Pagination (default: 15)
page -> Page number
```

### Request Body (Create Lead)

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+8801700000000",
  "company_name": "Acme Corp",
  "job_title": "Manager",
  "address": "123 Main St",
  "city": "Dhaka",
  "country": "Bangladesh",
  "source": "website",
  "estimated_value": 100000,
  "expected_close_date": "2026-04-30",
  "notes": "Interested in our product"
}
```

### Response Format

```json
{
  "success": true,
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john@example.com",
    "company_name": "Acme Corp",
    "status": "new",
    "source": "website",
    "score": 50,
    "assigned_to": {
      "id": 1,
      "name": "Admin User"
    }
  },
  "message": "Lead created"
}
```

## Frontend API Integration

### CRM/Leads/Index.vue

```javascript
const fetchLeads = async (page = 1) => {
  const response = await get('/crm/leads', { page, per_page: 15 })
  leads.value = response.data
  pagination.value = response.meta
}

const createLead = async () => {
  const data = {
    first_name: form.value.first_name,
    last_name: form.value.last_name,
    email: form.value.email,
    phone: form.value.phone,
    company_name: form.value.company_name,
    source: form.value.source,
    estimated_value: form.value.estimated_value,
  }
  
  await post('/crm/leads', data)
  await fetchLeads()
}

const qualifyLead = async (lead) => {
  await post(`/crm/leads/${lead.id}/qualify`)
  await fetchLeads()
}

const convertLead = async (lead) => {
  await post(`/crm/leads/${lead.id}/convert`)
  await fetchLeads()
}
```

### CRM/Pipelines/KanbanBoard.vue

```javascript
const moveOpportunity = async (opportunity, stageId) => {
  await post(`/crm/opportunities/${opportunity.id}/move-stage`, { stage_id: stageId })
  await fetchPipelineOpportunities()
}

const markAsWon = async (opportunity) => {
  const reason = prompt('Enter won reason:')
  await post(`/crm/opportunities/${opportunity.id}/won`, { reason })
  await fetchPipelineOpportunities()
}

const markAsLost = async (opportunity) => {
  const reason = prompt('Enter lost reason:')
  await post(`/crm/opportunities/${opportunity.id}/lost`, { reason })
  await fetchPipelineOpportunities()
}
```

## Summary

### Backend Coverage
- ✅ Lead model (auto-UUID, status tracking, scoring, conversion)
- ✅ Opportunity model (pipeline-based, stage tracking, probability)
- ✅ Pipeline model (configuration, metrics, default stages)
- ✅ PipelineStage model (probability, closed won/lost, entry/exit actions)
- ✅ CrmActivity model (calls, emails, meetings, tasks)
- ✅ CrmContact model (contact information)
- ✅ LeadService (create, update, assign, qualify, convert, score)
- ✅ OpportunityService (create, update, move stage, won/lost)
- ✅ PipelineService (create, update, default, activate, stages)
- ✅ ActivityService (create, complete, reschedule, reminders)
- ✅ Multi-tenancy support
- ✅ UUID-based identification

### Frontend Coverage
- ✅ CRM/Dashboard/Index.vue (metrics, charts, top performers)
- ✅ CRM/Leads/Index.vue (list, create, edit, assign, qualify, convert)
- ✅ CRM/Pipelines/Index.vue (list, create, edit, default, activate)
- ✅ CRM/Pipelines/KanbanBoard.vue (drag-drop, stage metrics)
- ✅ CRM/Activities/Index.vue (list, create, complete, reschedule)
- ✅ BanglaAmount component (BDT formatting)
- ✅ Badge component (status display)
- ✅ Pagination support

### Integration
- ✅ Lead to Customer conversion (Customer domain)
- ✅ Opportunity to Sales Order (Sales domain)
- ✅ Won Opportunity journal entry (Accounting domain)
- ✅ Activity subject linking (Lead, Opportunity, etc.)
- ✅ Pipeline metrics auto-update
- ✅ Stage metrics auto-update
- ✅ Lead scoring (0-100)
- ✅ Activity reminders
- ✅ Multi-tenancy (company isolation)
- ✅ Bangladesh localization (BDT, Bangla name)

The CRM system provides **comprehensive customer relationship management** that follows modern CRM patterns with tight integration to customer, sales, and accounting domains.
















Created comprehensive CRM domain analysis at [docs/flow/12-CRMDomainAnalysis.md](cci:7://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/docs/flow/12-CRMDomainAnalysis.md:0:0-0:0) covering:

## Backend Architecture
- **Lead Model** - UUID-based identification, status tracking (NEW → CONTACTED → QUALIFIED → CONVERTED), scoring (0-100), conversion to customer
- **Opportunity Model** - Pipeline-based deal tracking, stage probability, status (OPEN → WON/LOST), days in stage
- **Pipeline Model** - Sales pipeline configuration, metrics (opportunities_count, total_value, won_value, lost_value, conversion_rate), default stages creation
- **PipelineStage Model** - Stage configuration, probability (0-100), closed won/lost flags, entry/exit actions, max days in stage
- **CrmActivity Model** - Activities (Call, Email, Meeting, Task), status tracking, reminders, subject linking (Lead, Opportunity)
- **CrmContact Model** - Contact information for leads/opportunities

## Data Flows
- **Lead Lifecycle:** Create → Contact → Qualify → Convert to Customer
- **Opportunity Pipeline:** Create → Move to Next Stage → Won/Lost
- **Activity Flow:** Create → Complete/Reschedule

## Services
- **LeadService:** create, update, assign, qualify, convert, score
- **OpportunityService:** create, update, move to next stage, mark as won/lost
- **PipelineService:** create, update, default, activate, stages management
- **ActivityService:** create, complete, reschedule, reminders

## Integration
- **Customer Domain:** Lead to Customer conversion (copy info, set converted_to_customer_id)
- **Sales Domain:** Opportunity to Sales Order (copy details, create SalesOrder)
- **Accounting Domain:** Won Opportunity journal entry (DR: Accounts Receivable, CR: Sales Revenue/Output VAT)

## Frontend Architecture
- **CRM/Dashboard/Index.vue** - Metrics (leads, opportunities, pipeline value, conversion rate), charts, top performers
- **CRM/Leads/Index.vue** - List, create, edit, assign, qualify, convert
- **CRM/Pipelines/Index.vue** - List, create, edit, default, activate
- **CRM/Pipelines/KanbanBoard.vue** - Drag-drop, stage metrics, opportunity cards
- **CRM/Activities/Index.vue** - List, create, complete, reschedule

## Comparison with Modern ERPs
- **Similar:** Core CRM management, lead lifecycle, opportunity tracking, pipeline management, activity tracking, lead scoring
- **Simpler:** No email integration, no calendar integration, no email templates, no revenue forecasting, no quotation management
- **Unique:** UUID-based identification, pipeline metrics auto-update, stage metrics auto-update, Bangladesh localization (BDT), simplified workflow