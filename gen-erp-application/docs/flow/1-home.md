# Application Entry Point

When you visit the URL, here's the complete flow:

## 1. Entry Point - [public/index.php](cci:7://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/public/index.php:0:0-0:0)

All HTTP requests start here:
- Checks for maintenance mode
- Loads Composer autoloader
- Boots Laravel application
- Handles the request

## 2. Root Route - `/`

From `routes/web.php:47`:
```php
Route::inertia('/', 'Home')->name('home');
```

This route renders the `Home` Inertia page component.

## 3. Home Page - [resources/js/Pages/Home.vue](cci:7://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/resources/js/Pages/Home.vue:0:0-0:0)

The landing page includes:
- **Navigation** with language switcher (BN/EN)
- **Hero Section** with CTA buttons
- **Companies Marquee** - trusted companies display
- **Stats Section** - key metrics
- **Features Section** - product features
- **Modules Section** - ERP modules showcase
- **Parallax Showcase** - dashboard preview

**Authentication Check:**
- If logged in → Shows "ড্যাশবোর্ড" button → redirects to `/dashboard`
- If not logged in → Shows "লগইন" and "শুরু করুন" buttons → redirects to `/login` or `/register`

## Complete Flow:

```
User visits URL (e.g., http://localhost:8000)
    ↓
public/index.php (Laravel entry point)
    ↓
Routes to '/' route
    ↓
Inertia renders 'Home' page
    ↓
resources/js/Pages/Home.vue loads
    ↓
Landing page displayed
    ↓
User clicks button → Redirects to login/register/dashboard
```

**Alternative:** There's also [resources/views/home.blade.php](cci:7://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/resources/views/home.blade.php:0:0-0:0) which provides a Blade template version of the landing page with similar content.