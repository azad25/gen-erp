<?php

namespace App\Domain\SalesOrder\Policies;

use App\Domain\SalesOrder\Models\SalesOrder;
use App\Models\User;
use App\Support\Enums\SalesOrderStatus;

class SalesOrderPolicy
{
    /**
     * Determine whether the user can view any sales orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('sales_order.view');
    }

    /**
     * Determine whether the user can view the sales order.
     */
    public function view(User $user, SalesOrder $salesOrder): bool
    {
        return $user->company_id === $salesOrder->company_id
            && $user->hasPermission('sales_order.view');
    }

    /**
     * Determine whether the user can create sales orders.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('sales_order.create');
    }

    /**
     * Determine whether the user can update the sales order.
     */
    public function update(User $user, SalesOrder $salesOrder): bool
    {
        return $user->company_id === $salesOrder->company_id
            && $user->hasPermission('sales_order.edit')
            && $salesOrder->status === SalesOrderStatus::DRAFT;
    }

    /**
     * Determine whether the user can confirm the sales order.
     */
    public function confirm(User $user, SalesOrder $salesOrder): bool
    {
        return $user->company_id === $salesOrder->company_id
            && $user->hasPermission('sales_order.confirm')
            && $salesOrder->status === SalesOrderStatus::DRAFT;
    }

    /**
     * Determine whether the user can cancel the sales order.
     */
    public function cancel(User $user, SalesOrder $salesOrder): bool
    {
        return $user->company_id === $salesOrder->company_id
            && $user->hasPermission('sales_order.cancel')
            && $salesOrder->status !== SalesOrderStatus::CANCELLED;
    }

    /**
     * Determine whether the user can convert the sales order to invoice.
     */
    public function convertToInvoice(User $user, SalesOrder $salesOrder): bool
    {
        return $user->company_id === $salesOrder->company_id
            && $user->hasPermission('sales_order.convert_to_invoice')
            && in_array($salesOrder->status, [SalesOrderStatus::CONFIRMED, SalesOrderStatus::PROCESSING]);
    }

    /**
     * Determine whether the user can delete the sales order.
     */
    public function delete(User $user, SalesOrder $salesOrder): bool
    {
        return $user->company_id === $salesOrder->company_id
            && $user->hasPermission('sales_order.delete')
            && in_array($salesOrder->status, [SalesOrderStatus::DRAFT, SalesOrderStatus::CANCELLED]);
    }
}