<?php

namespace App\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\Product\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view products');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('view products') && 
               $product->company_id === session('active_company_id');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create products');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('edit products') && 
               $product->company_id === session('active_company_id');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('delete products') && 
               $product->company_id === session('active_company_id');
    }
}