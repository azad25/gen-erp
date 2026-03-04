<?php

namespace App\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\Sales\Models\Invoice;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view invoices');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('view invoices') && 
               $invoice->company_id === session('active_company_id');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create invoices');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('edit invoices') && 
               $invoice->company_id === session('active_company_id');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('delete invoices') && 
               $invoice->company_id === session('active_company_id');
    }
}