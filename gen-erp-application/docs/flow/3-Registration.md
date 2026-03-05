





# Registration Flow - Backend & Frontend

## Frontend Flow ([resources/js/Pages/Auth/Signup.vue](cci:7://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/resources/js/Pages/Auth/Signup.vue:0:0-0:0))

**User Action:**
1. User enters name, email, password, password_confirmation
2. Accepts terms checkbox
3. Clicks "রেজিস্টার করুন" button

**Frontend Process:**
```javascript
handleSubmit() {
  1. Get CSRF cookie from /sanctum/csrf-cookie
  2. POST to /register with credentials
  3. Handle response:
     - If success → redirect to /login
     - If error → show error message
}
```

**Form Fields:**
- **Name** (required)
- **Email** (required, unique)
- **Password** (required, min 8 characters)
- **Password Confirmation** (required, must match)
- **Terms** (checkbox - must accept)

## Backend Flow ([RegisterController.php](cci:7://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Http/Controllers/Auth/RegisterController.php:0:0-0:0))

**Traditional web registration** with redirects:
```php
public function register(RegisterRequest $request) {
  1. Validate input
  2. Create user:
     - name
     - email
     - password (auto-hashed)
     - phone
     - password_changed_at = now()
  3. Auth::login($user)
  4. Redirect to /setup.company
}
```

## Backend Flow ([AuthService.php](cci:7://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Auth/Services/AuthService.php:0:0-0:0))

**Domain service for registration:**
```php
public function register(UserRegistrationData $userData) {
  1. RegisterUserAction::execute($userData)
     - Creates user with hashed password
     - Sets email_verified_at = null (requires verification)
  2. Create token without company context
  3. Return response with:
     - user data
     - token
     - requires_company_setup: true
}
```

## Validation Rules ([RegisterRequest.php](cci:7://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Http/Requests/Auth/RegisterRequest.php:0:0-0:0))

**Field Validations:**
- `name` - required, string, max:255
- `email` - required, string, email, max:255, unique:users
- `password` - required, string, min:8, confirmed
- `phone` - nullable, string, max:20, regex: `^01[3-9]\d{8}$` (Bangladeshi mobile format)

**Custom Messages:**
- Phone format error: "Please enter a valid Bangladeshi mobile number (e.g. 01712345678)."

## Complete Registration Flow Diagram

```
User fills registration form
    ↓
Frontend: Signup.vue
    ↓
GET /sanctum/csrf-cookie
    ↓
POST /register
    ↓
Backend: RegisterController::register()
    ├─→ Validate input (RegisterRequest)
    ├─→ Create user (RegisterUserAction)
    │   ├─→ Hash password automatically
    │   ├─→ Set email_verified_at = null
    │   └─→ Store in database
    ├─→ Auth::login($user)
    └─→ Redirect to /setup.company
    ↓
Company Setup Wizard
    ↓
User completes company setup
    ↓
Redirect to /dashboard
```

## Key Features

1. **Bangladesh Mobile Validation:** BD format (01[3-9]\d{8})
2. **Password Requirements:** Minimum 8 characters
3. **Email Verification:** Required after registration
4. **Auto-login:** User is logged in immediately after registration
5. **Company Setup:** Redirects to company setup wizard
6. **CSRF Protection:** Sanctum CSRF cookie
7. **Password Hashing:** Automatic via User model cast

## Post-Registration Flow

After successful registration:
1. User is automatically logged in
2. Redirected to `/setup/company` (company setup wizard)
3. User must create company before accessing dashboard
4. Company setup includes:
   - Company name
   - Business type
   - Address
   - Phone/email
   - VAT BIN (optional)
5. After setup → redirect to `/dashboard`