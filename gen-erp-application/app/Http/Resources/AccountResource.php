<?php

namespace App\Http\Resources;

use App\Domain\Accounting\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Account
 */
class AccountResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'account_type' => $this->account_type,
            'sub_type' => $this->sub_type,
            'opening_balance' => $this->opening_balance,
            'opening_balance_date' => $this->opening_balance_date,
            'current_balance' => $this->currentBalance(),
            'formatted_balance' => $this->formattedBalance(),
            'normal_balance_side' => $this->normalBalanceSide(),
            'is_system' => $this->is_system,
            'is_active' => $this->is_active,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'account_group' => new AccountGroupResource($this->whenLoaded('accountGroup')),
        ];
    }
}