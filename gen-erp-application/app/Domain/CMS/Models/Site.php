<?php

namespace App\Domain\CMS\Models;

use App\Domain\Auth\Models\Company;
use App\Domain\CMS\Models\Page;
use App\Domain\CMS\Models\Menu;
use App\Domain\CMS\Models\BlogPost;
use App\Domain\CMS\Models\BlogCategory;
use App\Domain\CMS\Models\CustomerAccount;
use App\Domain\CMS\Models\ContactSubmission;
use App\Domain\CMS\Enums\SiteStatus;
use Database\Factories\Domain\CMS\SiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a CMS site for a tenant company.
 */
class Site extends Model
{
    use HasFactory;

    protected $table = 'cms_sites';

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'domain',
        'subdomain',
        'logo',
        'favicon',
        'primary_color',
        'accent_color',
        'font_family',
        'status',
        'seo_title',
        'seo_description',
        'seo_image',
        'google_analytics_id',
        'facebook_pixel_id',
        'settings',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SiteStatus::class,
            'settings' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SiteFactory
    {
        return SiteFactory::new();
    }

    // ── Relationships ────────────────────────────────────────

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<Page, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    /**
     * @return HasMany<Menu, $this>
     */
    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    /**
     * @return HasMany<BlogPost, $this>
     */
    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    /**
     * @return HasMany<BlogCategory, $this>
     */
    public function blogCategories(): HasMany
    {
        return $this->hasMany(BlogCategory::class);
    }

    /**
     * @return HasMany<CustomerAccount, $this>
     */
    public function customerAccounts(): HasMany
    {
        return $this->hasMany(CustomerAccount::class);
    }

    /**
     * @return HasMany<ContactSubmission, $this>
     */
    public function contactSubmissions(): HasMany
    {
        return $this->hasMany(ContactSubmission::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    /**
     * Scope to only published sites.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Site>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Site>
     */
    public function scopePublished(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', SiteStatus::PUBLISHED);
    }

    /**
     * Scope by subdomain.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Site>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Site>
     */
    public function scopeBySubdomain(\Illuminate\Database\Eloquent\Builder $query, string $subdomain): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('subdomain', $subdomain);
    }

    /**
     * Scope by custom domain.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Site>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Site>
     */
    public function scopeByDomain(\Illuminate\Database\Eloquent\Builder $query, string $domain): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('domain', $domain);
    }

    // ── Methods ──────────────────────────────────────────────

    /**
     * Get the homepage for this site.
     */
    public function homepage(): ?Page
    {
        return $this->pages()
            ->where('is_homepage', true)
            ->first();
    }

    /**
     * Get the site URL.
     */
    public function getUrl(): string
    {
        if ($this->domain) {
            return "https://{$this->domain}";
        }

        return "https://{$this->subdomain}.yourplatform.com";
    }

    /**
     * Check if site is published.
     */
    public function isPublished(): bool
    {
        return $this->status === SiteStatus::PUBLISHED;
    }
}