<?php

namespace App\Models;

use App\Enums\ExpenseStatus;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * General business expense with approval workflow.
 */
class Expense extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'account_id',
        'payment_account_id',
        'expense_number',
        'expense_date',
        'category',
        'description',
        'amount',
        'tax_amount',
        'total_amount',
        'payment_method_id',
        'reference_number',
        'receipt_url',
        'status',
        'custom_fields',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'integer',
            'tax_amount' => 'integer',
            'total_amount' => 'integer',
            'status' => ExpenseStatus::class,
            'custom_fields' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Expense $expense): void {
            if ($expense->expense_number === null || $expense->expense_number === '') {
                $seq = static::withoutGlobalScopes()
                    ->where('company_id', $expense->company_id)
                    ->count() + 1;
                $expense->expense_number = 'EXP-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function workflowDocumentType(): string
    {
        return 'expense_claim';
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
