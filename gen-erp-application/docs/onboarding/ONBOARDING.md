# Developer Onboarding Guide

## Table of Contents
- [Welcome](#welcome)
- [Before You Start](#before-you-start)
- [Development Environment Setup](#development-environment-setup)
- [Understanding the Codebase](#understanding-the-codebase)
- [Common Development Tasks](#common-development-tasks)
- [Git Workflow](#git-workflow)
- [Code Standards](#code-standards)
- [Testing Guidelines](#testing-guidelines)
- [Debugging Tips](#debugging-tips)
- [Getting Help](#getting-help)

---

## Welcome

Welcome to the Gen-ERP development team! This guide will help you get up and running quickly and understand our development practices.

### What You'll Learn

- How to set up your local development environment
- Understanding the codebase architecture
- Common development workflows
- Code standards and best practices
- How to contribute effectively

### Time to Productivity

- **Day 1:** Environment setup, codebase overview
- **Week 1:** First bug fix or small feature
- **Month 1:** Comfortable with major modules
- **Month 3:** Contributing to architecture decisions

---

## Before You Start

### Required Knowledge

**Essential:**
- PHP 8.2+ and object-oriented programming
- Laravel 12 framework fundamentals
- MySQL database and SQL
- Git version control
- HTML, CSS, JavaScript basics

**Recommended:**
- Vue.js 3 and Composition API
- Inertia.js for SPA development
- TailwindCSS utility-first CSS
- Pest PHP testing framework
- Domain-Driven Design (DDD) principles

### Recommended Reading

**Before starting:**
1. [Laravel 12 Documentation](https://laravel.com/docs/12.x)
2. [Vue 3 Documentation](https://vuejs.org/guide/introduction.html)
3. [Inertia.js Documentation](https://inertiajs.com/)
4. [Pest PHP Documentation](https://pestphp.com/)

**First week:**
1. Gen-ERP Architecture Documentation (`docs/developer/ARCHITECTURE.md`)
2. Application Flow Documentation (`docs/developer/APPLICATION_FLOW.md`)
3. Database Schema Documentation (`docs/developer/DATABASE.md`)

### Tools You'll Need

**Required:**
- **IDE:** PhpStorm, VS Code, or similar
- **Terminal:** iTerm2 (Mac), Windows Terminal, or similar
- **Database Client:** TablePlus, DBeaver, or MySQL Workbench
- **API Client:** Postman or Insomnia
- **Git Client:** Command line or GitKraken

**Recommended:**
- **Browser:** Chrome with Vue DevTools extension
- **Docker:** For consistent development environments
- **Redis Client:** RedisInsight or Medis

---

## Development Environment Setup

### Step 1: System Requirements

**macOS/Linux:**
```bash
# Check PHP version
php -v  # Should be 8.2 or higher

# Check Composer
composer --version

# Check Node.js
node -v  # Should be 20.x LTS

# Check MySQL
mysql --version  # Should be 8.0+
```

**Install missing dependencies:**
```bash
# macOS (using Homebrew)
brew install php@8.2 composer node mysql redis

# Ubuntu/Debian
sudo apt install php8.2 php8.2-fpm composer nodejs mysql-server redis-server
```

### Step 2: Clone Repository

```bash
# Clone the repository
git clone https://github.com/your-org/gen-erp.git
cd gen-erp

# Create your feature branch
git checkout -b feature/your-name-setup
```

### Step 3: Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### Step 4: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=generp_dev
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Step 5: Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE generp_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed
```

### Step 6: Build Frontend Assets

```bash
# Development build with hot reload
npm run dev

# Or production build
npm run build
```

### Step 7: Start Development Server

```bash
# Option 1: Use Laravel's built-in server
php artisan serve

# Option 2: Use the dev script (recommended)
composer dev
# This starts: Laravel server, queue worker, Pail logs, and Vite

# Access application at: http://localhost:8000
```

### Step 8: Verify Installation

```bash
# Run tests to verify everything works
php artisan test

# Check code style
./vendor/bin/pint --test
```

### Troubleshooting Setup

**Issue: "Class not found" errors**
```bash
composer dump-autoload
php artisan clear-compiled
php artisan config:clear
```

**Issue: Permission denied on storage**
```bash
chmod -R 775 storage bootstrap/cache
```

**Issue: NPM build fails**
```bash
rm -rf node_modules package-lock.json
npm install
```

---

## Understanding the Codebase

### Project Structure Overview

```
gen-erp/
├── app/                          # Application code
│   ├── Domain/                   # 30 business domains (DDD)
│   │   ├── Auth/                # Authentication domain
│   │   ├── Invoice/             # Invoice domain
│   │   ├── CRM/                 # CRM domain
│   │   └── ...                  # Other domains
│   ├── Http/                    # HTTP layer
│   │   ├── Controllers/         # Controllers
│   │   ├── Middleware/          # Middleware
│   │   └── Requests/            # Form requests
│   ├── Services/                # Application services
│   ├── Events/                  # Application events
│   ├── Jobs/                    # Background jobs
│   ├── Listeners/               # Event listeners
│   └── Observers/               # Model observers
├── config/                      # Configuration files
├── database/                    # Database files
│   ├── factories/              # Model factories
│   ├── migrations/             # Database migrations
│   └── seeders/                # Database seeders
├── resources/                   # Frontend resources
│   ├── js/                     # Vue.js application
│   │   ├── Components/         # Vue components
│   │   ├── Pages/              # Inertia pages
│   │   ├── Composables/        # Composition functions
│   │   ├── Services/           # API services
│   │   └── Stores/             # Pinia stores
│   ├── css/                    # Stylesheets
│   └── views/                  # Blade templates
├── routes/                      # Route definitions
│   ├── web.php                 # Web routes
│   ├── api.php                 # API routes
│   └── channels.php            # Broadcast channels
├── tests/                       # Test suite
│   ├── Feature/                # Feature tests
│   └── Unit/                   # Unit tests
└── docs/                        # Documentation
```

### Key Architectural Concepts

**1. Domain-Driven Design (DDD):**
- Business logic organized into 30 domains
- Each domain has Models, Services, Events, Listeners
- Clear boundaries between domains

**2. Multi-Tenancy:**
- Data isolated by `company_id`
- Global scopes enforce tenant isolation
- `CompanyContext` service manages active company

**3. CQRS Pattern (Invoice Domain):**
- Separate read and write operations
- Commands for writes, Queries for reads
- Event sourcing for audit trail

**4. Service Layer:**
- Business logic in service classes
- Controllers are thin, services are fat
- Services are testable and reusable

### Important Files to Know

| File | Purpose |
|------|---------|
| `app/Domain/*/Models/*.php` | Eloquent models |
| `app/Domain/*/Services/*.php` | Business logic |
| `app/Http/Controllers/Api/V1/*.php` | API controllers |
| `resources/js/Pages/**/*.vue` | Frontend pages |
| `resources/js/Components/**/*.vue` | Reusable components |
| `routes/web.php` | Web routes |
| `routes/api.php` | API routes |
| `config/*.php` | Configuration |


---

## Common Development Tasks

### Task 1: Add a New API Endpoint

**Example: Add endpoint to get customer statistics**

1. **Create the controller method:**
```php
// app/Http/Controllers/Api/V1/CustomerController.php
public function statistics(Request $request): JsonResponse
{
    $stats = $this->customerService->getStatistics(
        companyId: CompanyContext::getId(),
        startDate: $request->input('start_date'),
        endDate: $request->input('end_date')
    );
    
    return response()->json([
        'success' => true,
        'data' => $stats
    ]);
}
```

2. **Add the route:**
```php
// routes/api.php
Route::get('/customers/statistics', [CustomerController::class, 'statistics']);
```

3. **Add service method:**
```php
// app/Domain/Customer/Services/CustomerService.php
public function getStatistics(int $companyId, ?string $startDate, ?string $endDate): array
{
    return [
        'total_customers' => Customer::where('company_id', $companyId)->count(),
        'new_this_month' => Customer::where('company_id', $companyId)
            ->whereMonth('created_at', now()->month)
            ->count(),
        // ... more stats
    ];
}
```

4. **Write tests:**
```php
// tests/Feature/Api/CustomerApiTest.php
test('can get customer statistics', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $company->users()->attach($user);
    
    Customer::factory()->count(10)->create(['company_id' => $company->id]);
    
    $response = $this->actingAs($user)
        ->withHeaders(['X-Company-ID' => $company->id])
        ->getJson('/api/v1/customers/statistics');
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['total_customers', 'new_this_month']
        ]);
});
```

### Task 2: Add a New Vue Component

**Example: Create a customer card component**

1. **Create the component:**
```vue
<!-- resources/js/Components/Customer/CustomerCard.vue -->
<template>
  <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold">{{ customer.name }}</h3>
      <span :class="statusClass">{{ customer.status }}</span>
    </div>
    <div class="space-y-2 text-sm text-gray-600">
      <p>Email: {{ customer.email }}</p>
      <p>Phone: {{ customer.phone }}</p>
      <p>Total Orders: {{ customer.orders_count }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  customer: {
    type: Object,
    required: true
  }
})

const statusClass = computed(() => {
  return props.customer.status === 'active'
    ? 'px-2 py-1 text-xs font-semibold rounded-full bg-success/10 text-success'
    : 'px-2 py-1 text-xs font-semibold rounded-full bg-danger/10 text-danger'
})
</script>
```

2. **Use the component:**
```vue
<!-- resources/js/Pages/Customer/Index.vue -->
<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <CustomerCard
      v-for="customer in customers"
      :key="customer.id"
      :customer="customer"
    />
  </div>
</template>

<script setup>
import CustomerCard from '@/Components/Customer/CustomerCard.vue'

defineProps({
  customers: Array
})
</script>
```

### Task 3: Add a Database Migration

**Example: Add a column to customers table**

1. **Create migration:**
```bash
php artisan make:migration add_credit_limit_to_customers_table
```

2. **Write migration:**
```php
// database/migrations/2026_03_04_000000_add_credit_limit_to_customers_table.php
public function up(): void
{
    Schema::table('customers', function (Blueprint $table) {
        $table->integer('credit_limit')->default(0)->after('phone');
    });
}

public function down(): void
{
    Schema::table('customers', function (Blueprint $table) {
        $table->dropColumn('credit_limit');
    });
}
```

3. **Run migration:**
```bash
php artisan migrate
```

4. **Update model:**
```php
// app/Domain/Customer/Models/Customer.php
protected $fillable = [
    'name',
    'email',
    'phone',
    'credit_limit', // Add this
];

protected $casts = [
    'credit_limit' => 'integer',
];
```

### Task 4: Add a Background Job

**Example: Send welcome email to new customer**

1. **Create job:**
```bash
php artisan make:job SendWelcomeEmail
```

2. **Implement job:**
```php
// app/Jobs/SendWelcomeEmail.php
class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Customer $customer
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        Mail::to($this->customer->email)->send(
            new WelcomeEmail($this->customer)
        );
    }
}
```

3. **Dispatch job:**
```php
// In CustomerService or Controller
SendWelcomeEmail::dispatch($customer);
```

4. **Test job:**
```php
test('welcome email is sent to new customer', function () {
    Queue::fake();
    
    $customer = Customer::factory()->create();
    
    SendWelcomeEmail::dispatch($customer);
    
    Queue::assertPushed(SendWelcomeEmail::class);
});
```

### Task 5: Add a Custom Artisan Command

**Example: Generate monthly reports**

1. **Create command:**
```bash
php artisan make:command GenerateMonthlyReports
```

2. **Implement command:**
```php
// app/Console/Commands/GenerateMonthlyReports.php
class GenerateMonthlyReports extends Command
{
    protected $signature = 'reports:generate-monthly {--company=}';
    protected $description = 'Generate monthly reports for all companies';

    public function handle(ReportService $reportService): int
    {
        $companyId = $this->option('company');
        
        $this->info('Generating monthly reports...');
        
        $companies = $companyId 
            ? Company::where('id', $companyId)->get()
            : Company::where('is_active', true)->get();
        
        $bar = $this->output->createProgressBar($companies->count());
        
        foreach ($companies as $company) {
            $reportService->generateMonthlyReport($company);
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('Reports generated successfully!');
        
        return Command::SUCCESS;
    }
}
```

3. **Run command:**
```bash
php artisan reports:generate-monthly
php artisan reports:generate-monthly --company=1
```

---

## Git Workflow

### Branch Naming Convention

```
feature/short-description    # New features
bugfix/issue-number         # Bug fixes
hotfix/critical-issue       # Production hotfixes
refactor/component-name     # Code refactoring
docs/section-name           # Documentation updates
```

**Examples:**
- `feature/customer-credit-limit`
- `bugfix/invoice-calculation`
- `hotfix/payment-gateway-error`

### Development Workflow

**1. Start new work:**
```bash
# Update main branch
git checkout main
git pull origin main

# Create feature branch
git checkout -b feature/customer-statistics

# Make changes...
```

**2. Commit changes:**
```bash
# Stage changes
git add .

# Commit with descriptive message
git commit -m "feat: add customer statistics endpoint

- Add statistics method to CustomerController
- Add getStatistics method to CustomerService
- Add API route for /customers/statistics
- Add tests for statistics endpoint"
```

**3. Push and create PR:**
```bash
# Push branch
git push origin feature/customer-statistics

# Create Pull Request on GitHub/GitLab
```

### Commit Message Format

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Examples:**
```
feat(customer): add credit limit field

- Add credit_limit column to customers table
- Update Customer model with credit_limit attribute
- Add validation for credit limit

Closes #123
```

```
fix(invoice): correct tax calculation

Tax was being calculated on discounted amount instead of subtotal.
Now calculates tax on subtotal before applying discount.

Fixes #456
```

### Pull Request Checklist

Before submitting a PR, ensure:

- [ ] Code follows style guidelines (run `./vendor/bin/pint`)
- [ ] All tests pass (`php artisan test`)
- [ ] New features have tests
- [ ] Documentation is updated
- [ ] No console errors or warnings
- [ ] Database migrations are reversible
- [ ] API changes are backward compatible
- [ ] PR description explains what and why

---

## Code Standards

### PHP Coding Standards

We follow **PSR-12** coding standards with Laravel conventions. Use Laravel Pint for automatic formatting.

**Run Pint before committing:**
```bash
# Fix all files
./vendor/bin/pint

# Check without fixing
./vendor/bin/pint --test

# Fix specific file
./vendor/bin/pint app/Domain/Customer/Services/CustomerService.php
```

**Key conventions:**

**1. Class naming:**
```php
// Models: Singular, PascalCase
class Customer extends Model {}

// Services: Descriptive, ends with Service
class CustomerService {}

// Controllers: Plural, ends with Controller
class CustomersController extends Controller {}

// Requests: Descriptive, ends with Request
class StoreCustomerRequest extends FormRequest {}
```

**2. Method naming:**
```php
// Use descriptive, camelCase names
public function getActiveCustomers(): Collection {}
public function calculateTotalRevenue(int $customerId): float {}
public function sendWelcomeEmail(Customer $customer): void {}

// Boolean methods start with is/has/can
public function isActive(): bool {}
public function hasOrders(): bool {}
public function canPlaceOrder(): bool {}
```

**3. Type hints and return types:**
```php
// Always use type hints and return types
public function createCustomer(array $data): Customer
{
    return Customer::create($data);
}

// Use nullable types when appropriate
public function findCustomer(?int $id): ?Customer
{
    return $id ? Customer::find($id) : null;
}

// Use union types for multiple types
public function getCustomer(int|string $identifier): Customer
{
    return is_int($identifier)
        ? Customer::findOrFail($identifier)
        : Customer::where('code', $identifier)->firstOrFail();
}
```

**4. Dependency injection:**
```php
// Inject dependencies in constructor
class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
        private readonly NotificationService $notificationService
    ) {}
    
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->create($request->validated());
        $this->notificationService->sendWelcomeEmail($customer);
        
        return response()->json(['data' => $customer], 201);
    }
}
```

**5. Array syntax:**
```php
// Use short array syntax
$customers = ['John', 'Jane', 'Bob'];

// Use trailing commas in multi-line arrays
$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '1234567890',
];
```

### Vue.js Conventions

**1. Component naming:**
```vue
<!-- Use PascalCase for component files -->
<!-- CustomerCard.vue -->
<template>
  <div class="customer-card">
    <!-- Use kebab-case in templates -->
    <customer-avatar :customer="customer" />
  </div>
</template>

<script setup>
// Use PascalCase for imports
import CustomerAvatar from '@/Components/Customer/CustomerAvatar.vue'
</script>
```

**2. Props and emits:**
```vue
<script setup>
// Define props with types and defaults
const props = defineProps({
  customer: {
    type: Object,
    required: true
  },
  showActions: {
    type: Boolean,
    default: true
  }
})

// Define emits
const emit = defineEmits(['update', 'delete'])

// Use emits
const handleUpdate = () => {
  emit('update', props.customer.id)
}
</script>
```

**3. Composables:**
```javascript
// Use "use" prefix for composables
// composables/useCustomer.js
export function useCustomer() {
  const customers = ref([])
  const loading = ref(false)
  
  const fetchCustomers = async () => {
    loading.value = true
    try {
      const response = await axios.get('/api/v1/customers')
      customers.value = response.data.data
    } finally {
      loading.value = false
    }
  }
  
  return {
    customers,
    loading,
    fetchCustomers
  }
}
```

**4. Template organization:**
```vue
<template>
  <!-- 1. Structural elements first -->
  <div class="container">
    <!-- 2. Conditional rendering -->
    <div v-if="loading">Loading...</div>
    
    <!-- 3. List rendering -->
    <div v-else>
      <customer-card
        v-for="customer in customers"
        :key="customer.id"
        :customer="customer"
        @update="handleUpdate"
      />
    </div>
  </div>
</template>
```

### Naming Conventions

**Database:**
```
Tables: plural, snake_case          → customers, sales_orders
Columns: singular, snake_case       → customer_name, created_at
Foreign keys: singular_id           → customer_id, company_id
Pivot tables: alphabetical order    → company_user, not user_company
```

**Files:**
```
Models: Singular, PascalCase        → Customer.php
Controllers: Plural, PascalCase     → CustomersController.php
Services: Descriptive, PascalCase   → CustomerService.php
Migrations: descriptive_snake_case  → create_customers_table.php
Vue components: PascalCase          → CustomerCard.vue
Composables: camelCase with use     → useCustomer.js
```

**Variables:**
```php
// PHP: camelCase
$customerName = 'John Doe';
$totalRevenue = 1000.00;

// JavaScript: camelCase
const customerName = 'John Doe'
const totalRevenue = 1000.00

// Constants: UPPER_SNAKE_CASE
const MAX_UPLOAD_SIZE = 10485760;
```

---

## Testing Guidelines

### When to Write Tests

**Always write tests for:**
- New features and functionality
- Bug fixes (write test that reproduces bug first)
- Business logic in services
- API endpoints
- Database queries and relationships
- Event listeners and jobs

**Optional tests for:**
- Simple CRUD operations
- Straightforward getters/setters
- UI components (unless complex logic)

### Test Structure

We use **Pest PHP** for testing. Tests are organized by type:

```
tests/
├── Feature/              # Integration tests
│   ├── Api/             # API endpoint tests
│   ├── Auth/            # Authentication tests
│   └── Domain/          # Domain feature tests
└── Unit/                # Unit tests
    └── Services/        # Service class tests
```

**Basic test structure:**
```php
<?php

use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Services\CustomerService;

// Feature test example
test('can create customer via API', function () {
    // Arrange: Set up test data
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $company->users()->attach($user);
    
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '1234567890',
    ];
    
    // Act: Perform the action
    $response = $this->actingAs($user)
        ->withHeaders(['X-Company-ID' => $company->id])
        ->postJson('/api/v1/customers', $data);
    
    // Assert: Verify the results
    $response->assertStatus(201)
        ->assertJsonStructure(['success', 'data' => ['id', 'name', 'email']]);
    
    $this->assertDatabaseHas('customers', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'company_id' => $company->id,
    ]);
});

// Unit test example
test('customer service calculates total revenue correctly', function () {
    $customer = Customer::factory()->create();
    
    // Create orders with known amounts
    SalesOrder::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 1000.00,
    ]);
    SalesOrder::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 500.00,
    ]);
    
    $service = app(CustomerService::class);
    $revenue = $service->calculateTotalRevenue($customer->id);
    
    expect($revenue)->toBe(1500.00);
});
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Api/CustomerApiTest.php

# Run tests with coverage
php artisan test --coverage

# Run tests in parallel (faster)
php artisan test --parallel

# Run specific test by name
php artisan test --filter="can create customer"

# Run tests for specific domain
php artisan test tests/Feature/Domain/Customer/
```

### Coverage Requirements

**Minimum coverage targets:**
- Critical business logic: 100%
- Services: 90%+
- API endpoints: 85%+
- Models: 70%+
- Overall: 80%+

**Check coverage:**
```bash
php artisan test --coverage --min=80
```

### Test Patterns

**1. Multi-tenancy testing:**
```php
test('customer is scoped to company', function () {
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();
    
    $customer1 = Customer::factory()->create(['company_id' => $company1->id]);
    $customer2 = Customer::factory()->create(['company_id' => $company2->id]);
    
    // User from company1 should only see their customers
    $user = User::factory()->create();
    $company1->users()->attach($user);
    
    $response = $this->actingAs($user)
        ->withHeaders(['X-Company-ID' => $company1->id])
        ->getJson('/api/v1/customers');
    
    $response->assertJsonCount(1, 'data')
        ->assertJsonFragment(['id' => $customer1->id])
        ->assertJsonMissing(['id' => $customer2->id]);
});
```

**2. Event testing:**
```php
test('customer created event is dispatched', function () {
    Event::fake([CustomerCreated::class]);
    
    $customer = Customer::factory()->create();
    
    Event::assertDispatched(CustomerCreated::class, function ($event) use ($customer) {
        return $event->customer->id === $customer->id;
    });
});
```

**3. Job testing:**
```php
test('welcome email job is queued', function () {
    Queue::fake();
    
    $customer = Customer::factory()->create();
    
    SendWelcomeEmail::dispatch($customer);
    
    Queue::assertPushed(SendWelcomeEmail::class, function ($job) use ($customer) {
        return $job->customer->id === $customer->id;
    });
});
```

---

## Debugging Tips

### Common Issues

**Issue 1: "Class not found" errors**
```bash
# Solution: Clear and rebuild autoload
composer dump-autoload
php artisan clear-compiled
php artisan config:clear
php artisan cache:clear
```

**Issue 2: Changes not reflecting**
```bash
# Clear all caches
php artisan optimize:clear

# Or individually:
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

**Issue 3: Database connection errors**
```bash
# Check database connection
php artisan db:show

# Test connection
php artisan tinker
>>> DB::connection()->getPdo();
```

**Issue 4: Queue jobs not processing**
```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

**Issue 5: Frontend not updating**
```bash
# Clear Vite cache
rm -rf node_modules/.vite
npm run dev
```

### Debugging Tools

**1. Laravel Pail (Real-time logs):**
```bash
# Watch logs in real-time
php artisan pail

# Filter by level
php artisan pail --filter="error"

# Filter by message
php artisan pail --message="Customer"
```

**2. Tinker (REPL):**
```bash
php artisan tinker

# Test queries
>>> Customer::count()
>>> Customer::where('company_id', 1)->get()

# Test services
>>> $service = app(CustomerService::class)
>>> $service->getActiveCustomers(1)

# Test events
>>> event(new CustomerCreated(Customer::first()))
```

**3. Ray (Debug tool):**
```php
// Install Ray: composer require spatie/laravel-ray --dev

// Use in code
ray($customer);
ray($customer)->blue();
ray()->table($customers);
ray()->measure(fn() => Customer::all());
```

**4. Vue DevTools:**
- Install Vue DevTools browser extension
- Inspect component state, props, events
- Monitor Pinia store state
- Track Inertia page visits

**5. Database Query Logging:**
```php
// Enable query logging in tinker or controller
DB::enableQueryLog();

// Run queries
Customer::where('company_id', 1)->get();

// View queries
dd(DB::getQueryLog());
```

### Performance Profiling

**1. Identify slow queries:**
```php
// In AppServiceProvider boot method
if (app()->environment('local')) {
    DB::listen(function ($query) {
        if ($query->time > 100) { // Log queries over 100ms
            Log::warning('Slow query', [
                'sql' => $query->sql,
                'time' => $query->time,
                'bindings' => $query->bindings,
            ]);
        }
    });
}
```

**2. Profile API endpoints:**
```bash
# Use Laravel Telescope (if installed)
php artisan telescope:install
php artisan migrate

# Access at: http://localhost:8000/telescope
```

**3. Check memory usage:**
```php
// In your code
ray()->measure(function () {
    // Code to profile
    Customer::with('orders')->get();
});

// Or manually
$start = memory_get_usage();
// ... code ...
$end = memory_get_usage();
ray('Memory used: ' . ($end - $start) / 1024 / 1024 . ' MB');
```

---

## Getting Help

### Team Contacts

**Technical Leads:**
- Backend Lead: [backend-lead@example.com]
- Frontend Lead: [frontend-lead@example.com]
- DevOps Lead: [devops-lead@example.com]

**Domain Experts:**
- CRM Module: [crm-expert@example.com]
- Invoice/Accounting: [accounting-expert@example.com]
- Logistics: [logistics-expert@example.com]

### Resources

**Internal Documentation:**
- Architecture: `docs/developer/ARCHITECTURE.md`
- API Reference: `docs/developer/API_REFERENCE.md`
- Database Schema: `docs/developer/DATABASE.md`
- Frontend Guide: `docs/developer/FRONTEND.md`
- Testing Guide: `docs/developer/TESTING.md`
- Deployment Guide: `docs/operations/DEPLOYMENT.md`

**External Resources:**
- [Laravel 12 Documentation](https://laravel.com/docs/12.x)
- [Vue 3 Documentation](https://vuejs.org/)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Pest PHP Documentation](https://pestphp.com/)
- [TailwindCSS Documentation](https://tailwindcss.com/)

### Communication Channels

**Slack Channels:**
- `#gen-erp-dev` - General development discussion
- `#gen-erp-backend` - Backend-specific questions
- `#gen-erp-frontend` - Frontend-specific questions
- `#gen-erp-bugs` - Bug reports and fixes
- `#gen-erp-releases` - Release announcements

**Meetings:**
- Daily Standup: 10:00 AM (15 minutes)
- Sprint Planning: Every 2 weeks, Monday 2:00 PM
- Code Review: Tuesday/Thursday 3:00 PM
- Tech Talks: Friday 4:00 PM (optional)

### Getting Unstuck

**When you're stuck:**

1. **Check documentation first** - Most answers are in the docs
2. **Search codebase** - Look for similar implementations
3. **Ask in Slack** - Team is friendly and helpful
4. **Pair programming** - Schedule time with a senior dev
5. **Create a ticket** - Document the issue for tracking

**Before asking for help:**
- [ ] Checked relevant documentation
- [ ] Searched codebase for examples
- [ ] Tried debugging with tools (Pail, Tinker, Ray)
- [ ] Reproduced the issue consistently
- [ ] Prepared code snippets and error messages

---

## Welcome Aboard!

You're now ready to start contributing to Gen-ERP! Remember:

- **Ask questions** - No question is too small
- **Read the code** - Best way to learn the codebase
- **Write tests** - They save time in the long run
- **Follow conventions** - Consistency matters
- **Have fun** - We're building something great together!

**Your first tasks:**
1. Complete environment setup
2. Read architecture documentation
3. Pick a "good first issue" from the backlog
4. Submit your first PR
5. Celebrate! 🎉

---

**Last Updated**: March 4, 2026
**Maintainer**: Development Team
**Questions?** Ask in `#gen-erp-dev` Slack channel

