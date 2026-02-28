<?php

namespace App\Models\Scopes;

use App\Services\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filters queries by active branch when branch context is set.
 */
class BranchScope implements Scope
{
    /** @param Builder<Model> $builder */
    public function apply(Builder $builder, Model $model): void
    {
        if (BranchContext::isFiltered()) {
            $builder->where($model->getTable().'.branch_id', BranchContext::activeId());
        }
    }
}
