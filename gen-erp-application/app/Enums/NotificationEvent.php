<?php

namespace App\Enums;

/**
 * All system notification events.
 */
enum NotificationEvent: string
{
    case INVOICE_SENT = 'invoice_sent';
    case PAYMENT_RECEIVED = 'payment_received';
    case LEAVE_APPROVED = 'leave_approved';
    case LEAVE_REJECTED = 'leave_rejected';
    case LOW_STOCK = 'low_stock';
    case EXPIRY_ALERT = 'expiry_alert';
    case PAYROLL_APPROVED = 'payroll_approved';
    case PO_APPROVED = 'po_approved';
    case INVITATION_SENT = 'invitation_sent';
    case PAYMENT_VERIFIED = 'payment_verified';
    case WORKFLOW_TRANSITION = 'workflow_transition';
    case ACCOUNT_LOCKED = 'account_locked';
    case IMPORT_COMPLETED = 'import_completed';

    public function label(): string
    {
        return match ($this) {
            self::INVOICE_SENT => __('Invoice Sent'),
            self::PAYMENT_RECEIVED => __('Payment Received'),
            self::LEAVE_APPROVED => __('Leave Approved'),
            self::LEAVE_REJECTED => __('Leave Rejected'),
            self::LOW_STOCK => __('Low Stock Alert'),
            self::EXPIRY_ALERT => __('Expiry Alert'),
            self::PAYROLL_APPROVED => __('Payroll Approved'),
            self::PO_APPROVED => __('Purchase Order Approved'),
            self::INVITATION_SENT => __('Invitation Sent'),
            self::PAYMENT_VERIFIED => __('Payment Verified'),
            self::WORKFLOW_TRANSITION => __('Workflow Transition'),
            self::ACCOUNT_LOCKED => __('Account Locked'),
            self::IMPORT_COMPLETED => __('Import Completed'),
        };
    }

    /**
     * @return array<int, string>
     */
    public function availableVariables(): array
    {
        return match ($this) {
            self::INVOICE_SENT => ['{invoice_number}', '{customer_name}', '{total_amount}', '{due_date}'],
            self::PAYMENT_RECEIVED => ['{receipt_number}', '{customer_name}', '{amount}'],
            self::LEAVE_APPROVED, self::LEAVE_REJECTED => ['{employee_name}', '{leave_type}', '{from_date}', '{to_date}'],
            self::LOW_STOCK => ['{product_name}', '{current_stock}', '{threshold}'],
            self::EXPIRY_ALERT => ['{product_name}', '{batch_number}', '{expiry_date}'],
            self::PAYROLL_APPROVED => ['{run_number}', '{period}', '{total_amount}'],
            self::PO_APPROVED => ['{po_number}', '{supplier_name}', '{total_amount}'],
            self::INVITATION_SENT => ['{invitee_email}', '{company_name}'],
            self::PAYMENT_VERIFIED => ['{payment_number}', '{amount}'],
            self::WORKFLOW_TRANSITION => ['{document_type}', '{document_number}', '{from_status}', '{to_status}'],
            self::ACCOUNT_LOCKED => ['{user_email}', '{reason}'],
            self::IMPORT_COMPLETED => ['{entity_type}', '{total_rows}', '{created_rows}', '{failed_rows}'],
        };
    }

    /**
     * @return array<int, string>
     */
    public function defaultChannels(): array
    {
        return match ($this) {
            self::INVOICE_SENT => ['in_app', 'email'],
            self::PAYMENT_RECEIVED => ['in_app', 'email'],
            self::LOW_STOCK => ['in_app'],
            self::ACCOUNT_LOCKED => ['email'],
            default => ['in_app'],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->toArray();
    }
}
