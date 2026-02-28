<?php

namespace App\Enums;

/**
 * Document types that can have workflow definitions attached.
 */
enum WorkflowDocumentType: string
{
    case PURCHASE_ORDER = 'purchase_order';
    case SALES_ORDER = 'sales_order';
    case EXPENSE_CLAIM = 'expense_claim';
    case LEAVE_REQUEST = 'leave_request';
    case PAYROLL_RUN = 'payroll_run';
    case CREDIT_NOTE = 'credit_note';
    case SALARY_ADVANCE = 'salary_advance';
    case STOCK_TRANSFER = 'stock_transfer';
    case PAYMENT_REQUEST = 'payment_request';

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE_ORDER => __('Purchase Order'),
            self::SALES_ORDER => __('Sales Order'),
            self::EXPENSE_CLAIM => __('Expense Claim'),
            self::LEAVE_REQUEST => __('Leave Request'),
            self::PAYROLL_RUN => __('Payroll Run'),
            self::CREDIT_NOTE => __('Credit Note'),
            self::SALARY_ADVANCE => __('Salary Advance'),
            self::STOCK_TRANSFER => __('Stock Transfer'),
            self::PAYMENT_REQUEST => __('Payment Request'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
