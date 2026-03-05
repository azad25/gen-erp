# CMS Domain - Complete Analysis

## Overview

The CMS domain provides comprehensive content management with multi-tenant site management, page builder, blog functionality, e-commerce features, and SEO optimization.

## Backend Architecture

### 1. Core Models

#### Site Model (`app/Domain/CMS/Models/Site.php`)

**Purpose:** Multi-tenant site configuration

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'name',                   // Site name
  'slug',                   // Site slug
  'domain',                 // Custom domain
  'subdomain',              // Subdomain
  'logo',                   // Logo URL
  'favicon',                // Favicon URL
  'primary_color',          // Primary color
  'accent_color',           // Accent color
  'font_family',            // Font family
  'status',                 // Status (draft, published, maintenance)
  'seo_title',              // SEO title
  'seo_description',        // SEO description
  'seo_image',              // SEO image
  'google_analytics_id',   // Google Analytics ID
  'facebook_pixel_id',      // Facebook Pixel ID
  'settings',               // Settings (JSON)
  'published_at',           // Published at
];

// Status constants
public const STATUS_DRAFT = 'draft';
public const STATUS_PUBLISHED = 'published';
public const STATUS_MAINTENANCE = 'maintenance';
```

**Relationships:**
```php
company() -> Company
pages() -> Page (hasMany)
menus() -> Menu (hasMany)
blogPosts() -> BlogPost (hasMany)
blogCategories() -> BlogCategory (hasMany)
customerAccounts() -> CustomerAccount (hasMany)
contactSubmissions() -> ContactSubmission (hasMany)
```

#### Page Model (`app/Domain/CMS/Models/Page.php`)

**Purpose:** Page content management

**Database Schema:**
```php
$fillable = [
  'site_id',                // Site (FK)
  'title',                  // Page title
  'slug',                   // URL slug
  'seo_title',              // SEO title
  'seo_description',        // SEO description
  'seo_image',              // SEO image
  'status',                 // Status (draft, published, scheduled, archived)
  'is_homepage',            // Is homepage
  'sort_order',             // Sort order
  'published_at',           // Published at
  'scheduled_at',           // Scheduled at
];

// Status constants
public const STATUS_DRAFT = 'draft';
public const STATUS_PUBLISHED = 'published';
public const STATUS_SCHEDULED = 'scheduled';
public const STATUS_ARCHIVED = 'archived';
```

**Relationships:**
```php
site() -> Site
sections() -> Section (hasMany)
```

**Key Methods:**
```php
public function getUrl(): string {
  $baseUrl = $this->site->getUrl();
  
  if ($this->is_homepage) {
    return $baseUrl;
  }

  return "{$baseUrl}/{$this->slug}";
}

public function isPublished(): bool {
  return $this->status === PageStatus::PUBLISHED 
    && ($this->published_at === null || $this->published_at <= now());
}

public function isScheduled(): bool {
  return $this->status === PageStatus::SCHEDULED 
    && $this->scheduled_at !== null 
    && $this->scheduled_at > now();
}
```

#### BlogPost Model (`app/Domain/CMS/Models/BlogPost.php`)

**Purpose:** Blog post management

**Database Schema:**
```php
$fillable = [
  'site_id',                // Site (FK)
  'category_id',            // BlogCategory (FK)
  'author_id',              // User (FK)
  'title',                  // Post title
  'slug',                   // URL slug
  'excerpt',                // Excerpt
  'content',                // Content
  'featured_image',         // Featured image
  'status',                 // Status (draft, published, scheduled, archived)
  'published_at',           // Published at
  'scheduled_at',           // Scheduled at
  'views_count',            // Views count
];

// Status constants
public const STATUS_DRAFT = 'draft';
public const STATUS_PUBLISHED = 'published';
public const STATUS_SCHEDULED = 'scheduled';
public const STATUS_ARCHIVED = 'archived';
```

**Relationships:**
```php
site() -> Site
category() -> BlogCategory
author() -> User
```

**Key Methods:**
```php
public function getUrl(): string {
  $baseUrl = $this->site->getUrl();
  return "{$baseUrl}/blog/{$this->slug}";
}

public function getExcerpt(int $length = 160): string {
  if ($this->excerpt) {
    return $this->excerpt;
  }

  return substr(strip_tags($this->content), 0, $length) . '...';
}

public function getReadingTime(): int {
  $wordCount = str_word_count(strip_tags($this->content));
  return max(1, (int) ceil($wordCount / 200)); // 200 words per minute
}
```

#### Section Model (`app/Domain/CMS/Models/Section.php`)

**Purpose:** Page sections for page builder

**Database Schema:**
```php
$fillable = [
  'page_id',                // Page (FK)
  'type',                   // Section type
  'sort_order',             // Sort order
  'content',               // Content (JSON)
  'is_visible',             // Is visible
];

// Section type constants
public const TYPE_HERO_BANNER = 'hero_banner';
public const TYPE_TEXT_BLOCK = 'text_block';
public const TYPE_IMAGE_TEXT = 'image_text';
public const TYPE_PRODUCT_GRID = 'product_grid';
public const TYPE_PORTFOLIO_GRID = 'portfolio_grid';
public const TYPE_TEAM_GRID = 'team_grid';
public const TYPE_STATS = 'stats';
public const TYPE_FAQ = 'faq';
public const TYPE_CTA_BANNER = 'cta_banner';
public const TYPE_CONTACT_FORM = 'contact_form';
public const TYPE_GALLERY = 'gallery';
public const TYPE_TESTIMONIALS = 'testimonials';
public const TYPE_BLOG_POSTS = 'blog_posts';
public const TYPE_CUSTOM_HTML = 'custom_html';
```

**Relationships:**
```php
page() -> Page
```

**Key Methods:**
```php
public function getContent(string $key, mixed $default = null): mixed {
  return $this->content[$key] ?? $default;
}

public function setContent(string $key, mixed $value): void {
  $content = $this->content ?? [];
  $content[$key] = $value;
  $this->content = $content;
}

public function hasContent(string $key): bool {
  return isset($this->content[$key]) && !empty($this->content[$key]);
}
```

#### Menu Model (`app/Domain/CMS/Models/Menu.php`)

**Purpose:** Navigation menu management

**Database Schema:**
```php
$fillable = [
  'site_id',                // Site (FK)
  'name',                   // Menu name
  'location',               // Location (header, footer, etc.)
];
```

**Relationships:**
```php
site() -> Site
items() -> MenuItem (hasMany)
rootItems() -> MenuItem (hasMany, whereNull('parent_id'))
```

**Key Methods:**
```php
public function getNestedItems(): array {
  $items = $this->rootItems()->with('children')->get();
  
  return $items->map(function ($item) {
    return $this->formatMenuItem($item);
  })->toArray();
}

private function formatMenuItem(MenuItem $item): array {
  $formatted = [
    'id' => $item->id,
    'label' => $item->label,
    'url' => $item->getUrl(),
    'target' => $item->target,
  ];

  if ($item->children->isNotEmpty()) {
    $formatted['children'] = $item->children->map(function ($child) {
      return $this->formatMenuItem($child);
    })->toArray();
  }

  return $formatted;
}
```

#### ContactSubmission Model (`app/Domain/CMS/Models/ContactSubmission.php`)

**Purpose:** Contact form submissions

**Database Schema:**
```php
$fillable = [
  'site_id',                // Site (FK)
  'name',                   // Name
  'email',                  // Email
  'phone',                  // Phone
  'company',                // Company
  'subject',                // Subject
  'message',                // Message
  'form_data',              // Form data (JSON)
  'status',                 // Status (new, contacted, resolved, spam)
  'source',                 // Source
  'ip_address',             // IP address
  'user_agent',             // User agent
  'contacted_at',           // Contacted at
  'assigned_to',            // Assigned to (User FK)
  'notes',                  // Notes
];
```

**Relationships:**
```php
site() -> Site
assignedUser() -> User
```

**Key Methods:**
```php
public function markAsContacted(?int $userId = null): void {
  $this->update([
    'status' => 'contacted',
    'contacted_at' => now(),
    'assigned_to' => $userId,
  ]);
}

public function markAsResolved(?string $notes = null): void {
  $this->update([
    'status' => 'resolved',
    'notes' => $notes,
  ]);
}

public function markAsSpam(): void {
  $this->update([
    'status' => 'spam',
  ]);
}

public function assignTo(int $userId): void {
  $this->update([
    'assigned_to' => $userId,
  ]);
}
```

#### ShoppingCart Model (`app/Domain/CMS/Models/ShoppingCart.php`)

**Purpose:** Shopping cart for e-commerce

**Database Schema:**
```php
$fillable = [
  'site_id',                // Site (FK)
  'session_id',             // Session ID
  'customer_id',            // CustomerAccount (FK)
  'expires_at',             // Expires at
];
```

**Relationships:**
```php
site() -> Site
customer() -> CustomerAccount
items() -> CartItem (hasMany)
```

**Key Methods:**
```php
public function isExpired(): bool {
  return $this->expires_at && $this->expires_at->isPast();
}

public function isEmpty(): bool {
  return $this->items()->count() === 0;
}

public function getItemCount(): int {
  return $this->items()->sum('quantity');
}

public function getSubtotal(): float {
  return $this->items()->get()->sum(function (CartItem $item) {
    return $item->getTotal();
  });
}

public function getTotal(): float {
  $subtotal = $this->getSubtotal();
  // Add shipping, tax, discount logic here
  return $subtotal;
}
```

#### CartItem Model

**Purpose:** Shopping cart items

**Database Schema:**
```php
$fillable = [
  'cart_id',                // ShoppingCart (FK)
  'product_id',             // Product (FK)
  'product_variant_id',     // ProductVariant (FK)
  'quantity',               // Quantity
  'unit_price',             // Unit price
  'total_price',            // Total price
];
```

#### CustomerAccount Model (`app/Domain/CMS/Models/CustomerAccount.php`)

**Purpose:** Customer accounts for e-commerce

**Database Schema:**
```php
$fillable = [
  'site_id',                // Site (FK)
  'email',                  // Email
  'password',               // Password
  'first_name',             // First name
  'last_name',              // Last name
  'phone',                  // Phone
  'is_guest',               // Is guest
  'email_verified_at',      // Email verified at
];
```

**Relationships:**
```php
site() -> Site
carts() -> ShoppingCart (hasMany)
orders() -> PublicOrder (hasMany)
```

**Key Methods:**
```php
public function getFullName(): string {
  return trim($this->first_name . ' ' . $this->last_name);
}
```

#### PublicOrder Model (`app/Domain/CMS/Models/PublicOrder.php`)

**Purpose:** Public orders for e-commerce

**Database Schema:**
```php
$fillable = [
  'site_id',                // Site (FK)
  'customer_id',            // CustomerAccount (FK)
  'order_number',           // Order number
  'customer_email',         // Customer email
  'customer_first_name',    // Customer first name
  'customer_last_name',     // Customer last name
  'customer_phone',         // Customer phone
  'billing_address_line_1', // Billing address line 1
  'billing_address_line_2', // Billing address line 2
  'billing_city',           // Billing city
  'billing_state',          // Billing state
  'billing_postal_code',    // Billing postal code
  'billing_country',        // Billing country
  'shipping_address_line_1', // Shipping address line 1
  'shipping_address_line_2', // Shipping address line 2
  'shipping_city',          // Shipping city
  'shipping_state',         // Shipping state
  'shipping_postal_code',   // Shipping postal code
  'shipping_country',       // Shipping country
  'subtotal',               // Subtotal
  'shipping_cost',          // Shipping cost
  'tax_amount',             // Tax amount
  'discount_amount',        // Discount amount
  'total_amount',           // Total amount
  'status',                 // Status (pending, processing, completed, cancelled)
  'payment_status',         // Payment status (pending, paid, failed, refunded)
  'payment_method',         // Payment method (credit_card, bank_transfer, cash, etc.)
  'customer_notes',         // Customer notes
  'admin_notes',            // Admin notes
  'tracking_number',        // Tracking number
  'placed_at',              // Placed at
  'completed_at',           // Completed at
  'cancelled_at',           // Cancelled at
];

// Status constants
public const STATUS_PENDING = 'pending';
public const STATUS_PROCESSING = 'processing';
public const STATUS_COMPLETED = 'completed';
public const STATUS_CANCELLED = 'cancelled';

// Payment status constants
public const PAYMENT_STATUS_PENDING = 'pending';
public const PAYMENT_STATUS_PAID = 'paid';
public const PAYMENT_STATUS_FAILED = 'failed';
public const PAYMENT_STATUS_REFUNDED = 'refunded';

// Payment method constants
public const PAYMENT_METHOD_CREDIT_CARD = 'credit_card';
public const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';
public const PAYMENT_METHOD_CASH = 'cash';
public const PAYMENT_METHOD_COD = 'cod';
```

**Relationships:**
```php
site() -> Site
customer() -> CustomerAccount
items() -> PublicOrderItem (hasMany)
```

#### ProductReview Model (`app/Domain/CMS/Models/ProductReview.php`)

**Purpose:** Product reviews

**Database Schema:**
```php
$fillable = [
  'site_id',                // Site (FK)
  'product_id',             // Product (FK)
  'customer_id',            // CustomerAccount (FK)
  'order_id',               // PublicOrder (FK)
  'rating',                 // Rating (1-5)
  'title',                  // Review title
  'review',                 // Review content
  'customer_name',          // Customer name
  'customer_email',         // Customer email
  'is_verified_purchase',   // Is verified purchase
  'is_approved',            // Is approved
  'helpful_count',          // Helpful count
];
```

**Relationships:**
```php
site() -> Site
customer() -> CustomerAccount
order() -> PublicOrder
```

#### Wishlist Model

**Purpose:** Wishlist functionality

**Database Schema:**
```php
$fillable = [
  'site_id',                // Site (FK)
  'customer_id',            // CustomerAccount (FK)
  'items',                  // Items (JSON)
];
```

**Relationships:**
```php
site() -> Site
customer() -> CustomerAccount
```

### 2. Services

#### CMSService (`app/Domain/CMS/Services/CMSService.php`)

**Purpose:** Core CMS management

**Methods:**

```php
// Site Management
public function getSitesForCompany(int $companyId): Collection {
  return Site::where('company_id', $companyId)
    ->orderBy('created_at', 'desc')
    ->get();
}

public function createSite(CreateSiteData $data): Site {
  $site = Site::create($data->toArray());
  event(new SiteCreated($site));
  return $site;
}

public function publishSite(int $siteId): Site {
  $site = Site::findOrFail($siteId);
  
  $site->update([
    'status' => SiteStatus::PUBLISHED,
    'published_at' => now(),
  ]);

  event(new SitePublished($site));

  return $site->fresh();
}

public function findSiteBySubdomain(string $subdomain): ?Site {
  return Site::bySubdomain($subdomain)->published()->first();
}

// Page Management
public function createPage(CreatePageData $data): Page {
  // If this is set as homepage, unset other homepages
  if ($data->isHomepage) {
    Page::where('site_id', $data->siteId)
      ->where('is_homepage', true)
      ->update(['is_homepage' => false]);
  }

  $page = Page::create($data->toArray());
  event(new PageCreated($page));
  return $page;
}

public function publishPage(int $pageId): Page {
  $page = Page::findOrFail($pageId);
  
  $page->update([
    'status' => PageStatus::PUBLISHED,
    'published_at' => now(),
  ]);

  event(new PagePublished($page));

  return $page->fresh();
}
```

#### PublicSiteService (`app/Domain/CMS/Services/PublicSiteService.php`)

**Purpose:** Public-facing site rendering

**Methods:**

```php
public function findSiteByTenant(string $tenant): ?Site {
  // Try to find by custom domain first
  $site = Site::where('custom_domain', $tenant)
    ->where('is_published', true)
    ->first();

  if ($site) {
    return $site;
  }

  // Try to find by subdomain
  return Site::where('subdomain', $tenant)
    ->where('is_published', true)
    ->first();
}

public function getSiteData(string $tenant): ?array {
  $site = $this->findSiteByTenant($tenant);

  if (!$site) {
    return null;
  }

  return [
    'site' => [
      'id' => $site->id,
      'name' => $site->name,
      'subdomain' => $site->subdomain,
      'custom_domain' => $site->custom_domain,
      'theme' => $site->theme,
      'logo_url' => $site->logo_url,
      'favicon_url' => $site->favicon_url,
      'meta_title' => $site->meta_title,
      'meta_description' => $site->meta_description,
      'settings' => $site->settings,
    ],
    'menus' => $this->getSiteMenus($site->id),
  ];
}

public function getPageBySlug(string $tenant, string $slug): ?array {
  $site = $this->findSiteByTenant($tenant);

  if (!$site) {
    return null;
  }

  $page = Page::where('site_id', $site->id)
    ->where('slug', $slug)
    ->where('is_published', true)
    ->with(['sections' => function ($query) {
      $query->where('is_visible', true)->orderBy('order');
    }])
    ->first();

  if (!$page) {
    return null;
  }

  return [
    'page' => [
      'id' => $page->id,
      'title' => $page->title,
      'slug' => $page->slug,
      'meta_title' => $page->meta_title,
      'meta_description' => $page->meta_description,
      'meta_keywords' => $page->meta_keywords,
      'og_image' => $page->og_image,
      'is_homepage' => $page->is_homepage,
    ],
    'sections' => $page->sections->map(function ($section) {
      return [
        'id' => $section->id,
        'type' => $section->type,
        'content' => $section->content,
        'order' => $section->order,
      ];
    })->toArray(),
  ];
}

public function getHomepage(string $tenant): ?array {
  $site = $this->findSiteByTenant($tenant);

  if (!$site) {
    return null;
  }

  $page = Page::where('site_id', $site->id)
    ->where('is_homepage', true)
    ->where('is_published', true)
    ->with(['sections' => function ($query) {
      $query->where('is_visible', true)->orderBy('order');
    }])
    ->first();

  if (!$page) {
    // If no homepage is set, get the first published page
    $page = Page::where('site_id', $site->id)
      ->where('is_published', true)
      ->with(['sections' => function ($query) {
        $query->where('is_visible', true)->orderBy('order');
      }])
      ->orderBy('created_at')
      ->first();
  }

  if (!$page) {
    return null;
  }

  return [
    'page' => [
      'id' => $page->id,
      'title' => $page->title,
      'slug' => $page->slug,
      'meta_title' => $page->meta_title,
      'meta_description' => $page->meta_description,
      'meta_keywords' => $page->meta_keywords,
      'og_image' => $page->og_image,
      'is_homepage' => $page->is_homepage,
    ],
    'sections' => $page->sections->map(function ($section) {
      return [
        'id' => $section->id,
        'type' => $section->type,
        'content' => $section->content,
        'order' => $section->order,
      ];
    })->toArray(),
  ];
}
```

#### SEOService (`app/Domain/CMS/Services/SEOService.php`)

**Purpose:** SEO optimization

**Methods:**

```php
public function generateSitemap(Site $site): string {
  $baseUrl = $site->getUrl();
  $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
  $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

  // Add homepage
  $xml .= $this->addSitemapUrl($baseUrl, now(), 'daily', '1.0');

  // Add pages
  $pages = $site->pages()->where('is_published', true)->get();
  foreach ($pages as $page) {
    $url = $baseUrl . '/' . $page->slug;
    $xml .= $this->addSitemapUrl($url, $page->updated_at, 'weekly', '0.8');
  }

  // Add blog posts
  $blogPosts = $site->blogPosts()->where('is_published', true)->get();
  foreach ($blogPosts as $post) {
    $url = $baseUrl . '/blog/' . $post->slug;
    $xml .= $this->addSitemapUrl($url, $post->updated_at, 'monthly', '0.6');
  }

  $xml .= '</urlset>';

  return $xml;
}

public function generateRobotsTxt(Site $site): string {
  $baseUrl = $site->getUrl();
  $robotsTxt = "User-agent: *\n";
  
  if ($site->isPublished()) {
    $robotsTxt .= "Allow: /\n";
    $robotsTxt .= "Disallow: /admin/\n";
    $robotsTxt .= "Disallow: /api/\n";
    $robotsTxt .= "Disallow: /*.json$\n";
  } else {
    $robotsTxt .= "Disallow: /\n";
  }

  $robotsTxt .= "\nSitemap: {$baseUrl}/sitemap.xml\n";

  return $robotsTxt;
}

public function generateStructuredData(Site $site, ?Page $page = null, ?BlogPost $blogPost = null): array {
  $structuredData = [];

  // Organization/LocalBusiness schema
  $structuredData[] = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => $site->name,
    'url' => $site->getUrl(),
    'logo' => $site->logo_url,
    'description' => $site->meta_description,
    'address' => $this->getBusinessAddress($site),
    'contactPoint' => $this->getContactPoint($site),
    'sameAs' => $this->getSocialMediaLinks($site),
  ];

  // WebSite schema
  $structuredData[] = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => $site->name,
    'url' => $site->getUrl(),
    'description' => $site->meta_description,
    'potentialAction' => [
      '@type' => 'SearchAction',
      'target' => $site->getUrl() . '/search?q={search_term_string}',
      'query-input' => 'required name=search_term_string',
    ],
  ];

  // Page-specific schema
  if ($page) {
    $structuredData[] = [
      '@context' => 'https://schema.org',
      '@type' => 'WebPage',
      'name' => $page->title,
      'description' => $page->meta_description,
      'url' => $site->getUrl() . '/' . $page->slug,
      'isPartOf' => [
        '@type' => 'WebSite',
        'name' => $site->name,
        'url' => $site->getUrl(),
      ],
      'datePublished' => $page->created_at->toISOString(),
      'dateModified' => $page->updated_at->toISOString(),
    ];
  }

  // Blog post schema
  if ($blogPost) {
    $structuredData[] = [
      '@context' => 'https://schema.org',
      '@type' => 'Article',
      'headline' => $blogPost->title,
      'description' => $blogPost->excerpt,
      'image' => $blogPost->featured_image,
      'url' => $site->getUrl() . '/blog/' . $blogPost->slug,
      'datePublished' => $blogPost->published_at->toISOString(),
      'dateModified' => $blogPost->updated_at->toISOString(),
      'author' => [
        '@type' => 'Person',
        'name' => $blogPost->author?->name ?? 'Admin',
      ],
      'publisher' => [
        '@type' => 'Organization',
        'name' => $site->name,
        'logo' => [
          '@type' => 'ImageObject',
          'url' => $site->logo_url,
        ],
      ],
      'mainEntityOfPage' => [
        '@type' => 'WebPage',
        'id' => $site->getUrl() . '/blog/' . $blogPost->slug,
      ],
    ];
  }

  return $structuredData;
}
```

#### CartService (`app/Domain/CMS/Services/CartService.php`)

**Purpose:** Shopping cart management

**Methods:**

```php
public function getCart(int $siteId, string $sessionId, ?int $customerId = null): ShoppingCart {
  // First try to find existing cart
  $query = ShoppingCart::where('site_id', $siteId)->active();
  
  if ($customerId) {
    $cart = $query->where('customer_id', $customerId)->first();
  } else {
    $cart = $query->where('session_id', $sessionId)->first();
  }
  
  // Create new cart if not found
  if (!$cart) {
    $cart = ShoppingCart::create([
      'site_id' => $siteId,
      'session_id' => $customerId ? null : $sessionId,
      'customer_id' => $customerId,
      'expires_at' => $customerId ? null : now()->addHours(24),
    ]);
  }
  
  return $cart;
}

public function addItem(int $cartId, AddToCartData $data): CartItem {
  $cart = ShoppingCart::findOrFail($cartId);
  
  // Check if item already exists in cart
  $existingItem = $cart->items()
    ->forProduct($data->productId, $data->productVariantId)
    ->first();
  
  if ($existingItem) {
    // Update quantity if item exists
    $existingItem->incrementQuantity($data->quantity);
    $item = $existingItem;
  } else {
    // Create new cart item
    $item = $cart->items()->create($data->toArray());
  }
  
  // Dispatch event
  event(new CartItemAdded($item));
  
  return $item;
}

public function convertToOrder(int $cartId, CreateOrderData $data): PublicOrder {
  return DB::transaction(function () use ($cartId, $data) {
    $cart = ShoppingCart::with('items.product', 'items.productVariant')->findOrFail($cartId);
    
    if ($cart->isEmpty()) {
      throw new \InvalidArgumentException('Cannot create order from empty cart');
    }
    
    // Generate order number
    $orderNumber = $this->generateOrderNumber();
    
    // Create order
    $orderData = array_merge($data->toArray(), [
      'order_number' => $orderNumber,
      'status' => OrderStatus::PENDING->value,
      'payment_status' => PaymentStatus::PENDING->value,
      'placed_at' => now(),
    ]);
    
    $order = PublicOrder::create($orderData);
    
    // Create order items
    foreach ($cart->items as $cartItem) {
      PublicOrderItem::create([
        'order_id' => $order->id,
        'product_id' => $cartItem->product_id,
        'product_variant_id' => $cartItem->product_variant_id,
        'quantity' => $cartItem->quantity,
        'unit_price' => $cartItem->unit_price,
        'total_price' => $cartItem->total_price,
      ]);
    }
    
    // Clear cart
    $cart->clear();
    
    // Dispatch event
    event(new OrderPlaced($order));
    
    return $order->fresh();
  });
}
```

## Frontend Architecture

### 1. CMS/Dashboard/Index.vue

**Purpose:** CMS dashboard overview

**Metrics Displayed:**
- Total Sites
- Total Pages
- Blog Posts
- Media Files
- Published Pages
- Draft Pages
- Contact Submissions

**Components:**
- Recent Pages
- Recent Blog Posts

**API Calls:**
```javascript
GET /api/v1/cms/dashboard - Dashboard metrics
```

### 2. CMS/Sites/Index.vue

**Purpose:** List all sites

**Features:**
- List sites with columns:
  - Site (name, slug, logo)
  - Domain
  - Status (Badge)
  - Pages count
- Actions:
  - View
  - Edit
  - Delete
- Create Site button

**API Calls:**
```javascript
GET /api/v1/cms/sites - List sites
POST /api/v1/cms/sites - Create site
PUT /api/v1/cms/sites/{id} - Update site
DELETE /api/v1/cms/sites/{id} - Delete site
```

### 3. CMS/Pages/Index.vue

**Purpose:** List all pages

**Features:**
- List pages with columns:
  - Page (title, slug)
  - URL
  - Status (Badge)
  - Template
  - Updated
- Actions:
  - Builder
  - Edit
  - Delete
- Create Page button

**API Calls:**
```javascript
GET /api/v1/cms/sites/{id}/pages - List pages
POST /api/v1/cms/sites/{id}/pages - Create page
PUT /api/v1/cms/sites/{id}/pages/{pageId} - Update page
DELETE /api/v1/cms/sites/{id}/pages/{pageId} - Delete page
```

### 4. CMS/Pages/Create.vue

**Purpose:** Create new page

**Form Fields:**
- Title (required)
- Slug
- SEO Title
- SEO Description
- Status
- Is Homepage
- Sort Order

**API Calls:**
```javascript
POST /api/v1/cms/sites/{id}/pages - Create page
```

### 5. CMS/PageBuilder/Index.vue

**Purpose:** Page builder with sections

**Features:**
- Drag and drop sections
- Section types:
  - Hero Banner
  - Text Block
  - Image & Text
  - Product Grid
  - Portfolio Grid
  - Team Grid
  - Stats
  - FAQ
  - CTA Banner
  - Contact Form
  - Gallery
  - Testimonials
  - Blog Posts
  - Custom HTML

**API Calls:**
```javascript
GET /api/v1/cms/sites/{id}/pages/{pageId}/sections - Get sections
POST /api/v1/cms/sites/{id}/pages/{pageId}/sections - Create section
PUT /api/v1/cms/sites/{id}/pages/{pageId}/sections/{sectionId} - Update section
DELETE /api/v1/cms/sites/{id}/pages/{pageId}/sections/{sectionId} - Delete section
```

### 6. CMS/Blog/Index.vue

**Purpose:** List all blog posts

**Features:**
- List blog posts with columns:
  - Title
  - Category
  - Author
  - Status (Badge)
  - Published
- Actions:
  - Edit
  - Delete
- Create Blog Post button

**API Calls:**
```javascript
GET /api/v1/cms/sites/{id}/blog-posts - List blog posts
POST /api/v1/cms/sites/{id}/blog-posts - Create blog post
PUT /api/v1/cms/sites/{id}/blog-posts/{postId} - Update blog post
DELETE /api/v1/cms/sites/{id}/blog-posts/{postId} - Delete blog post
```

### 7. CMS/Menus/Index.vue

**Purpose:** List all menus

**Features:**
- List menus with columns:
  - Name
  - Location
  - Items count
- Actions:
  - Edit
  - Delete
- Create Menu button

**API Calls:**
```javascript
GET /api/v1/cms/sites/{id}/menus - List menus
POST /api/v1/cms/sites/{id}/menus - Create menu
PUT /api/v1/cms/sites/{id}/menus/{menuId} - Update menu
DELETE /api/v1/cms/sites/{id}/menus/{menuId} - Delete menu
```

### 8. CMS/Menus/Builder.vue

**Purpose:** Menu builder

**Features:**
- Drag and drop menu items
- Add menu items
- Edit menu items
- Delete menu items
- Nested menu items

**API Calls:**
```javascript
GET /api/v1/cms/sites/{id}/menus/{menuId}/items - Get menu items
POST /api/v1/cms/sites/{id}/menus/{menuId}/items - Create menu item
PUT /api/v1/cms/sites/{id}/menus/{menuId}/items/{itemId} - Update menu item
DELETE /api/v1/cms/sites/{id}/menus/{menuId}/items/{itemId} - Delete menu item
```

### 9. CMS/Contacts/Index.vue

**Purpose:** List contact submissions

**Features:**
- List contact submissions with columns:
  - Name
  - Email
  - Subject
  - Status (Badge)
  - Created
- Actions:
  - View
  - Assign
  - Mark as Contacted
  - Mark as Resolved
  - Mark as Spam

**API Calls:**
```javascript
GET /api/v1/cms/sites/{id}/contact-submissions - List contact submissions
PUT /api/v1/cms/sites/{id}/contact-submissions/{submissionId}/contact - Mark as contacted
PUT /api/v1/cms/sites/{id}/contact-submissions/{submissionId}/resolve - Mark as resolved
PUT /api/v1/cms/sites/{id}/contact-submissions/{submissionId}/spam - Mark as spam
```

### 10. CMS/Reviews/Index.vue

**Purpose:** List product reviews

**Features:**
- List reviews with columns:
  - Product
  - Customer
  - Rating
  - Status (Badge)
  - Created
- Actions:
  - View
  - Approve
  - Reject

**API Calls:**
```javascript
GET /api/v1/cms/sites/{id}/reviews - List reviews
PUT /api/v1/cms/sites/{id}/reviews/{reviewId}/approve - Approve review
PUT /api/v1/cms/sites/{id}/reviews/{reviewId}/reject - Reject review
```

### 11. CMS/SEO/Index.vue

**Purpose:** SEO management

**Features:**
- Generate sitemap
- Generate robots.txt
- View structured data
- Edit meta tags

**API Calls:**
```javascript
GET /api/v1/cms/sites/{id}/seo/sitemap - Generate sitemap
GET /api/v1/cms/sites/{id}/seo/robots - Generate robots.txt
GET /api/v1/cms/sites/{id}/seo/structured-data - Get structured data
```

## Complete Data Flow

### Site Creation Flow

```
User creates site
    ↓
CMSService::createSite()
    ├─→ Create Site
    │   ├─→ Set status = DRAFT
    │   ├─→ Set settings
    │   └─→ Set published_at = null
    ├─→ Dispatch SiteCreated event
    └─→ Return Site
```

### Page Creation Flow

```
User creates page
    ↓
CMSService::createPage()
    ├─→ Check if is_homepage
    │   └─→ Unset other homepages if true
    ├─→ Create Page
    │   ├─→ Set status = DRAFT
    │   ├─→ Set slug
    │   └─→ Set sort_order
    ├─→ Dispatch PageCreated event
    └─→ Return Page
```

### Page Publishing Flow

```
User publishes page
    ↓
CMSService::publishPage()
    ├─→ Update Page
    │   ├─→ Set status = PUBLISHED
    │   └─→ Set published_at = now()
    ├─→ Dispatch PagePublished event
    └─→ Return Page
```

### Cart to Order Flow

```
User places order
    ↓
CartService::convertToOrder()
    ├─→ Validate cart not empty
    ├─→ Generate order number
    ├─→ Create PublicOrder
    │   ├─→ Set status = PENDING
    │   ├─→ Set payment_status = PENDING
    │   ├─→ Set placed_at = now()
    │   └─→ Set customer details
    ├─→ Create PublicOrderItems
    │   └─→ Copy cart items to order items
    ├─→ Clear cart
    ├─→ Dispatch OrderPlaced event
    └─→ Return Order
```

### Contact Submission Flow

```
User submits contact form
    ↓
ContactService::createSubmission()
    ├─→ Create ContactSubmission
    │   ├─→ Set status = NEW
    │   ├─→ Set source
    │   ├─→ Set ip_address
    │   └─→ Set user_agent
    ├─→ Send notification email
    └─→ Return ContactSubmission
```

## Integration with Other Domains

### Product Domain

**Product Catalog Integration:**
```php
CartItem Model
  ├─→ product_id -> Product
  └─→ product_variant_id -> ProductVariant

ProductReview Model
  └─→ product_id -> Product

Section Model (Product Grid)
  └─→ content['products'] -> Product[]
```

**Product Display:**
```
Product Grid Section
    ↓
PublicSiteService::getPageBySlug()
    ├─→ Load Product Grid section
    ├─→ Get product IDs from content
    ├─→ Load products from Product domain
    └─-> Return products with pricing
```

### Customer Domain

**Customer Account Integration:**
```php
CustomerAccount Model
  └─→ Links to Customer domain for billing/shipping

PublicOrder Model
  ├─→ customer_email -> Customer.email
  ├─→ customer_first_name -> Customer.first_name
  ├─-> customer_last_name -> Customer.last_name
```

**Order Sync:**
```
PublicOrder Placed
    ↓
ERPIntegrationService::syncOrderToERP()
    ├─→ Create SalesOrder
    │   ├─→ Copy customer details
    │   ├─-> Copy items
    │   └─→ Set status
    ├─→ Create Invoice
    │   ├─-> Copy order details
    │   └─-> Set total_amount
    └─-> Dispatch OrderSynced event
```

### Accounting Domain

**Order to Invoice Flow:**
```
PublicOrder Completed
    ↓
ERPIntegrationService::createInvoice()
    ├─→ Create Invoice
    │   ├─-> Copy order details
    │   ├─-> Set customer_id
    │   └─-> Set total_amount
    ├─→ Create Invoice Items
    │   └─-> Copy order items
    └─-> Return Invoice

Invoice Paid
    ↓
AccountingService::journalForInvoice()
    ├─→ DR: Accounts Receivable
    ├─-> CR: Sales Revenue
    └─-> CR: Output VAT Payable
```

### CRM Domain

**Lead Generation:**
```
Contact Submission Created
    ↓
CRMService::createLead()
    ├─→ Create Lead
    │   ├─-> Copy contact details
    │   ├─-> Set source = website
    │   └─-> Set status = NEW
    ├─→ Assign to sales rep
    └─-> Return Lead
```

## Comparison with Modern CMS

### Features Comparison

| Feature | This System | WordPress | Shopify | Wix |
|---------|-------------|-----------|---------|-----|
| **Multi-tenancy** | ✅ | ⚠️ | ❌ | ❌ |
| **Page Builder** | ✅ | ✅ | ✅ | ✅ |
| **Blog** | ✅ | ✅ | ✅ | ✅ |
| **E-commerce** | ✅ | ⚠️ | ✅ | ✅ |
| **Shopping Cart** | ✅ | ⚠️ | ✅ | ✅ |
| **Product Reviews** | ✅ | ⚠️ | ✅ | ✅ |
| **Wishlist** | ✅ | ⚠️ | ✅ | ✅ |
| **SEO** | ✅ | ✅ | ✅ | ✅ |
| **Sitemap** | ✅ | ✅ | ✅ | ✅ |
| **Robots.txt** | ✅ | ✅ | ✅ | ✅ |
| **Structured Data** | ✅ | ✅ | ✅ | ✅ |
| **Contact Forms** | ✅ | ✅ | ✅ | ✅ |
| **Menu Builder** | ✅ | ✅ | ✅ | ✅ |
| **Multi-language** | ⚠️ | ✅ | ✅ | ✅ |
| **Theme System** | ⚠️ | ✅ | ✅ | ✅ |
| **Plugin System** | ⚠️ | ✅ | ✅ | ✅ |
| **App Store** | ❌ | ✅ | ✅ | ✅ |
| **API** | ✅ | ✅ | ✅ | ✅ |
| **Headless CMS** | ⚠️ | ✅ | ✅ | ✅ |
| **CDN Integration** | ⚠️ | ✅ | ✅ | ✅ |
| **Image Optimization** | ⚠️ | ✅ | ✅ | ✅ |
| **Page Caching** | ⚠️ | ✅ | ✅ | ✅ |

### Workflow Comparison

**This System:**
```
Site: DRAFT → PUBLISHED → MAINTENANCE
Page: DRAFT → PUBLISHED → SCHEDULED → ARCHIVED
BlogPost: DRAFT → PUBLISHED → SCHEDULED → ARCHIVED
Order: PENDING → PROCESSING → COMPLETED/CANCELLED
```

**WordPress:**
```
Page: DRAFT → PUBLISHED → PRIVATE
Post: DRAFT → PUBLISHED → PRIVATE
Order: PENDING → PROCESSING → COMPLETED/CANCELLED/REFUNDED
```

**Shopify:**
```
Page: DRAFT → PUBLISHED → ARCHIVED
BlogPost: DRAFT → PUBLISHED
Order: PENDING → PROCESSING → COMPLETED/CANCELLED/REFUNDED
```

### Unique Features

**This System:**
- Multi-tenancy support
- ERP integration (sales orders, invoices)
- CRM integration (lead generation)
- Bangladesh localization (BDT)
- Page builder with sections
- Contact form with assignment
- Product reviews with approval
- Wishlist functionality
- SEO optimization (sitemap, robots.txt, structured data)
- Shopping cart with session/customer support

**WordPress/Shopify/Wix:**
- Plugin ecosystem
- Theme marketplace
- App store
- Multi-language support
- CDN integration
- Image optimization
- Page caching
- Headless CMS support

## API Reference

### Sites

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/cms/sites` | List sites | Required |
| GET | `/api/v1/cms/sites/{id}` | Get site | Required |
| POST | `/api/v1/cms/sites` | Create site | Required |
| PUT | `/api/v1/cms/sites/{id}` | Update site | Required |
| DELETE | `/api/v1/cms/sites/{id}` | Delete site | Required |
| POST | `/api/v1/cms/sites/{id}/publish` | Publish site | Required |
| POST | `/api/v1/cms/sites/{id}/unpublish` | Unpublish site | Required |

### Pages

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/cms/sites/{id}/pages` | List pages | Required |
| GET | `/api/v1/cms/sites/{id}/pages/{pageId}` | Get page | Required |
| POST | `/api/v1/cms/sites/{id}/pages` | Create page | Required |
| PUT | `/api/v1/cms/sites/{id}/pages/{pageId}` | Update page | Required |
| DELETE | `/api/v1/cms/sites/{id}/pages/{pageId}` | Delete page | Required |
| POST | `/api/v1/cms/sites/{id}/pages/{pageId}/publish` | Publish page | Required |

### Sections

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/cms/sites/{id}/pages/{pageId}/sections` | List sections | Required |
| POST | `/api/v1/cms/sites/{id}/pages/{pageId}/sections` | Create section | Required |
| PUT | `/api/v1/cms/sites/{id}/pages/{pageId}/sections/{sectionId}` | Update section | Required |
| DELETE | `/api/v1/cms/sites/{id}/pages/{pageId}/sections/{sectionId}` | Delete section | Required |

### Blog Posts

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/cms/sites/{id}/blog-posts` | List blog posts | Required |
| GET | `/api/v1/cms/sites/{id}/blog-posts/{postId}` | Get blog post | Required |
| POST | `/api/v1/cms/sites/{id}/blog-posts` | Create blog post | Required |
| PUT | `/api/v1/cms/sites/{id}/blog-posts/{postId}` | Update blog post | Required |
| DELETE | `/api/v1/cms/sites/{id}/blog-posts/{postId}` | Delete blog post | Required |

### Menus

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/cms/sites/{id}/menus` | List menus | Required |
| POST | `/api/v1/cms/sites/{id}/menus` | Create menu | Required |
| PUT | `/api/v1/cms/sites/{id}/menus/{menuId}` | Update menu | Required |
| DELETE | `/api/v1/cms/sites/{id}/menus/{menuId}` | Delete menu | Required |

### Cart

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/public/sites/{id}/cart` | Get cart | Optional |
| POST | `/api/v1/public/sites/{id}/cart/items` | Add item to cart | Optional |
| PUT | `/api/v1/public/sites/{id}/cart/items/{itemId}` | Update item | Optional |
| DELETE | `/api/v1/public/sites/{id}/cart/items/{itemId}` | Remove item | Optional |
| POST | `/api/v1/public/sites/{id}/cart/checkout` | Convert to order | Optional |

### Contact Submissions

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/cms/sites/{id}/contact-submissions` | List submissions | Required |
| GET | `/api/v1/cms/sites/{id}/contact-submissions/{submissionId}` | Get submission | Required |
| POST | `/api/v1/public/sites/{id}/contact` | Submit contact form | Optional |
| PUT | `/api/v1/cms/sites/{id}/contact-submissions/{submissionId}/contact` | Mark as contacted | Required |
| PUT | `/api/v1/cms/sites/{id}/contact-submissions/{submissionId}/resolve` | Mark as resolved | Required |
| PUT | `/api/v1/cms/sites/{id}/contact-submissions/{submissionId}/spam` | Mark as spam | Required |

### SEO

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/public/sites/{id}/sitemap.xml` | Get sitemap | Optional |
| GET | `/api/v1/public/sites/{id}/robots.txt` | Get robots.txt | Optional |
| GET | `/api/v1/public/sites/{id}/seo/structured-data` | Get structured data | Optional |

### Query Parameters (Index)

```
status -> Filter by status
search -> Filter by title, slug
sort_by -> Sort field
sort_order -> Sort order (asc/desc)
per_page -> Pagination (default: 15)
page -> Page number
```

### Request Body (Create Site)

```json
{
  "name": "My Website",
  "slug": "my-website",
  "domain": "example.com",
  "subdomain": "my-website",
  "logo": "https://example.com/logo.png",
  "favicon": "https://example.com/favicon.ico",
  "primary_color": "#3b82f6",
  "accent_color": "#10b981",
  "font_family": "Inter",
  "seo_title": "My Website",
  "seo_description": "My website description",
  "google_analytics_id": "UA-123456789",
  "facebook_pixel_id": "123456789",
  "settings": {}
}
```

### Response Format

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "My Website",
    "slug": "my-website",
    "domain": "example.com",
    "subdomain": "my-website",
    "status": "draft",
    "logo_url": "https://example.com/logo.png",
    "favicon_url": "https://example.com/favicon.ico",
    "theme": "default",
    "meta_title": "My Website",
    "meta_description": "My website description",
    "settings": {},
    "pages_count": 0,
    "blog_posts_count": 0
  },
  "message": "Site created"
}
```

## Frontend API Integration

### CMS/Sites/Index.vue

```javascript
const fetchSites = async (page = 1) => {
  const response = await get('/cms/sites', { page, per_page: 15 })
  sites.value = response.data
  pagination.value = response.meta
}

const createSite = async () => {
  const data = {
    name: form.value.name,
    slug: form.value.slug,
    domain: form.value.domain,
    subdomain: form.value.subdomain,
    primary_color: form.value.primary_color,
    accent_color: form.value.accent_color,
  }
  
  await post('/cms/sites', data)
  await fetchSites()
}
```

### CMS/Pages/Index.vue

```javascript
const fetchPages = async (siteId, page = 1) => {
  const response = await get(`/cms/sites/${siteId}/pages`, { page, per_page: 15 })
  pages.value = response.data
  pagination.value = response.meta
}

const createPage = async (siteId) => {
  const data = {
    title: form.value.title,
    slug: form.value.slug,
    seo_title: form.value.seo_title,
    seo_description: form.value.seo_description,
    status: form.value.status,
    is_homepage: form.value.is_homepage,
  }
  
  await post(`/cms/sites/${siteId}/pages`, data)
  await fetchPages(siteId)
}
```

## Summary

### Backend Coverage
- ✅ Site model (multi-tenancy, status, theme, SEO settings)
- ✅ Page model (status, homepage, SEO, sections)
- ✅ BlogPost model (author, category, views, reading time)
- ✅ Section model (page builder, section types, content JSON)
- ✅ Menu model (navigation, nested items)
- ✅ ContactSubmission model (status, assignment, tracking)
- ✅ ShoppingCart model (session/customer support, expiration)
- ✅ CartItem model (product/variant, quantity, pricing)
- ✅ CustomerAccount model (guest/customer, authentication)
- ✅ PublicOrder model (order workflow, payment, shipping)
- ✅ ProductReview model (rating, approval, verified purchase)
- ✅ Wishlist model (customer items)
- ✅ CMSService (site/page management, publishing)
- ✅ PublicSiteService (public rendering, tenant detection)
- ✅ SEOService (sitemap, robots.txt, structured data)
- ✅ CartService (cart management, order conversion)
- ✅ Multi-tenancy support

### Frontend Coverage
- ✅ CMS/Dashboard.vue (metrics, recent pages, recent blog posts)
- ✅ CMS/Sites/Index.vue (list, create, edit, delete)
- ✅ CMS/Pages/Index.vue (list, create, edit, delete, builder)
- ✅ CMS/Pages/Create.vue (create page form)
- ✅ CMS/PageBuilder/Index.vue (drag-drop sections)
- ✅ CMS/Blog/Index.vue (list, create, edit, delete)
- ✅ CMS/Menus/Index.vue (list, create, edit, delete)
- ✅ CMS/Menus/Builder.vue (drag-drop menu items)
- ✅ CMS/Contacts/Index.vue (list, assign, mark contacted/resolved/spam)
- ✅ CMS/Reviews/Index.vue (list, approve, reject)
- ✅ CMS/SEO/Index.vue (sitemap, robots.txt, structured data)

### Integration
- ✅ Product Domain (product catalog, product reviews, product grid sections)
- ✅ Customer Domain (customer account sync, billing/shipping)
- ✅ Accounting Domain (order to invoice, journal entries)
- ✅ CRM Domain (contact submissions to lead generation)
- ✅ Multi-tenancy (company isolation)
- ✅ Bangladesh localization (BDT)
- ✅ SEO optimization (sitemap, robots.txt, structured data)
- ✅ E-commerce (shopping cart, orders, reviews, wishlist)

The CMS system provides **comprehensive content management** with multi-tenancy support, page builder, e-commerce functionality, and tight integration to product, customer, accounting, and CRM domains.

## Backend Architecture
- **Site Model** - Multi-tenancy, status (DRAFT → PUBLISHED → MAINTENANCE), theme, SEO settings, Google Analytics, Facebook Pixel
- **Page Model** - Status (DRAFT → PUBLISHED → SCHEDULED → ARCHIVED), homepage flag, SEO, sections
- **BlogPost Model** - Author, category, views count, reading time calculation
- **Section Model** - Page builder with 13 section types (Hero Banner, Text Block, Product Grid, Portfolio Grid, Team Grid, Stats, FAQ, CTA Banner, Contact Form, Gallery, Testimonials, Blog Posts, Custom HTML)
- **Menu Model** - Navigation with nested items, location-based menus
- **ContactSubmission Model** - Status (NEW → CONTACTED → RESOLVED/SPAM), assignment, tracking
- **ShoppingCart Model** - Session/customer support, expiration, item management
- **CustomerAccount Model** - Guest/customer authentication, orders, carts
- **PublicOrder Model** - Order workflow (PENDING → PROCESSING → COMPLETED/CANCELLED), payment status (PENDING → PAID/FAILED/REFUNDED), billing/shipping addresses
- **ProductReview Model** - Rating (1-5), approval, verified purchase, helpful count
- **Wishlist Model** - Customer items management

## Services
- **CMSService:** create/update/publish sites, create/update/publish pages, find by subdomain
- **PublicSiteService:** find site by tenant, get site data, get page by slug, get homepage
- **SEOService:** generate sitemap XML, generate robots.txt, generate structured data (Organization, WebSite, WebPage, Article schemas)
- **CartService:** get/create cart, add/update/remove items, convert to order

## Data Flows
- **Site Creation:** Create site → Set status = DRAFT → Dispatch SiteCreated event → Return site
- **Page Creation:** Check homepage → Unset other homepages → Create page → Dispatch PageCreated event
- **Page Publishing:** Update status = PUBLISHED → Set published_at → Dispatch PagePublished event
- **Cart to Order:** Validate cart → Generate order number → Create order/items → Clear cart → Dispatch OrderPlaced event
- **Contact Submission:** Create submission → Set status = NEW → Send notification email → Return submission

## Integration
- **Product Domain:** Product catalog, product reviews, product grid sections (load products from Product domain)
- **Customer Domain:** Customer account sync, billing/shipping addresses
- **Accounting Domain:** Order to invoice → Journal entries (DR: Accounts Receivable, CR: Sales Revenue/Output VAT)
- **CRM Domain:** Contact submissions → Lead generation (set source = website, status = NEW)

## Frontend Architecture
- **CMS/Dashboard.vue** - Metrics (sites, pages, blog posts, media files), recent pages, recent blog posts
- **CMS/Sites/Index.vue** - List, create, edit, delete sites (name, domain, status, pages count)
- **CMS/Pages/Index.vue** - List, create, edit, delete pages (title, URL, status, template)
- **CMS/PageBuilder/Index.vue** - Drag-drop sections, 13 section types
- **CMS/Blog/Index.vue** - List, create, edit, delete blog posts (title, category, author, status)
- **CMS/Menus/Index.vue** - List, create, edit, delete menus
- **CMS/Menus/Builder.vue** - Drag-drop menu items, nested items
- **CMS/Contacts/Index.vue** - List, assign, mark contacted/resolved/spam
- **CMS/Reviews/Index.vue** - List, approve, reject product reviews
- **CMS/SEO/Index.vue** - Generate sitemap, robots.txt, structured data

## Comparison with Modern CMS
- **Similar:** Page builder, blog, e-commerce, shopping cart, product reviews, wishlist, SEO (sitemap, robots.txt, structured data), contact forms, menu builder
- **Simpler:** No multi-language, no theme system, no plugin system, no app store, no headless CMS, no CDN integration, no image optimization, no page caching
- **Unique:** Multi-tenancy support, ERP integration (sales orders, invoices), CRM integration (lead generation), Bangladesh localization (BDT), page builder with sections, contact form with assignment, product reviews with approval, shopping cart with session/customer support