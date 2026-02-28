<?php

namespace App\Models\Traits;

use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Automatically scopes all queries to the active company and sets company_id on creation.
 *
 * Apply this trait to any model that holds tenant-scoped business data.
 */
trait BelongsToCompany
{
    /**
     * Boot the trait: add global scope and creating hook.
     */
    public static function bootBelongsToCompany(): void
    {
        // Global scope: automatically filter all queries by active company
        static::addGlobalScope('company', function (Builder $builder): void {
            if (CompanyContext::hasActive()) {
                $builder->where(
                    $builder->getModel()->getTable().'.company_id',
                    CompanyContext::activeId()
                );
            }
        });

        // Creating hook: auto-set company_id from active context
        static::creating(function (Model $model): void {
            if (empty($model->company_id)) {
                if (! CompanyContext::hasActive()) {
                    throw new RuntimeException(
                        'Cannot create '.class_basename($model).' without an active company context.'
                    );
                }

                $model->company_id = CompanyContext::activeId();
            }
        });
    }

    /**
     * Relationship to the owning company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Company, $this>
     */
    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}
