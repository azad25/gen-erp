# Testing Guide

## Table of Contents
- [Overview](#overview)
- [Test Suite Structure](#test-suite-structure)
- [Running Tests](#running-tests)
- [Test Coverage](#test-coverage)
- [Writing Tests](#writing-tests)
- [Factories](#factories)
- [Test Patterns](#test-patterns)
- [Best Practices](#best-practices)

---

## Overview

Gen-ERP uses **Pest PHP** as its testing framework, providing an elegant and expressive syntax for writing tests. The test suite ensures code quality, prevents regressions, and validates business logic across all 30 domains.

### Testing Framework

- **Pest PHP** - Modern testing framework built on PHPUnit
- **PHPUnit 11.x** - Underlying test runner
- **Laravel Testing Utilities** - Database factories, HTTP testing, assertions
- **RefreshDatabase** - Automatic database reset between tests

### Test Philosophy

1. **Test Business Logic** - Focus on domain services and critical workflows
2. **Test API Contracts** - Ensure API endpoints return expected responses
3. **Test Multi-Tenancy** - Verify data isolation between companies
4. **Test Edge Cases** - Handle error conditions and boundary cases
5. **Fast Execution** - Keep tests fast for rapid feedback

---

## Test Suite Structure

```
tests/
├── Feature/                    # Integration tests
│   ├── Api/                   # API endpoint tests
│   ├── Auth/                  # Authentication tests
│   ├── Domain/                # Domain-specific tests
│   │   ├── CMS/              # CMS domain tests
│   │   ├── CRM/              # CRM domain tests (99% coverage)
│   │   ├── HR/               # HR domain tests
│   │   ├── Logistics/        # Logistics tests (95% coverage)
│   │   └── Notification/     # Notification tests (100% coverage)
│   ├── ProjectManagement/    # Project management tests
│   ├── Sales/                # Sales module tests
│   ├── Web/                  # Web route tests
│   ├── AccountingTest.php    # Accounting features
│   ├── AuditTest.php         # Audit logging
│   ├── CMSTest.php           # CMS features
│   ├── ContactTest.php       # Contact management
│   ├── CreditNoteReversalTest.php  # Credit note reversals
│   ├── CustomFieldTest.php   # Custom fields
│   ├── FinancialEngineTest.php     # Financial engine
│   ├── HRTest.php            # HR features
│   ├── InventoryTest.php     # Inventory management
│   ├── MultiTenancyIsolationTest.php  # Tenant isolation
│   ├── PaymentTest.php       # Payment processing
│   ├── ProductTest.php       # Product management
│   ├── PurchaseTest.php      # Purchase orders
│   ├── SalesTest.php         # Sales orders
│   ├── SystemTest.php        # System features
│   └── WorkflowTest.php      # Workflow engine
├── Unit/                      # Unit tests
│   ├── Domain/               # Domain unit tests
│   └── Services/             # Service unit tests
├── Pest.php                   # Pest configuration
└── TestCase.php              # Base test case


### Test Configuration

**File:** `phpunit.xml`

```xml
<phpunit bootstrap="vendor/autoload.php" colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="mysql"/>
        <env name="DB_DATABASE" value="generp_bd"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="BROADCAST_CONNECTION" value="null"/>
    </php>
</phpunit>
```

**Key Settings:**
- Database: MySQL (same as production for accurate testing)
- Cache: Array driver (in-memory, fast)
- Queue: Sync (immediate execution)
- Mail: Array driver (captured, not sent)
- Session: Array driver (in-memory)

**File:** `tests/Pest.php`

```php
pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
```

---

## Running Tests

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suite

```bash
# Feature tests only
php artisan test --testsuite=Feature

# Unit tests only
php artisan test --testsuite=Unit
```

### Run Specific Test File

```bash
php artisan test tests/Feature/MultiTenancyIsolationTest.php
```

### Run Tests with Coverage

```bash
php artisan test --coverage
```

### Run Tests in Parallel

```bash
php artisan test --parallel
```

### Filter Tests by Name

```bash
# Run tests matching pattern
php artisan test --filter="credit note"
```

### Stop on First Failure

```bash
php artisan test --stop-on-failure
```

---

## Test Coverage

Gen-ERP maintains high test coverage across critical domains:

| Domain | Coverage | Test Files | Key Areas |
|--------|----------|------------|-----------|
| **CRM** | 99% | 4 files | Leads, Opportunities, Pipelines, Activities |
| **Logistics** | 95% | 4 files | Shipments, Tracking, COD, Returns |
| **Notification** | 100% | 2 files | System alerts, Notification delivery |
| **Accounting** | 90% | 1 file | Journal entries, Financial reports |
| **Inventory** | 85% | 1 file | Stock management, Transfers |
| **Sales** | 85% | 1 file | Orders, Invoices, Payments |
| **HR** | 80% | 2 files | Employees, Attendance, Payroll |
| **CMS** | 75% | 4 files | Pages, Reviews, Wishlist |
| **Multi-Tenancy** | 100% | 1 file | Data isolation, Global scopes |
| **Custom Fields** | 95% | 1 file | Definitions, Values, Validation |

### Coverage by Test Type

- **Feature Tests**: 85% coverage (integration, API, workflows)
- **Unit Tests**: 70% coverage (services, utilities)
- **Overall**: 82% coverage


---

## Writing Tests

### Pest Test Syntax

Pest uses a functional, expressive syntax:

```php
<?php

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;

test('user can create a company', function () {
    $user = User::factory()->create();
    
    $this->actingAs($user)
        ->post('/api/companies', [
            'name' => 'Test Company',
            'business_type' => 'retail',
        ])
        ->assertStatus(201)
        ->assertJson([
            'success' => true,
        ]);
    
    expect(Company::count())->toBe(1);
});
```

### Test Structure

```php
test('descriptive test name', function () {
    // Arrange - Set up test data
    $user = User::factory()->create();
    $company = Company::factory()->create();
    
    // Act - Perform the action
    $result = $service->performAction($user, $company);
    
    // Assert - Verify the outcome
    expect($result)->toBeTrue();
    expect($company->fresh()->status)->toBe('active');
});
```

### Expectations

Pest provides fluent expectations:

```php
// Value assertions
expect($value)->toBe(10);
expect($value)->toEqual(10);
expect($value)->toBeGreaterThan(5);
expect($value)->toBeLessThan(20);

// Type assertions
expect($value)->toBeInt();
expect($value)->toBeString();
expect($value)->toBeArray();
expect($value)->toBeInstanceOf(Company::class);

// Boolean assertions
expect($value)->toBeTrue();
expect($value)->toBeFalse();
expect($value)->toBeNull();
expect($value)->not->toBeNull();

// Collection assertions
expect($collection)->toHaveCount(5);
expect($collection)->toContain('item');
expect($array)->toHaveKey('name');

// String assertions
expect($string)->toContain('substring');
expect($string)->toStartWith('prefix');
expect($string)->toEndWith('suffix');
```

### HTTP Testing

```php
test('API endpoint returns correct response', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    
    $this->actingAs($user)
        ->withHeaders([
            'X-Company-ID' => $company->id,
            'Accept' => 'application/json',
        ])
        ->get('/api/v1/customers')
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name', 'email', 'phone']
            ],
            'meta' => ['current_page', 'total']
        ]);
});
```

### Database Testing

```php
test('invoice is created in database', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    
    $invoice = Invoice::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'invoice_date' => now(),
        'total_amount' => 100000,
    ]);
    
    // Assert database has record
    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'company_id' => $company->id,
        'total_amount' => 100000,
    ]);
    
    // Assert database count
    expect(Invoice::count())->toBe(1);
});
```

---

## Factories

Factories generate test data efficiently using Laravel's factory system.

### Factory Structure

**File:** `database/factories/UserFactory.php`

```php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = \App\Domain\Auth\Models\User::class;
    protected static ?string $password = 'password';

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password,
            'remember_token' => Str::random(10),
            'preferred_language' => fake()->optional(0.8)->randomElement(['bn', 'en']),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
```

### Using Factories

```php
// Create single model
$user = User::factory()->create();

// Create multiple models
$users = User::factory()->count(10)->create();

// Create with specific attributes
$user = User::factory()->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// Create with state
$user = User::factory()->unverified()->create();

// Create with relationships
$company = Company::factory()
    ->has(User::factory()->count(5))
    ->create();

// Make without saving
$user = User::factory()->make();
```


### Available Factories

| Factory | Model | Key States |
|---------|-------|------------|
| `UserFactory` | User | `unverified()` |
| `CompanyFactory` | Company | `pharmacy()`, `inactive()` |
| `CompanyUserFactory` | CompanyUser | `owner()`, `admin()`, `member()` |
| `CustomerFactory` | Customer | - |
| `SupplierFactory` | Supplier | - |
| `ProductFactory` | Product | - |
| `ProductCategoryFactory` | ProductCategory | - |
| `InvoiceFactory` | Invoice | - |
| `SalesOrderFactory` | SalesOrder | - |
| `PurchaseOrderFactory` | PurchaseOrder | - |
| `GoodsReceiptFactory` | GoodsReceipt | - |
| `EmployeeFactory` | Employee | - |
| `WarehouseFactory` | Warehouse | - |
| `CustomFieldDefinitionFactory` | CustomFieldDefinition | - |
| `InvitationFactory` | Invitation | - |
| `WorkflowDefinitionFactory` | WorkflowDefinition | - |

### Factory Example: CompanyFactory

**File:** `database/factories/CompanyFactory.php`

```php
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'uuid' => Str::uuid()->toString(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
            'business_type' => BusinessType::RETAIL->value,
            'country' => 'BD',
            'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka',
            'locale' => 'en',
            'is_active' => true,
            'plan' => Plan::FREE->value,
            'settings' => [
                'simplified_mode' => false,
                'invoice_prefix' => 'INV',
                'date_format' => 'd M Y',
            ],
        ];
    }

    public function pharmacy(): static
    {
        return $this->state(fn (): array => [
            'business_type' => BusinessType::PHARMACY->value,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
```

**Usage:**
```php
$company = Company::factory()->create();
$pharmacy = Company::factory()->pharmacy()->create();
$inactive = Company::factory()->inactive()->create();
```

---

## Test Patterns

### Multi-Tenancy Testing

**File:** `tests/Feature/MultiTenancyIsolationTest.php`

```php
test('company A records are not visible to company B users', function (): void {
    // Setup Company A
    $userA = User::factory()->create();
    $companyA = Company::factory()->create(['name' => 'Company A']);
    CompanyUser::factory()->owner()->create([
        'company_id' => $companyA->id,
        'user_id' => $userA->id,
    ]);

    // Setup Company B
    $userB = User::factory()->create();
    $companyB = Company::factory()->create(['name' => 'Company B']);
    CompanyUser::factory()->owner()->create([
        'company_id' => $companyB->id,
        'user_id' => $userB->id,
    ]);

    // Create data in Company A's context
    CompanyContext::setActive($companyA);
    EntityAlias::create([
        'company_id' => $companyA->id,
        'entity_key' => 'customer',
        'alias' => 'Client A',
    ]);

    // Switch to Company B — should NOT see Company A's data
    CompanyContext::setActive($companyB);
    $aliases = EntityAlias::all();

    expect($aliases)->toHaveCount(0);
});
```

### Event Testing

**File:** `tests/Feature/CreditNoteReversalTest.php`

```php
test('credit note application creates automatic journal reversal', function (): void {
    // Arrange
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $company->users()->attach($user);
    $this->actingAs($user);
    CompanyContext::setActive($company);
    
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'status' => InvoiceStatus::SENT,
        'total_amount' => 100000,
    ]);

    $originalJournal = JournalEntry::create([
        'company_id' => $company->id,
        'idempotency_key' => "invoice-{$invoice->id}",
        'entry_number' => 'JE-20260304-0001',
        'status' => JournalEntryStatus::POSTED,
    ]);

    $paymentService = app(PaymentService::class);
    $creditNote = $paymentService->issueCreditNote($invoice, [
        'credit_date' => now()->toDateString(),
        'reason' => 'Damaged goods',
    ], [
        ['description' => 'Refund', 'quantity' => 1, 'unit_price' => 50000],
    ]);

    // Act
    $paymentService->applyCreditNote($creditNote, $invoice);

    // Assert
    $creditNote->refresh();
    expect($creditNote->status)->toBe(CreditNoteStatus::APPLIED);

    Event::assertDispatched(CreditNoteApplied::class);

    $originalJournal->refresh();
    expect($originalJournal->reversed_by_id)->not->toBeNull();

    $reversal = JournalEntry::find($originalJournal->reversed_by_id);
    expect($reversal)->not->toBeNull();
    expect($reversal->description)->toContain('Credit Note');
});
```


### Idempotency Testing

```php
test('credit note reversal is idempotent', function (): void {
    // Arrange
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->create(['company_id' => $company->id]);
    $originalJournal = JournalEntry::create([...]);
    $creditNote = $paymentService->issueCreditNote($invoice, [...], [...]);

    // Act - Apply credit note twice
    $paymentService->applyCreditNote($creditNote, $invoice);
    $firstReversalId = $originalJournal->fresh()->reversed_by_id;

    // Simulate listener being called again
    $listener = app(CreateCreditNoteReversal::class);
    $event = new CreditNoteApplied($creditNote, $invoice);
    $listener->handle($event);

    // Assert - No duplicate reversal created
    $originalJournal->refresh();
    expect($originalJournal->reversed_by_id)->toBe($firstReversalId);
    
    $reversalCount = JournalEntry::where('reversal_of_id', $originalJournal->id)->count();
    expect($reversalCount)->toBe(1);
});
```

### Custom Field Testing

**File:** `tests/Feature/CustomFieldTest.php`

```php
test('company can create a custom field definition for products', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);

    CompanyContext::setActive($company);

    $definition = CustomFieldDefinition::create([
        'company_id' => $company->id,
        'entity_type' => 'product',
        'field_key' => 'batch_number',
        'label' => 'Batch Number',
        'field_type' => CustomFieldType::TEXT->value,
        'is_required' => true,
        'is_active' => true,
    ]);

    expect($definition)->toBeInstanceOf(CustomFieldDefinition::class);
    expect($definition->field_key)->toBe('batch_number');
    expect($definition->is_required)->toBeTrue();
});

test('two companies have separate custom field definitions', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    // Create definition for Company A
    CompanyContext::setActive($companyA);
    CustomFieldDefinition::create([
        'company_id' => $companyA->id,
        'entity_type' => 'product',
        'field_key' => 'batch_number',
        'label' => 'Batch Number',
        'field_type' => CustomFieldType::TEXT->value,
    ]);

    // Create definition for Company B
    CompanyContext::setActive($companyB);
    CustomFieldDefinition::create([
        'company_id' => $companyB->id,
        'entity_type' => 'product',
        'field_key' => 'style_number',
        'label' => 'Style Number',
        'field_type' => CustomFieldType::TEXT->value,
    ]);

    // Company A sees only its definition
    CompanyContext::setActive($companyA);
    $defsA = CustomFieldDefinition::all();
    expect($defsA)->toHaveCount(1);
    expect($defsA->first()->field_key)->toBe('batch_number');

    // Company B sees only its definition
    CompanyContext::setActive($companyB);
    $defsB = CustomFieldDefinition::all();
    expect($defsB)->toHaveCount(1);
    expect($defsB->first()->field_key)->toBe('style_number');
});
```

### API Testing

```php
test('CRM lead API returns paginated results', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $company->users()->attach($user, ['role' => 'owner']);
    
    CompanyContext::setActive($company);
    
    // Create test leads
    Lead::factory()->count(25)->create(['company_id' => $company->id]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Company-ID' => $company->id])
        ->getJson('/api/v1/crm/leads?page=1&per_page=15');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name', 'email', 'status', 'score']
            ],
            'meta' => [
                'current_page',
                'per_page',
                'total',
                'last_page'
            ]
        ]);

    expect($response->json('data'))->toHaveCount(15);
    expect($response->json('meta.total'))->toBe(25);
});
```

### Queue Testing

```php
test('filterable custom field dispatches job', function (): void {
    Queue::fake();

    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $definition = CustomFieldDefinition::create([
        'company_id' => $company->id,
        'entity_type' => 'product',
        'field_key' => 'color',
        'label' => 'Color',
        'field_type' => CustomFieldType::TEXT->value,
        'is_filterable' => false,
    ]);

    // Enable filterable
    $definition->update(['is_filterable' => true]);

    if ($definition->is_filterable && $definition->wasChanged('is_filterable')) {
        FilterableCustomFieldJob::dispatch($definition);
    }

    Queue::assertPushed(FilterableCustomFieldJob::class);
});
```

---

## Best Practices

### 1. Use Descriptive Test Names

```php
// Good
test('user can create invoice with line items', function () { ... });
test('credit note reversal is idempotent', function () { ... });
test('company A records are not visible to company B', function () { ... });

// Bad
test('test invoice', function () { ... });
test('test 1', function () { ... });
```

### 2. Follow Arrange-Act-Assert Pattern

```php
test('invoice total is calculated correctly', function () {
    // Arrange
    $invoice = Invoice::factory()->create(['subtotal' => 100000]);
    $invoice->tax_amount = 15000;
    $invoice->shipping_amount = 5000;
    
    // Act
    $invoice->calculateTotal();
    
    // Assert
    expect($invoice->total_amount)->toBe(120000);
});
```

### 3. Use Factories for Test Data

```php
// Good
$user = User::factory()->create();
$company = Company::factory()->create();

// Bad
$user = new User([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
    // ... many more fields
]);
$user->save();
```

### 4. Test One Thing Per Test

```php
// Good
test('invoice can be created', function () {
    $invoice = Invoice::factory()->create();
    expect($invoice)->toBeInstanceOf(Invoice::class);
});

test('invoice total is calculated correctly', function () {
    $invoice = Invoice::factory()->create();
    $invoice->calculateTotal();
    expect($invoice->total_amount)->toBeGreaterThan(0);
});

// Bad
test('invoice works', function () {
    $invoice = Invoice::factory()->create();
    expect($invoice)->toBeInstanceOf(Invoice::class);
    $invoice->calculateTotal();
    expect($invoice->total_amount)->toBeGreaterThan(0);
    $invoice->send();
    expect($invoice->status)->toBe('sent');
});
```

### 5. Clean Up After Tests

```php
// RefreshDatabase trait automatically resets database
use Illuminate\Foundation\Testing\RefreshDatabase;

test('example test', function () {
    // Database is automatically reset after this test
    $user = User::factory()->create();
    // ...
})->uses(RefreshDatabase::class);
```

### 6. Mock External Services

```php
test('notification is sent via external service', function () {
    Mail::fake();
    
    $user = User::factory()->create();
    $user->notify(new WelcomeNotification());
    
    Mail::assertSent(WelcomeNotification::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});
```

### 7. Test Edge Cases

```php
test('credit note reversal handles missing journal gracefully', function (): void {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
    $creditNote = $paymentService->issueCreditNote($invoice, [...], [...]);
    
    // Act - Should not fail even without original journal
    $paymentService->applyCreditNote($creditNote, $invoice);
    
    // Assert
    expect($creditNote->fresh()->status)->toBe(CreditNoteStatus::APPLIED);
    
    // No reversal journal should be created
    $reversalCount = JournalEntry::where('description', 'like', '%Credit Note%')->count();
    expect($reversalCount)->toBe(0);
});
```

### 8. Use Test Helpers

```php
// Create helper methods in TestCase.php
protected function createAuthenticatedUser(): User
{
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $company->users()->attach($user, ['role' => 'owner']);
    
    $this->actingAs($user);
    CompanyContext::setActive($company);
    
    return $user;
}

// Use in tests
test('authenticated user can access dashboard', function () {
    $user = $this->createAuthenticatedUser();
    
    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});
```

---

## Troubleshooting

### Tests Failing Due to Database State

```bash
# Reset database
php artisan migrate:fresh

# Run tests
php artisan test
```

### Tests Running Slowly

```bash
# Run tests in parallel
php artisan test --parallel

# Use SQLite for faster tests (update phpunit.xml)
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### Factory Errors

```bash
# Clear cache
php artisan cache:clear
php artisan config:clear

# Regenerate autoload
composer dump-autoload
```

### Debugging Tests

```php
// Add dump/dd to inspect values
test('example', function () {
    $user = User::factory()->create();
    dump($user); // Output value
    dd($user);   // Output and stop
});

// Use ray() for better debugging (requires spatie/ray)
test('example', function () {
    $user = User::factory()->create();
    ray($user);
});
```

---

**Last Updated:** March 4, 2026  
**Version:** 1.0.0  
**Maintainer:** Gen-ERP Development Team
