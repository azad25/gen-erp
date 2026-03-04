# Real-World Implementation Examples

## Table of Contents
- [Project Management Complete Flow](#project-management-complete-flow)
- [Customer Management with Credit Tracking](#customer-management-with-credit-tracking)
- [Invoice Creation with Stock Deduction](#invoice-creation-with-stock-deduction)
- [CRM Lead to Opportunity Conversion](#crm-lead-to-opportunity-conversion)
- [Multi-Tenancy Implementation](#multi-tenancy-implementation)
- [Vue Component Integration](#vue-component-integration)
- [API Authentication Flow](#api-authentication-flow)
- [Background Job Processing](#background-job-processing)

---

## Project Management Complete Flow

### 1. Project Model (app/Domain/Project/Models/Project.php)

**Actual Model Implementation:**
```php
class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'description', 'status', 'priority',
        'start_date', 'end_date', 'budget', 'currency', 'client_name',
        'client_email', 'client_phone', 'project_manager_id',
        'progress_percentage', 'is_billable', 'hourly_rate',
        'estimated_hours', 'actual_hours', 'color', 'settings',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'progress_percentage' => 'integer',
        'is_billable' => 'boolean',
        'settings' => 'array',
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

