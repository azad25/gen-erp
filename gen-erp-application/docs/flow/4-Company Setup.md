

# Company Setup Flow

## Frontend Flow ([resources/js/Pages/Auth/CompanySetup.vue](cci:7://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/resources/js/Pages/Auth/CompanySetup.vue:0:0-0:0))

**3-Step Wizard:**

### Step 1: Basics
- Company name (required)
- Business type (required)
- Phone (optional)
- Company email (optional)

### Step 2: Location
- Street address (optional)
- City (optional)
- Postal code (optional)
- District (optional)
- VAT Registered checkbox
- VAT BIN (required if VAT registered)

### Step 3: Confirm
- Review all entered data
- Submit button

**Frontend Process:**
```javascript
handleSubmit() {
  1. Validate each step
  2. POST to /api/v1/auth/setup-company
  3. On success → redirect to /dashboard
  4. On error → display validation errors
}
```

## Backend Flow ([AuthController::setupCompany()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/AuthService.php:124:4-143:5))

**Validation ([CompanySetupRequest](cci:2://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Http/Requests/Auth/CompanySetupRequest.php:9:0-99:1)):**

**Required Fields:**
- `name` - Company name (string, max:255)
- `business_type` - Business type (enum: retail, wholesale, manufacturing, etc.)

**Optional Fields:**
- `country` - Country code (BD, US, UK, CA, AU)
- `currency` - Currency code (BDT, USD, EUR, GBP, CAD, AUD)
- `timezone` - Timezone
- `locale` - Language (en, bn)
- `address_line1`, `address_line2` - Address
- `city`, `district`, `postal_code` - Location
- `phone` - BD mobile format
- `email`, `website` - Contact info
- `vat_bin` - 12 digits
- `trade_license`, `tin` - Tax IDs

**Bangladesh-Specific Validations:**
- Phone: `^(\+880|880|0)?1[3-9]\d{8}$`
- Postal code: `^\d{4}$`
- VAT BIN: `^\d{12}$`
- TIN: `^\d{12}$`
- District: Must be valid BD district (64 districts)

**Domain Action ([SetupCompanyAction](cci:2://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Actions/SetupCompanyAction.php:11:0-43:1)):**

```php
public function execute(User $user, CompanySetupData $data): Company {
  1. Create company with UUID and slug
  2. Use user's email if company email not provided
  3. Attach user to company as owner
  4. Update user's last_active_company_id
  5. Return company
}
```

**User-Company Relationship:**
```php
$company->users()->attach($user->id, [
  'role' => 'owner',
  'is_owner' => true,
  'is_active' => true,
  'joined_at' => now(),
]);
```

## Complete Flow Diagram

```
User completes 3-step wizard
    ↓
Frontend: CompanySetup.vue
    ↓
GET /sanctum/csrf-cookie
    ↓
POST /api/v1/auth/setup-company
    ↓
Backend: AuthController::setupCompany()
    ├─→ Validate input (CompanySetupRequest)
    │   ├─→ Required: name, business_type
    │   ├─→ BD-specific: phone, postal_code, vat_bin, tin
    │   └─→ District validation (64 BD districts)
    ├─→ SetupCompanyAction::execute()
    │   ├─→ Create company with UUID/slug
    │   ├─→ Attach user as owner
    │   └─→ Set last_active_company_id
    └─→ Return success response
    ↓
Frontend handles response
    ├─→ Success → redirect to /dashboard
    └─→ Error → display validation errors
```

## Key Features

1. **Bangladesh Localization:**
   - 64 valid districts
   - BD mobile format
   - 4-digit postal codes
   - 12-digit VAT BIN/TIN

2. **Business Types:**
   - retail, wholesale, manufacturing
   - rmg (Ready Made Garments)
   - restaurant, service, freelancer
   - ngo, ecommerce, school, government, other

3. **Multi-tenancy:**
   - User attached as owner
   - Company UUID for unique identification
   - Slug for URL-friendly access
   - Active company set as last_active

4. **Validation:**
   - Server-side validation
   - Client-side error display
   - Field-specific error messages