<?php

namespace App\Domain\Invoice\Policies;

use App\Domain\Invoice\Models\Invoice;
use App\Domain\Auth\Models\User;
use App\Support\Enums\InvoiceStatus;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('invoice.view');
    }

    /**
     * Determine whether the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->company_id === $invoice->company_id
            && $user->hasPermission('invoice.view');
    }

    /**
     * Determine whether the user can create invoices.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('invoice.create');
    }

    /**
     * Determine whether the user can update the invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return $user->company_id === $invoice->company_id
            && $user->hasPermission('invoice.edit')
            && $invoice->status === InvoiceStatus::DRAFT;
    }

    /**
     * Determine whether the user can send the invoice.
     */
    public function send(User $user, Invoice $invoice): bool
    {
        return $user->company_id === $invoice->company_id
            && $user->hasPermission('invoice.send')
            && $invoice->status === InvoiceStatus::DRAFT;
    }

    /**
     * Determine whether the user can cancel the invoice.
     */
    public function cancel(User $user, Invoice $invoice): bool
    {
        return $user->company_id === $invoice->company_id
            && $user->hasPermission('invoice.cancel')
            && $invoice->status !== InvoiceStatus::CANCELLED;
    }

    /**
     * Determine whether the user can delete the invoice.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Invoices are financial records and should not be deleted
        return false;
    }
}