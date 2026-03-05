



# Login Flow - Backend & Frontend

## Frontend Flow ([resources/js/Pages/Auth/Signin.vue](cci:7://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/resources/js/Pages/Auth/Signin.vue:0:0-0:0))

**User Action:**
1. User enters email and password
2. Clicks "লগইন করুন" button

**Frontend Process:**
```javascript
handleSubmit() {
  1. Get CSRF cookie from /sanctum/csrf-cookie
  2. POST to /auth/login with credentials
  3. Handle response:
     - If 2FA required → redirect to /auth/two-factor/challenge
     - If success → store company_id in sessionStorage → redirect to /dashboard
     - If error → show error message
}
```

**Error Handling:**
- `401` → "ইমেইল বা পাসওয়ার্ড ভুল হয়েছে"
- `403` → Account locked or email verification required
- `429` → Too many attempts (rate limited)

## Backend Flow ([AuthController.php](cci:7://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Http/Controllers/Auth/AuthController.php:0:0-0:0))

**Rate Limiting:**
```php
5 attempts per IP per minute
```

**Authentication Steps:**
1. **Check account lock status** - If locked, return 403 with remaining time
2. **Attempt authentication** - `Auth::attempt(email, password)`
3. **Reset failed login count** on success
4. **Email verification check** - Bypass for `dev@generp.test`
5. **2FA check** - If enabled, return partial auth state
6. **Set active company** in session:
   - Single company → auto-select
   - Multiple companies → use last active or first
7. **Return JSON response** with user data

**Account Lockout:**
- 10 failed attempts → 30-minute lockout
- Sends lockout notification email

## Backend Flow ([LoginController.php](cci:7://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Http/Controllers/Auth/LoginController.php:0:0-0:0))

**Traditional web login** with redirects:
- Same logic as [AuthController](cci:2://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Http/Controllers/Auth/AuthController.php:18:0-217:1)
- Returns `RedirectResponse` instead of JSON
- Redirects to `/dashboard` on success

## Complete Login Flow Diagram

```
User enters credentials
    ↓
Frontend: Signin.vue
    ↓
GET /sanctum/csrf-cookie
    ↓
POST /auth/login
    ↓
Backend: AuthController::login()
    ├─→ Rate limiting check
    ├─→ Account lock check
    ├─→ Auth::attempt()
    ├─→ Email verification check
    ├─→ 2FA check
    ├─→ Set active company in session
    └─→ Return JSON response
    ↓
Frontend handles response
    ├─→ 2FA required → redirect to 2FA page
    ├─→ Success → store company_id → redirect to /dashboard
    └─→ Error → show error message
```

## Key Security Features

1. **Rate Limiting:** 5 attempts per minute per IP
2. **Account Lockout:** 10 failures → 30-minute lock
3. **CSRF Protection:** Sanctum CSRF cookie
4. **Session Regeneration:** Prevents session fixation
5. **Email Verification:** Required (except dev admin)
6. **2FA Support:** Optional TOTP-based 2FA
7. **Failed Attempt Tracking:** Logged to database



There are **two Auth controllers** for different purposes:

## 1. `App\Http\Controllers\Api\V1\AuthController.php`

**Purpose:** REST API v1 for external API consumers

**Characteristics:**
- Namespace: `App\Http\Controllers\Api\V1`
- Uses **token-based authentication** (Sanctum tokens)
- Has OpenAPI/Swagger documentation
- Returns JSON responses
- Used by external API clients (mobile apps, third-party integrations)
- Routes: `/api/v1/auth/*`

## 2. `App\Http\Controllers\Auth\AuthController.php`

**Purpose:** SPA (Vue.js frontend) authentication

**Characteristics:**
- Namespace: `App\Http\Controllers\Auth`
- Uses **cookie-based authentication** (Sanctum SPA auth)
- No OpenAPI docs
- Returns JSON responses
- Used by internal Vue.js frontend
- Routes: `/auth/*`

## Why Both?

**Separation of Concerns:**

| Feature | API/V1/AuthController | Auth/AuthController |
|---------|----------------------|---------------------|
| **Target** | External API consumers | Internal Vue SPA |
| **Auth Type** | Bearer tokens | Cookie-based sessions |
| **Routes** | `/api/v1/auth/*` | `/auth/*` |
| **Documentation** | OpenAPI/Swagger | None |
| **Use Case** | Mobile apps, integrations | Web frontend |

**Example:**
- Vue frontend uses `POST /auth/login` (cookie-based)
- Mobile app uses `POST /api/v1/auth/login` (token-based)

This allows the system to serve both internal web users and external API consumers with appropriate authentication methods.