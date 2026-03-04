<?php

namespace App\Domain\CMS\Models;

use App\Domain\CMS\Models\Menu;
use App\Domain\CMS\Models\Page;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a menu item within a navigation menu.
 */
class MenuItem extends Model
{
    use HasFactory;

    protected $table = 'cms_menu_items';

    protected $fillable = [
        'menu_id',
        'parent_id',
        'label',
        'url',
        'page_id',
        'target',
        'sort_order',
    ];

    // ── Relationships ────────────────────────────────────────

    /**
     * @return BelongsTo<Menu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * @return BelongsTo<MenuItem, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    // ── Methods ──────────────────────────────────────────────

    /**
     * Get the menu item URL.
     */
    public function getUrl(): string
    {
        if ($this->page_id && $this->page) {
            return $this->page->getUrl();
        }

        if ($this->url) {
            // If it's a relative URL, make it absolute
            if (str_starts_with($this->url, '/')) {
                return $this->menu->site->getUrl() . $this->url;
            }
            
            return $this->url;
        }

        return '#';
    }

    /**
     * Check if menu item is active for given path.
     */
    public function isActive(string $currentPath): bool
    {
        $itemUrl = $this->getUrl();
        $itemPath = parse_url($itemUrl, PHP_URL_PATH) ?: '/';
        
        return $itemPath === $currentPath;
    }

    /**
     * Check if menu item has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }
}