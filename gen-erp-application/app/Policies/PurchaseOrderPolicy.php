<?php

namespace App\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\Purchase\Models\PurchaseOrder;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view purchase orders');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermissionTo('view purchase orders') && 
               $purchaseOrder->company_id === session('active_company_id');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create purchase orders');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermissionTo('edit purchase orders') && 
               $purchaseOrder->company_id === session('active_company_id');
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermissionTo('delete purchase orders') && 
               $purchaseOrder->company_id === session('active_company_id');
    }
}