<?php

namespace App\Domain\CMS\Models;

use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a navigation menu for a CMS site.
 */
class Menu extends Model
{
    use HasFactory;

    protected $table = 'cms_menus';

    protected $fillable = [
        'site_id',
        'name',
        'location',
    ];

    // ── Relationships ────────────────────────────────────────

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function rootItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->orderBy('sort_order');
    }

    // ── Methods ──────────────────────────────────────────────

    /**
     * Get menu items as nested array.
     */
    public function getNestedItems(): array
    {
        $items = $this->rootItems()->with('children')->get();
        
        return $items->map(function ($item) {
            return $this->formatMenuItem($item);
        })->toArray();
    }

    /**
     * Format menu item with children.
     */
    private function formatMenuItem(MenuItem $item): array
    {
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
}