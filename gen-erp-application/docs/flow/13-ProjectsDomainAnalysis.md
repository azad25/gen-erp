# Projects Domain - Complete Analysis

## Overview

The Projects domain provides Jira-like project management with Kanban boards, task tracking, time logging, and team collaboration features.

## Backend Architecture

### 1. Core Models

#### Project Model (`app/Domain/Project/Models/Project.php`)

**Purpose:** Core project entity

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'name',                   // Project name
  'description',            // Description
  'status',                 // Status (planning, active, on_hold, completed, cancelled)
  'priority',               // Priority (low, medium, high, urgent)
  'start_date',             // Start date
  'end_date',               // End date
  'budget',                 // Budget
  'currency',               // Currency
  'client_name',            // Client name
  'client_email',           // Client email
  'client_phone',           // Client phone
  'project_manager_id',     // Project manager (FK)
  'progress_percentage',    // Progress percentage (0-100)
  'is_billable',            // Is billable
  'hourly_rate',            // Hourly rate
  'estimated_hours',        // Estimated hours
  'actual_hours',           // Actual hours
  'color',                  // Color
  'settings',               // Settings (JSON)
];

// Status constants
public const STATUS_PLANNING = 'planning';
public const STATUS_ACTIVE = 'active';
public const STATUS_ON_HOLD = 'on_hold';
public const STATUS_COMPLETED = 'completed';
public const STATUS_CANCELLED = 'cancelled';

// Priority constants
public const PRIORITY_LOW = 'low';
public const PRIORITY_MEDIUM = 'medium';
public const PRIORITY_HIGH = 'high';
public const PRIORITY_URGENT = 'urgent';
```

**Relationships:**
```php
company() -> Company
projectManager() -> Employee
members() -> Employee (belongsToMany with pivot)
phases() -> ProjectPhase (hasMany)
boards() -> Board (hasMany)
tasks() -> Task (hasMany)
timeEntries() -> TimeEntry (hasMany)
attachments() -> ProjectAttachment (hasMany)
comments() -> ProjectComment (hasMany)
```

#### Task Model (`app/Domain/Project/Models/Task.php`)

**Purpose:** Task entity with Kanban board support

**Database Schema:**
```php
$fillable = [
  'project_id',              // Project (FK)
  'board_id',                // Board (FK)
  'board_column_id',         // BoardColumn (FK)
  'parent_task_id',          // Parent task (FK)
  'phase_id',                // Phase (FK)
  'title',                  // Title
  'description',            // Description
  'status',                 // Status (todo, in_progress, in_review, testing, completed, cancelled)
  'priority',               // Priority (low, medium, high, urgent)
  'type',                   // Type (task, bug, feature, improvement, epic, story)
  'assignee_id',            // Assignee (FK)
  'reporter_id',            // Reporter (FK)
  'start_date',             // Start date
  'due_date',               // Due date
  'estimated_hours',        // Estimated hours
  'actual_hours',           // Actual hours
  'story_points',           // Story points
  'position',               // Position in column
  'tags',                   // Tags (JSON)
  'settings',               // Settings (JSON)
];

// Status constants
public const STATUS_TODO = 'todo';
public const STATUS_IN_PROGRESS = 'in_progress';
public const STATUS_IN_REVIEW = 'in_review';
public const STATUS_TESTING = 'testing';
public const STATUS_COMPLETED = 'completed';
public const STATUS_CANCELLED = 'cancelled';

// Priority constants
public const PRIORITY_LOW = 'low';
public const PRIORITY_MEDIUM = 'medium';
public const PRIORITY_HIGH = 'high';
public const PRIORITY_URGENT = 'urgent';

// Type constants
public const TYPE_TASK = 'task';
public const TYPE_BUG = 'bug';
public const TYPE_FEATURE = 'feature';
public const TYPE_IMPROVEMENT = 'improvement';
public const TYPE_EPIC = 'epic';
public const TYPE_STORY = 'story';
```

**Relationships:**
```php
project() -> Project
board() -> Board
boardColumn() -> BoardColumn
parentTask() -> Task (self)
subtasks() -> Task (hasMany)
assignee() -> Employee
reporter() -> Employee
watchers() -> Employee (belongsToMany)
comments() -> TaskComment (hasMany)
attachments() -> TaskAttachment (hasMany)
checklists() -> TaskChecklist (hasMany)
dependencies() -> TaskDependency (hasMany)
dependents() -> TaskDependency (hasMany)
timeEntries() -> TimeEntry (hasMany)
```

#### Board Model (`app/Domain/Project/Models/Board.php`)

**Purpose:** Kanban/Scrum board configuration

**Database Schema:**
```php
$fillable = [
  'project_id',              // Project (FK)
  'name',                   // Board name
  'description',            // Description
  'type',                   // Type (kanban, scrum, custom)
  'is_default',             // Is default board
  'settings',               // Settings (JSON)
];

// Type constants
public const TYPE_KANBAN = 'kanban';
public const TYPE_SCRUM = 'scrum';
public const TYPE_CUSTOM = 'custom';
```

**Relationships:**
```php
project() -> Project
columns() -> BoardColumn (hasMany)
tasks() -> Task (hasMany)
```

**Key Method:**
```php
public function createDefaultColumns(): void {
  $defaultColumns = [
    ['name' => 'To Do', 'color' => '#6b7280', 'position' => 1],
    ['name' => 'In Progress', 'color' => '#3b82f6', 'position' => 2],
    ['name' => 'In Review', 'color' => '#f59e0b', 'position' => 3],
    ['name' => 'Done', 'color' => '#10b981', 'position' => 4],
  ];

  foreach ($defaultColumns as $columnData) {
    $this->columns()->create($columnData);
  }
}
```

#### BoardColumn Model (`app/Domain/Project/Models/BoardColumn.php`)

**Purpose:** Column within a board

**Database Schema:**
```php
$fillable = [
  'board_id',                // Board (FK)
  'name',                   // Column name
  'description',            // Description
  'color',                  // Color
  'position',               // Position
  'wip_limit',              // WIP limit
  'is_done_column',         // Is done column
  'settings',               // Settings (JSON)
];
```

**Relationships:**
```php
board() -> Board
tasks() -> Task (hasMany)
```

**Key Methods:**
```php
public function hasReachedWipLimit(): bool {
  if (!$this->wip_limit) {
    return false;
  }

  return $this->tasks()->count() >= $this->wip_limit;
}

public function moveToPosition(int $newPosition): void {
  $oldPosition = $this->position;
  
  if ($newPosition > $oldPosition) {
    // Moving right, shift columns left
    $this->board->columns()
      ->where('position', '>', $oldPosition)
      ->where('position', '<=', $newPosition)
      ->decrement('position');
  } else {
    // Moving left, shift columns right
    $this->board->columns()
      ->where('position', '>=', $newPosition)
      ->where('position', '<', $oldPosition)
      ->increment('position');
  }

  $this->position = $newPosition;
  $this->save();
}
```

#### TaskDependency Model

**Purpose:** Task dependencies

**Database Schema:**
```php
$fillable = [
  'task_id',                // Task (FK)
  'depends_on_task_id',     // Depends on task (FK)
  'dependency_type',        // Type (blocks, is_blocked_by, relates_to)
];

// Dependency type constants
public const TYPE_BLOCKS = 'blocks';
public const TYPE_IS_BLOCKED_BY = 'is_blocked_by';
public const TYPE_RELATES_TO = 'relates_to';
```

**Relationships:**
```php
task() -> Task
dependsOnTask() -> Task
```

#### TimeEntry Model

**Purpose:** Time tracking for tasks

**Database Schema:**
```php
$fillable = [
  'task_id',                // Task (FK)
  'user_id',                // User (FK)
  'project_id',             // Project (FK)
  'description',            // Description
  'hours',                  // Hours
  'entry_date',             // Entry date
  'type',                   // Type (development, meeting, review, testing, documentation, other)
  'is_billable',            // Is billable
  'hourly_rate',            // Hourly rate
  'amount',                 // Amount (calculated)
];

// Type constants
public const TYPE_DEVELOPMENT = 'development';
public const TYPE_MEETING = 'meeting';
public const TYPE_REVIEW = 'review';
public const TYPE_TESTING = 'testing';
public const TYPE_DOCUMENTATION = 'documentation';
public const TYPE_OTHER = 'other';
```

**Relationships:**
```php
task() -> Task
user() -> User
project() -> Project
```

**Key Method:**
```php
protected static function boot() {
  parent::boot();

  static::saving(function ($timeEntry) {
    if ($timeEntry->hours && $timeEntry->hourly_rate) {
      $timeEntry->amount = $timeEntry->hours * $timeEntry->hourly_rate;
    }
  });
}
```

#### TaskChecklist Model

**Purpose:** Task checklists

**Database Schema:**
```php
$fillable = [
  'task_id',                // Task (FK)
  'title',                  // Title
  'sort_order',             // Sort order
];
```

**Relationships:**
```php
task() -> Task
items() -> TaskChecklistItem (hasMany)
```

**Key Methods:**
```php
public function getCompletionPercentage(): float {
  $totalItems = $this->items()->count();
  
  if ($totalItems === 0) {
    return 0;
  }

  $completedItems = $this->items()->where('is_completed', true)->count();
  return ($completedItems / $totalItems) * 100;
}

public function isCompleted(): bool {
  return $this->items()->where('is_completed', false)->count() === 0;
}
```

#### TaskComment Model

**Purpose:** Task comments

**Database Schema:**
```php
$fillable = [
  'task_id',                // Task (FK)
  'user_id',                // User (FK)
  'comment',                // Comment
  'is_internal',            // Is internal
];
```

**Relationships:**
```php
task() -> Task
user() -> User
```

#### TaskAttachment Model

**Purpose:** Task attachments

**Database Schema:**
```php
$fillable = [
  'task_id',                // Task (FK)
  'user_id',                // User (FK)
  'filename',               // Filename
  'filepath',               // Filepath
  'filesize',               // Filesize
  'mime_type',              // MIME type
];
```

**Relationships:**
```php
task() -> Task
user() -> User
```

### 2. Services

#### ProjectService (`app/Domain/Project/Services/ProjectService.php`)

**Purpose:** Project management

**Methods:**

```php
// Get all projects for a company
public function getAllProjects(int $companyId, array $filters = []): LengthAwarePaginator {
  $query = Project::where('company_id', $companyId)
    ->with(['projectManager', 'members']);

  // Apply filters
  if (isset($filters['status'])) {
    $query->where('status', $filters['status']);
  }

  if (isset($filters['priority'])) {
    $query->where('priority', $filters['priority']);
  }

  if (isset($filters['project_manager_id'])) {
    $query->where('project_manager_id', $filters['project_manager_id']);
  }

  if (isset($filters['search'])) {
    $query->where(function ($q) use ($filters) {
      $q->where('name', 'like', '%' . $filters['search'] . '%')
        ->orWhere('description', 'like', '%' . $filters['search'] . '%')
        ->orWhere('client_name', 'like', '%' . $filters['search'] . '%');
    });
  }

  if (isset($filters['overdue']) && $filters['overdue']) {
    $query->overdue();
  }

  // Sort
  $sortBy = $filters['sort_by'] ?? 'created_at';
  $sortOrder = $filters['sort_order'] ?? 'desc';
  $query->orderBy($sortBy, $sortOrder);

  return $query->paginate($filters['per_page'] ?? 15);
}

// Create a new project
public function createProject(CreateProjectData $data): Project {
  $project = Project::create([
    'company_id' => $data->companyId,
    'name' => $data->name,
    'description' => $data->description,
    'status' => $data->status ?? Project::STATUS_PLANNING,
    'priority' => $data->priority ?? Project::PRIORITY_MEDIUM,
    'start_date' => $data->startDate,
    'end_date' => $data->endDate,
    'budget' => $data->budget,
    'currency' => $data->currency ?? 'USD',
    'client_name' => $data->clientName,
    'client_email' => $data->clientEmail,
    'client_phone' => $data->clientPhone,
    'project_manager_id' => $data->projectManagerId,
    'is_billable' => $data->isBillable ?? true,
    'hourly_rate' => $data->hourlyRate,
    'estimated_hours' => $data->estimatedHours,
    'color' => $data->color,
    'settings' => $data->settings ?? [],
  ]);

  // Create default board
  $this->createDefaultBoard($project);

  // Add project manager as member if specified
  if ($data->projectManagerId) {
    $this->addProjectMember($project->id, $data->projectManagerId, 'lead');
  }

  // Add initial members if specified
  if (!empty($data->memberIds)) {
    foreach ($data->memberIds as $memberId) {
      $this->addProjectMember($project->id, $memberId, 'member');
    }
  }

  return $project->fresh(['projectManager', 'members', 'boards']);
}

// Update a project
public function updateProject(int $projectId, UpdateProjectData $data): Project {
  $project = Project::findOrFail($projectId);

  $project->update([
    'name' => $data->name ?? $project->name,
    'description' => $data->description ?? $project->description,
    'status' => $data->status ?? $project->status,
    'priority' => $data->priority ?? $project->priority,
    'start_date' => $data->startDate ?? $project->start_date,
    'end_date' => $data->endDate ?? $project->end_date,
    'budget' => $data->budget ?? $project->budget,
    'currency' => $data->currency ?? $project->currency,
    'client_name' => $data->clientName ?? $project->client_name,
    'client_email' => $data->clientEmail ?? $project->client_email,
    'client_phone' => $data->clientPhone ?? $project->client_phone,
    'project_manager_id' => $data->projectManagerId ?? $project->project_manager_id,
    'is_billable' => $data->isBillable ?? $project->is_billable,
    'hourly_rate' => $data->hourlyRate ?? $project->hourly_rate,
    'estimated_hours' => $data->estimatedHours ?? $project->estimated_hours,
    'color' => $data->color ?? $project->color,
    'settings' => array_merge($project->settings ?? [], $data->settings ?? []),
  ]);

  return $project->fresh(['projectManager', 'members']);
}

// Add project member
public function addProjectMember(int $projectId, int $employeeId, string $role = 'member'): void {
  $project = Project::findOrFail($projectId);
  
  $project->members()->syncWithoutDetaching([
    $employeeId => [
      'role' => $role,
      'joined_at' => now(),
    ],
  ]);
}

// Remove project member
public function removeProjectMember(int $projectId, int $employeeId): void {
  $project = Project::findOrFail($projectId);
  $project->members()->detach($employeeId);
}

// Update project progress
public function updateProgress(int $projectId): void {
  $project = Project::findOrFail($projectId);
  
  $totalTasks = $project->tasks()->count();
  $completedTasks = $project->tasks()->where('status', Task::STATUS_COMPLETED)->count();
  
  $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
  
  $project->update(['progress_percentage' => $progress]);
}
```

#### TaskService (`app/Domain/Project/Services/TaskService.php`)

**Purpose:** Task management

**Methods:**

```php
// Get all tasks for a project
public function getProjectTasks(int $projectId, array $filters = []): LengthAwarePaginator {
  $query = Task::where('project_id', $projectId)
    ->with(['assignee', 'reporter', 'boardColumn', 'parentTask', 'subtasks']);

  // Apply filters
  if (isset($filters['status'])) {
    $query->where('status', $filters['status']);
  }

  if (isset($filters['priority'])) {
    $query->where('priority', $filters['priority']);
  }

  if (isset($filters['type'])) {
    $query->where('type', $filters['type']);
  }

  if (isset($filters['assignee_id'])) {
    $query->where('assignee_id', $filters['assignee_id']);
  }

  if (isset($filters['board_column_id'])) {
    $query->where('board_column_id', $filters['board_column_id']);
  }

  if (isset($filters['parent_task_id'])) {
    $query->where('parent_task_id', $filters['parent_task_id']);
  } elseif (isset($filters['only_parent_tasks']) && $filters['only_parent_tasks']) {
    $query->whereNull('parent_task_id');
  }

  if (isset($filters['search'])) {
    $query->where(function ($q) use ($filters) {
      $q->where('title', 'like', '%' . $filters['search'] . '%')
        ->orWhere('description', 'like', '%' . $filters['search'] . '%');
    });
  }

  if (isset($filters['overdue']) && $filters['overdue']) {
    $query->overdue();
  }

  if (isset($filters['due_today']) && $filters['due_today']) {
    $query->dueToday();
  }

  if (isset($filters['due_this_week']) && $filters['due_this_week']) {
    $query->dueThisWeek();
  }

  if (isset($filters['tags']) && is_array($filters['tags'])) {
    foreach ($filters['tags'] as $tag) {
      $query->whereJsonContains('tags', $tag);
    }
  }

  // Sort
  $sortBy = $filters['sort_by'] ?? 'position';
  $sortOrder = $filters['sort_order'] ?? 'asc';
  
  if ($sortBy === 'position') {
    $query->orderBy('board_column_id')->orderBy('position');
  } else {
    $query->orderBy($sortBy, $sortOrder);
  }

  return $query->paginate($filters['per_page'] ?? 50);
}

// Create a new task
public function createTask(CreateTaskData $data): Task {
  // Get the project to validate access
  $project = Project::findOrFail($data->projectId);

  // If no board column specified, use the first column of the default board
  if (!$data->boardColumnId && !$data->boardId) {
    $defaultBoard = $project->boards()->where('is_default', true)->first();
    if ($defaultBoard) {
      $firstColumn = $defaultBoard->columns()->orderBy('position')->first();
      $data->boardColumnId = $firstColumn?->id;
    }
  } elseif ($data->boardId && !$data->boardColumnId) {
    $board = $project->boards()->findOrFail($data->boardId);
    $firstColumn = $board->columns()->orderBy('position')->first();
    $data->boardColumnId = $firstColumn?->id;
  }

  // Get next position in the column
  $position = 0;
  if ($data->boardColumnId) {
    $position = Task::where('board_column_id', $data->boardColumnId)->max('position') + 1;
  }

  $task = Task::create([
    'project_id' => $data->projectId,
    'board_id' => $data->boardId,
    'board_column_id' => $data->boardColumnId,
    'parent_task_id' => $data->parentTaskId,
    'title' => $data->title,
    'description' => $data->description,
    'status' => $data->status ?? Task::STATUS_TODO,
    'priority' => $data->priority ?? Task::PRIORITY_MEDIUM,
    'type' => $data->type ?? Task::TYPE_TASK,
    'assignee_id' => $data->assigneeId,
    'reporter_id' => $data->reporterId,
    'start_date' => $data->startDate,
    'due_date' => $data->dueDate,
    'estimated_hours' => $data->estimatedHours,
    'story_points' => $data->storyPoints,
    'position' => $position,
    'tags' => $data->tags ?? [],
    'settings' => $data->settings ?? [],
  ]);

  // Update project progress
  $project->updateProgress();

  return $task->fresh();
}

// Update a task
public function updateTask(int $taskId, UpdateTaskData $data): Task {
  $task = Task::findOrFail($taskId);
  $project = $task->project;

  $task->update([
    'title' => $data->title ?? $task->title,
    'description' => $data->description ?? $task->description,
    'status' => $data->status ?? $task->status,
    'priority' => $data->priority ?? $task->priority,
    'type' => $data->type ?? $task->type,
    'assignee_id' => $data->assigneeId ?? $task->assignee_id,
    'start_date' => $data->startDate ?? $task->start_date,
    'due_date' => $data->dueDate ?? $task->due_date,
    'estimated_hours' => $data->estimatedHours ?? $task->estimated_hours,
    'story_points' => $data->storyPoints ?? $task->story_points,
    'tags' => $data->tags ?? $task->tags,
    'settings' => array_merge($task->settings ?? [], $data->settings ?? []),
  ]);

  // Update project progress
  $project->updateProgress();

  return $task->fresh();
}

// Move task to column
public function moveTask(int $taskId, int $columnId, ?int $position = null): Task {
  $task = Task::findOrFail($taskId);
  $column = BoardColumn::findOrFail($columnId);

  // Check WIP limit
  if ($column->hasReachedWipLimit()) {
    throw new \Exception('Column has reached WIP limit');
  }

  // Move task to new column
  $task->update([
    'board_column_id' => $columnId,
    'position' => $position ?? ($column->tasks()->max('position') + 1),
  ]);

  // Update project progress
  $task->project->updateProgress();

  return $task->fresh();
}

// Complete a task
public function completeTask(int $taskId): Task {
  $task = Task::findOrFail($taskId);

  $task->update([
    'status' => Task::STATUS_COMPLETED,
    'actual_hours' => $task->timeEntries()->sum('hours'),
  ]);

  // Update project progress
  $task->project->updateProgress();

  return $task->fresh();
}

// Add task comment
public function addComment(int $taskId, int $userId, string $comment, bool $isInternal = false): TaskComment {
  return TaskComment::create([
    'task_id' => $taskId,
    'user_id' => $userId,
    'comment' => $comment,
    'is_internal' => $isInternal,
  ]);
}

// Add task attachment
public function addAttachment(int $taskId, int $userId, string $filename, string $filepath, int $filesize, string $mimeType): TaskAttachment {
  return TaskAttachment::create([
    'task_id' => $taskId,
    'user_id' => $userId,
    'filename' => $filename,
    'filepath' => $filepath,
    'filesize' => $filesize,
    'mime_type' => $mimeType,
  ]);
}
```

## Frontend Architecture

### 1. Projects/Dashboard.vue

**Purpose:** Project management dashboard

**Metrics Displayed:**
- Total Projects
- Active Projects
- Completed Projects
- Overdue Projects
- Projects by Status
- Projects by Priority
- My Tasks
- Focus Project

**Components:**
- ProjectSummaryGrid
- FocusProjectPanel
- ProjectListCard
- ProjectStatusPanels
- MyWorkList

**API Calls:**
```javascript
GET /api/v1/projects/dashboard - Dashboard metrics
```

### 2. Projects/Index.vue

**Purpose:** List all projects

**Features:**
- Filters:
  - Search (name, description, client)
  - Status (planning, active, on_hold, completed, cancelled)
  - Priority (low, medium, high, urgent)
  - Sort By (created_at, name, end_date, progress_percentage)
  - Overdue only
- Actions:
  - View
  - Edit
  - Delete
- Create Project button

**API Calls:**
```javascript
GET /api/v1/projects - List projects
POST /api/v1/projects - Create project
PUT /api/v1/projects/{id} - Update project
DELETE /api/v1/projects/{id} - Delete project
```

### 3. Projects/Board.vue

**Purpose:** Kanban board view

**Features:**
- Kanban board with drag-drop
- Board selector
- Task cards with key info
- Add Task button
- Board Settings
- Column management (add, edit, delete)
- WIP limit enforcement

**API Calls:**
```javascript
GET /api/v1/projects/{id}/board - Get board data
POST /api/v1/projects/{id}/tasks - Create task
PUT /api/v1/projects/{id}/tasks/{taskId} - Update task
DELETE /api/v1/projects/{id}/tasks/{taskId} - Delete task
POST /api/v1/projects/{id}/tasks/{taskId}/move - Move task
```

### 4. Projects/Create.vue

**Purpose:** Create new project

**Form Fields:**
- Name (required)
- Description
- Status
- Priority
- Start Date
- End Date
- Budget
- Currency
- Client Name
- Client Email
- Client Phone
- Project Manager
- Members
- Is Billable
- Hourly Rate
- Estimated Hours
- Color

**API Calls:**
```javascript
POST /api/v1/projects - Create project
```

### 5. Projects/Show.vue

**Purpose:** View project details

**Features:**
- Project overview
- Tasks list
- Board view
- Team members
- Time entries
- Reports

**API Calls:**
```javascript
GET /api/v1/projects/{id} - Get project details
```

### 6. Projects/Reports.vue

**Purpose:** Project reports

**Features:**
- Task completion by status
- Time logged by user
- Budget vs actual
- Progress tracking
- Burndown chart

**API Calls:**
```javascript
GET /api/v1/projects/{id}/reports - Get project reports
```

## Complete Data Flow

### Project Creation Flow

```
User creates project
    ↓
ProjectService::createProject()
    ├─→ Create Project
    │   ├─→ Set status = PLANNING
    │   ├─→ Set priority = MEDIUM
    │   ├─→ Set is_billable = true
    │   └─→ Set settings
    ├─→ Create Default Board
    │   └─→ Create Default Columns (To Do, In Progress, In Review, Done)
    ├─→ Add Project Manager as Member
    │   └─→ Set role = lead
    ├─→ Add Initial Members
    │   └─→ Set role = member
    └─→ Return Project
```

### Task Creation Flow

```
User creates task
    ↓
TaskService::createTask()
    ├─→ Validate Project Access
    ├─→ Get Default Board Column (if not specified)
    │   ├─→ Get default board
    │   └─→ Get first column
    ├─→ Calculate Position
    │   └─→ max(position) + 1
    ├─→ Create Task
    │   ├─→ Set status = TODO
    │   ├─→ Set priority = MEDIUM
    │   ├─→ Set type = TASK
    │   ├─→ Set position
    │   └─→ Set tags
    ├─→ Update Project Progress
    │   └─→ Calculate progress_percentage
    └─→ Return Task
```

### Task Movement Flow

```
User drags task to column
    ↓
TaskService::moveTask()
    ├─→ Check WIP Limit
    │   └─→ Throw exception if reached
    ├─→ Move Task to Column
    │   ├─→ Update board_column_id
    │   └─→ Update position
    ├─→ Update Project Progress
    │   └─→ Recalculate progress_percentage
    └─→ Return Task
```

### Task Completion Flow

```
User completes task
    ↓
TaskService::completeTask()
    ├─→ Update Task
    │   ├─→ Set status = COMPLETED
    │   └─→ Set actual_hours = sum(time_entries)
    ├─→ Update Project Progress
    │   └─→ Recalculate progress_percentage
    └─→ Return Task
```

### Time Logging Flow

```
User logs time
    ↓
TimeEntry::create()
    ├─→ Set task_id
    ├─→ Set user_id
    ├─→ Set project_id
    ├─→ Set hours
    ├─→ Set entry_date
    ├─→ Set type
    ├─→ Set is_billable
    ├─→ Set hourly_rate
    └─→ Calculate amount = hours * hourly_rate
```

## Integration with Other Domains

### HR Domain

**Employee Assignment:**
```php
Task Model
  ├─→ assignee_id -> Employee
  └─→ reporter_id -> Employee

Project Model
  ├─→ project_manager_id -> Employee
  └─→ members -> Employee (belongsToMany)

TimeEntry Model
  └─→ user_id -> User
```

**Time Tracking Integration:**
- Time entries linked to tasks
- Time entries linked to projects
- Employee time tracking for payroll
- Capacity planning integration

### CRM Domain

**Lead/Opportunity to Project:**
```
Lead/Opportunity Won
    ↓
Create Project
    ├─→ Copy lead/opportunity details
    ├─→ Set client_name from lead
    ├─→ Set budget from opportunity amount
    └─-> Create Project

Project Completed
    ↓
Update Opportunity
    ├─-> Set status = WON
    └─-> Set actual_close_date
```

### Accounting Domain

**Project Billing:**
```
Project Completed
    ↓
Create Invoice
    ├─→ Copy project details
    ├─-> Set customer_id
    ├─-> Set total_amount = actual_hours * hourly_rate
    └─-> Create Invoice

Invoice Paid
    ↓
AccountingService::journalForInvoice()
    ├─→ DR: Accounts Receivable
    ├─-> CR: Service Revenue
    └─-> CR: Output VAT Payable
```

## Comparison with Modern ERPs

### Features Comparison

| Feature | This System | Jira | Asana | Trello |
|---------|-------------|------|-------|-------|
| **Project Management** | ✅ | ✅ | ✅ | ✅ |
| **Kanban Boards** | ✅ | ✅ | ✅ | ✅ |
| **Task Management** | ✅ | ✅ | ✅ | ✅ |
| **Task Dependencies** | ✅ | ✅ | ✅ | ⚠️ |
| **Subtasks** | ✅ | ✅ | ✅ | ✅ |
| **Task Checklists** | ✅ | ✅ | ✅ | ✅ |
| **Task Comments** | ✅ | ✅ | ✅ | ✅ |
| **Task Attachments** | ✅ | ✅ | ✅ | ✅ |
| **Time Tracking** | ✅ | ⚠️ | ✅ | ⚠️ |
| **WIP Limits** | ✅ | ✅ | ✅ | ⚠️ |
| **Story Points** | ✅ | ✅ | ✅ | ❌ |
| **Sprint Planning** | ⚠️ | ✅ | ✅ | ❌ |
| **Burndown Charts** | ✅ | ✅ | ✅ | ❌ |
| **Gantt Charts** | ❌ | ✅ | ✅ | ❌ |
| **Resource Planning** | ⚠️ | ✅ | ✅ | ❌ |
| **Project Budgeting** | ✅ | ⚠️ | ✅ | ❌ |
| **Project Billing** | ✅ | ❌ | ✅ | ❌ |
| **Project Reports** | ✅ | ✅ | ✅ | ⚠️ |
| **Team Collaboration** | ✅ | ✅ | ✅ | ✅ |
| **Email Notifications** | ⚠️ | ✅ | ✅ | ✅ |
| **Mobile App** | ❌ | ✅ | ✅ | ✅ |
| **API Access** | ✅ | ✅ | ✅ | ✅ |

### Workflow Comparison

**This System:**
```
Project: PLANNING → ACTIVE → ON_HOLD → COMPLETED/CANCELLED
Task: TODO → IN_PROGRESS → IN_REVIEW → TESTING → COMPLETED/CANCELLED
Board: KANBAN/SCRUM/CUSTOM
```

**Jira:**
```
Project: BACKLOG → ACTIVE → ON_HOLD → COMPLETED/CANCELLED
Task: TODO → IN_PROGRESS → IN_REVIEW → DONE
Board: KANBAN/SCRUM
```

**Asana:**
```
Project: PLANNING → ACTIVE → ON_HOLD → COMPLETED/CANCELLED
Task: TODO → IN_PROGRESS → COMPLETED
Board: KANBAN/LIST/TIMELINE
```

### Unique Features

**This System:**
- Jira-like interface
- Bangladesh localization (BDT)
- Project billing integration
- Time tracking for payroll
- Capacity planning integration
- Multi-tenancy support
- WIP limit enforcement
- Project progress auto-calculation
- Task dependencies
- Story points support

**Jira/Asana/Trello:**
- Sprint planning
- Gantt charts
- Resource planning
- Email notifications
- Mobile app
- Advanced reporting
- Integrations marketplace
- Custom workflows

## API Reference

### Projects

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/projects` | List projects | Required |
| GET | `/api/v1/projects/{id}` | Get project | Required |
| POST | `/api/v1/projects` | Create project | Required |
| PUT | `/api/v1/projects/{id}` | Update project | Required |
| DELETE | `/api/v1/projects/{id}` | Delete project | Required |
| POST | `/api/v1/projects/{id}/members` | Add member | Required |
| DELETE | `/api/v1/projects/{id}/members/{memberId}` | Remove member | Required |

### Tasks

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/projects/{id}/tasks` | List tasks | Required |
| GET | `/api/v1/projects/{id}/tasks/{taskId}` | Get task | Required |
| POST | `/api/v1/projects/{id}/tasks` | Create task | Required |
| PUT | `/api/v1/projects/{id}/tasks/{taskId}` | Update task | Required |
| DELETE | `/api/v1/projects/{id}/tasks/{taskId}` | Delete task | Required |
| POST | `/api/v1/projects/{id}/tasks/{taskId}/move` | Move task | Required |
| POST | `/api/v1/projects/{id}/tasks/{taskId}/complete` | Complete task | Required |
| POST | `/api/v1/projects/{id}/tasks/{taskId}/comments` | Add comment | Required |
| POST | `/api/v1/projects/{id}/tasks/{taskId}/attachments` | Add attachment | Required |

### Boards

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/projects/{id}/board` | Get board | Required |
| POST | `/api/v1/projects/{id}/boards` | Create board | Required |
| PUT | `/api/v1/projects/{id}/boards/{boardId}` | Update board | Required |
| DELETE | `/api/v1/projects/{id}/boards/{boardId}` | Delete board | Required |
| POST | `/api/v1/projects/{id}/boards/{boardId}/columns` | Create column | Required |
| PUT | `/api/v1/projects/{id}/boards/{boardId}/columns/{columnId}` | Update column | Required |
| DELETE | `/api/v1/projects/{id}/boards/{boardId}/columns/{columnId}` | Delete column | Required |

### Time Entries

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/projects/{id}/time-entries` | List time entries | Required |
| POST | `/api/v1/projects/{id}/time-entries` | Create time entry | Required |
| PUT | `/api/v1/projects/{id}/time-entries/{entryId}` | Update time entry | Required |
| DELETE | `/api/v1/projects/{id}/time-entries/{entryId}` | Delete time entry | Required |

### Query Parameters (Index)

```
search -> Filter by name, description, client
status -> Filter by status
priority -> Filter by priority
project_manager_id -> Filter by project manager
overdue -> Filter by overdue projects
sort_by -> Sort field
sort_order -> Sort order (asc/desc)
per_page -> Pagination (default: 15)
page -> Page number
```

### Request Body (Create Project)

```json
{
  "name": "Website Redesign",
  "description": "Redesign company website",
  "status": "planning",
  "priority": "high",
  "start_date": "2026-03-15",
  "end_date": "2026-06-30",
  "budget": 500000,
  "currency": "BDT",
  "client_name": "Acme Corp",
  "client_email": "client@example.com",
  "client_phone": "+8801700000000",
  "project_manager_id": 1,
  "is_billable": true,
  "hourly_rate": 500,
  "estimated_hours": 200,
  "color": "#3b82f6",
  "member_ids": [2, 3, 4]
}
```

### Response Format

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Website Redesign",
    "description": "Redesign company website",
    "status": "planning",
    "priority": "high",
    "start_date": "2026-03-15",
    "end_date": "2026-06-30",
    "budget": 500000,
    "currency": "BDT",
    "progress_percentage": 0,
    "project_manager": {
      "id": 1,
      "name": "John Doe"
    },
    "members": [
      {
        "id": 1,
        "name": "John Doe",
        "pivot": {
          "role": "lead",
          "joined_at": "2026-03-05"
        }
      }
    ],
    "boards": [
      {
        "id": 1,
        "name": "Kanban Board",
        "type": "kanban",
        "is_default": true,
        "columns": [
          {
            "id": 1,
            "name": "To Do",
            "color": "#6b7280",
            "position": 1
          }
        ]
      }
    ]
  },
  "message": "Project created"
}
```

## Frontend API Integration

### Projects/Index.vue

```javascript
const fetchProjects = async (page = 1) => {
  const response = await get('/projects', { page, per_page: 15 })
  projects.value = response.data
  pagination.value = response.meta
}

const createProject = async () => {
  const data = {
    name: form.value.name,
    description: form.value.description,
    status: form.value.status,
    priority: form.value.priority,
    start_date: form.value.start_date,
    end_date: form.value.end_date,
    budget: form.value.budget,
    project_manager_id: form.value.project_manager_id,
    member_ids: form.value.member_ids,
  }
  
  await post('/projects', data)
  await fetchProjects()
}
```

### Projects/Board.vue

```javascript
const loadBoard = async () => {
  const response = await get(`/projects/${projectId}/board`)
  board.value = response.data.board
  columns.value = response.data.columns
  tasks.value = response.data.tasks
}

const handleTaskMoved = async (task, columnId, position) => {
  await post(`/projects/${projectId}/tasks/${task.id}/move`, {
    column_id: columnId,
    position: position
  })
  await loadBoard()
}

const handleAddTask = async (columnId) => {
  const taskData = {
    title: 'New Task',
    board_column_id: columnId,
  }
  await post(`/projects/${projectId}/tasks`, taskData)
  await loadBoard()
}
```

## Summary

### Backend Coverage
- ✅ Project model (status, priority, progress, budget, client info)
- ✅ Task model (Kanban support, subtasks, dependencies, story points)
- ✅ Board model (Kanban/Scrum/Custom)
- ✅ BoardColumn model (WIP limits, position management)
- ✅ TaskDependency model (blocks, is_blocked_by, relates_to)
- ✅ TimeEntry model (time tracking, billing)
- ✅ TaskChecklist model (checklists with items)
- ✅ TaskComment model (comments)
- ✅ TaskAttachment model (file attachments)
- ✅ ProjectService (create, update, members, progress)
- ✅ TaskService (create, update, move, complete, comments, attachments)
- ✅ Multi-tenancy support

### Frontend Coverage
- ✅ Projects/Dashboard.vue (metrics, focus project, my tasks)
- ✅ Projects/Index.vue (list, create, edit, delete, filters)
- ✅ Projects/Board.vue (Kanban board, drag-drop, WIP limits)
- ✅ Projects/Create.vue (create project form)
- ✅ Projects/Show.vue (project details, tasks, reports)
- ✅ Projects/Reports.vue (task completion, time logged, budget vs actual)
- ✅ Pagination support

### Integration
- ✅ HR Domain (employee assignment, time tracking for payroll, capacity planning)
- ✅ CRM Domain (lead/opportunity to project conversion)
- ✅ Accounting Domain (project billing, invoice creation, journal entries)
- ✅ Project progress auto-calculation
- ✅ WIP limit enforcement
- ✅ Task dependencies
- ✅ Story points support
- ✅ Multi-tenancy (company isolation)
- ✅ Bangladesh localization (BDT)

The Projects system provides **comprehensive project management** that follows modern Jira-like patterns with tight integration to HR, CRM, and accounting domains.


## Backend Architecture
- **Project Model** - Status (PLANNING → ACTIVE → ON_HOLD → COMPLETED/CANCELLED), priority, progress_percentage, budget, client info, hourly_rate, estimated_hours
- **Task Model** - Kanban support, subtasks, dependencies, story points, position in column, tags
- **Board Model** - Types (KANBAN, SCRUM, CUSTOM), default columns creation
- **BoardColumn Model** - WIP limits, position management, is_done_column
- **TaskDependency Model** - Types (BLOCKS, IS_BLOCKED_BY, RELATES_TO)
- **TimeEntry Model** - Time tracking, billing, amount calculation (hours × hourly_rate)
- **TaskChecklist Model** - Checklists with items, completion percentage
- **TaskComment Model** - Comments with is_internal flag
- **TaskAttachment Model** - File attachments with human-readable file size

## Services
- **ProjectService:** create, update, add/remove members, update progress
- **TaskService:** create, update, move task, complete task, add comments, add attachments

## Data Flows
- **Project Creation:** Create project → Create default board → Add members → Return project
- **Task Creation:** Validate access → Get default column → Calculate position → Create task → Update project progress
- **Task Movement:** Check WIP limit → Move to column → Update project progress
- **Task Completion:** Update status → Set actual_hours → Update project progress
- **Time Logging:** Create time entry → Calculate amount (hours × hourly_rate)

## Integration
- **HR Domain:** Employee assignment (assignee, reporter, project_manager), time tracking for payroll, capacity planning
- **CRM Domain:** Lead/Opportunity to Project conversion (copy details, set budget)
- **Accounting Domain:** Project billing → Invoice creation → Journal entry (DR: Accounts Receivable, CR: Service Revenue/Output VAT)

## Frontend Architecture
- **Projects/Dashboard.vue** - Metrics (total, active, completed, overdue), focus project, my tasks
- **Projects/Index.vue** - List, create, edit, delete, filters (status, priority, search, overdue)
- **Projects/Board.vue** - Kanban board, drag-drop, WIP limits, column management
- **Projects/Create.vue** - Create project form with all fields
- **Projects/Show.vue** - Project details, tasks, board, members, time entries, reports
- **Projects/Reports.vue** - Task completion, time logged, budget vs actual, burndown chart

## Comparison with Modern ERPs
- **Similar:** Project management, Kanban boards, task management, dependencies, subtasks, checklists, comments, attachments, time tracking, WIP limits, story points
- **Simpler:** No sprint planning, no Gantt charts, no resource planning, no email notifications, no mobile app
- **Unique:** Jira-like interface, Bangladesh localization (BDT), project billing integration, time tracking for payroll, capacity planning integration, WIP limit enforcement, project progress auto-calculation, multi-tenancy support
