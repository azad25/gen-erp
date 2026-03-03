<?php

namespace App\Http\Resources;

use App\Domain\Accounting\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Expense
 */
class ExpenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'expense_date' => $this->expense_date,
            'category' => $this->category,
            'description' => $this->description,
            'amount' => $this->amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
            'status' => $this->status,
            'custom_fields' => $this->custom_fields,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'account' => new AccountResource($this->whenLoaded('account')),
            'payment_account' => new AccountResource($this->whenLoaded('paymentAccount')),
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}