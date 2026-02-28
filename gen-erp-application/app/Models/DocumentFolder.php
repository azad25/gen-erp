<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hierarchical folder structure for organizing documents per company.
 */
class DocumentFolder extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'path',
        'created_by',
    ];

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'folder_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Build materialized path from parent chain.
     */
    public function buildPath(): string
    {
        if (! $this->parent_id) {
            return "/{$this->name}";
        }

        $parent = self::find($this->parent_id);

        return $parent ? "{$parent->path}/{$this->name}" : "/{$this->name}";
    }

    /**
     * Get breadcrumb array for folder navigation.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function breadcrumbs(): array
    {
        $crumbs = [];
        $current = $this;

        while ($current) {
            array_unshift($crumbs, ['id' => $current->id, 'name' => $current->name]);
            $current = $current->parent;
        }

        return $crumbs;
    }

    /**
     * Get total document count (recursive).
     */
    public function totalDocumentCount(): int
    {
        $count = $this->documents()->count();

        foreach ($this->children as $child) {
            $count += $child->totalDocumentCount();
        }

        return $count;
    }

    protected static function booted(): void
    {
        static::creating(function (self $folder): void {
            $folder->path = $folder->buildPath();
        });

        static::updating(function (self $folder): void {
            if ($folder->isDirty(['name', 'parent_id'])) {
                $folder->path = $folder->buildPath();
            }
        });
    }
}
