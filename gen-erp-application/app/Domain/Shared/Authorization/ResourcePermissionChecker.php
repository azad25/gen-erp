<?php

namespace App\Domain\Shared\Authorization;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Advanced resource-based permission checker.
 */
class ResourcePermissionChecker
{
    /**
     * Check if user has permission on a specific resource.
     */
    public function hasResourcePermission(User $user, string $permission, Model $resource): bool
    {
        // Basic tenant isolation check
        if (!$this->belongsToSameTenant($user, $resource)) {
            return false;
        }

        // Check basic permission
        if (!$user->hasPermission($permission)) {
            return false;
        }

        // Check resource-specific conditions
        return $this->checkResourceSpecificConditions($user, $permission, $resource);
    }

    /**
     * Check if user and resource belong to the same tenant.
     */
    private function belongsToSameTenant(User $user, Model $resource): bool
    {
        if (!property_exists($resource, 'company_id')) {
            return true; // Non-tenant resource
        }

        return $user->company_id === $resource->company_id;
    }

    /**
     * Check resource-specific authorization conditions.
     */
    private function checkResourceSpecificConditions(User $user, string $permission, Model $resource): bool
    {
        $resourceType = class_basename($resource);
        $method = 'check' . $resourceType . 'Conditions';

        if (method_exists($this, $method)) {
            return $this->$method($user, $permission, $resource);
        }

        return true; // Default allow if no specific conditions
    }

    /**
     * Check invoice-specific conditions.
     */
    private function checkInvoiceConditions(User $user, string $permission, $invoice): bool
    {
        switch ($permission) {
            case 'invoice.send':
                return $invoice->status === 'draft';
            
            case 'invoice.edit':
                return in_array($invoice->status, ['draft']);
            
            case 'invoice.cancel':
                return in_array($invoice->status, ['draft', 'sent']);
            
            case 'invoice.delete':
                return false; // Invoices cannot be deleted
            
            default:
                return true;
        }
    }

    /**
     * Check sales order specific conditions.
     */
    private function checkSalesOrderConditions(User $user, string $permission, $salesOrder): bool
    {
        switch ($permission) {
            case 'sales_order.confirm':
                return $salesOrder->status === 'draft';
            
            case 'sales_order.edit':
                return in_array($salesOrder->status, ['draft']);
            
            case 'sales_order.cancel':
                return in_array($salesOrder->status, ['draft', 'confirmed']);
            
            case 'sales_order.convert_to_invoice':
                return $salesOrder->status === 'confirmed';
            
            default:
                return true;
        }
    }

    /**
     * Check if user owns the resource (created by them).
     */
    public function ownsResource(User $user, Model $resource): bool
    {
        if (!property_exists($resource, 'created_by')) {
            return false;
        }

        return $user->id === $resource->created_by;
    }

    /**
     * Check if user is in the same department as resource creator.
     */
    public function sameDepartment(User $user, Model $resource): bool
    {
        if (!property_exists($resource, 'created_by') || !$user->department_id) {
            return false;
        }

        $creator = User::find($resource->created_by);
        return $creator && $user->department_id === $creator->department_id;
    }
}