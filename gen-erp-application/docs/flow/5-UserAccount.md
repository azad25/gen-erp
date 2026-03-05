









# Account Domain - Backend & Frontend

## Backend Auth Domain ([app/Domain/Auth/](cci:9://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth:0:0-0:0))

The Auth domain handles user accounts, companies, and authentication in a multi-tenant architecture.

### Models

**User Model:**
```php
// Multi-company membership support
$fillable = [
  'name', 'email', 'password', 'phone', 'avatar_url',
  'preferred_locale', 'locale', 'last_active_company_id',
  'is_superadmin', 'two_factor_secret', 'two_factor_recovery_codes',
  'two_factor_confirmed_at', 'failed_login_count', 'locked_until',
  'password_changed_at',
];

// Relationships
public function companies(): BelongsToMany
{
  return $this->belongsToMany(Company::class, 'company_user')
    ->withPivot(['role', 'is_owner', 'is_active', 'joined_at'])
    ->withTimestamps();
}
```

**Company Model:**
```php
// Tenant company
$fillable = [
  'uuid', 'name', 'slug', 'logo_url', 'business_type',
  'country', 'currency', 'timezone', 'locale', 'vat_registered',
  'lock_date', 'valuation_method', 'vat_bin', 'address_line1',
  'address_line2', 'city', 'district', 'postal_code', 'phone',
  'email', 'website', 'is_active', 'plan', 'plan_expires_at',
  'settings', 'onboarding_completed_at',
];
```

**CompanyUser Pivot Model:**
```php
// Company-user relationship with roles
$fillable = [
  'company_id', 'user_id', 'role', 'is_owner',
  'joined_at', 'invited_by', 'is_active',
];
```

### Services

**AuthService:**
- [login()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/AuthService.php:34:4-101:5) - User authentication
- [register()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/AuthService.php:103:4-122:5) - User registration
- [setupCompany()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/AuthService.php:124:4-143:5) - Company setup
- [verifyTwoFactorCode()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/AuthService.php:194:4-227:5) - 2FA verification
- [getUserData()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/AuthService.php:243:4-262:5) - User data retrieval

**CompanyService:**
- [paginateCompanies()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/CompanyService.php:20:4-34:5) - List companies
- [updateCompany()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/CompanyService.php:36:4-42:5) - Update company details
- [getBranches()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/CompanyService.php:56:4-67:5) - Manage branches
- [createBranch()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/CompanyService.php:77:4-84:5), [updateBranch()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/CompanyService.php:86:4-93:5), [deleteBranch()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/CompanyService.php:95:4-101:5)

**UserService:**
- [paginateUsers()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/UserService.php:27:4-45:5) - List users with search
- [createUser()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/UserService.php:47:4-53:5) - Create new user
- [updateUser()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/UserService.php:55:4-61:5) - Update user
- [addToCompany()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/UserService.php:63:4-75:5) - Add user to company
- [removeFromCompany()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/UserService.php:77:4-83:5) - Remove user from company
- [hasPermissionInCompany()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/UserService.php:95:4-103:5) - Permission check

### Actions

**Authentication Actions:**
- `AuthenticateUserAction` - Authenticate credentials
- [RegisterUserAction](cci:2://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Actions/RegisterUserAction.php:11:0-26:1) - Register new user
- [SetupCompanyAction](cci:2://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Actions/SetupCompanyAction.php:11:0-43:1) - Create company and attach user

**User Management Actions:**
- `CreateUserAction` - Create user
- `UpdateUserAction` - Update user
- `AddUserToCompanyAction` - Add user to company
- `RemoveUserFromCompanyAction` - Remove user from company

**Company Actions:**
- `UpdateCompanyAction` - Update company

## Frontend Settings Pages

### Settings/Company.vue
**Company Settings Page:**
- General information (name, business type, address)
- Financial settings (currency, tax rate, fiscal year)
- Logo & branding
- Save/cancel functionality

### Settings/Users.vue
**User Management Page:**
- List users
- Add/edit users
- Manage company membership

### Settings/Roles.vue
**Role Management Page:**
- Define roles
- Assign permissions

### Settings/Workflows.vue
**Workflow Settings:**
- Configure approval workflows
- Define status transitions

### Settings/Integrations.vue
**Integration Settings:**
- Third-party integrations
- API keys

## Multi-Tenancy Flow

```
User Registration
    ↓
Create User (UserService::createUser)
    ↓
No companies → Redirect to Company Setup Wizard
    ↓
Setup Company (AuthService::setupCompany)
    ├─→ Create Company (SetupCompanyAction)
    ├─→ Attach User as Owner (CompanyUser pivot)
    └─→ Set last_active_company_id
    ↓
User has Company Context
    ↓
All queries filtered by company_id (global scopes)
    ↓
Data isolation guaranteed
```

## Key Features

1. **Multi-Company Support:**
   - Users can belong to multiple companies
   - Active company tracked via session
   - Role-based access per company

2. **Company-User Relationship:**
   - Pivot model with role, ownership, status
   - Roles: admin, manager, staff, viewer
   - Owner flag for company creators

3. **Branch Management:**
   - Companies can have multiple branches
   - Branch-specific data isolation
   - Hierarchical structure

4. **Security Features:**
   - Failed login tracking
   - Account lockout
   - 2FA support
   - Security event logging

5. **Bangladesh Localization:**
   - VAT BIN validation
   - District validation (64 districts)
   - Mobile number format
   - Currency support (BDT, USD, EUR, GBP)

## API Endpoints

**Authentication:**
- `POST /api/v1/auth/login` - Login
- `POST /api/v1/auth/register` - Register
- `POST /api/v1/auth/setup-company` - Setup company
- `GET /api/v1/auth/user` - Get user data

**Company Management:**
- `GET /api/v1/companies` - List companies
- `PUT /api/v1/companies/{id}` - Update company
- `GET /api/v1/companies/{id}/branches` - List branches

**User Management:**
- `GET /api/v1/users` - List users
- `POST /api/v1/users` - Create user
- `PUT /api/v1/users/{id}` - Update user
- `POST /api/v1/users/{id}/add-to-company` - Add to company
- `POST /api/v1/users/{id}/remove-from-company` - Remove from company