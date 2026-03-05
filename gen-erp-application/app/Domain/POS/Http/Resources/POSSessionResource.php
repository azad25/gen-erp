<?php

namespace App\Domain\POS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class POSSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'branch' => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ],
            'opened_by' => [
                'id' => $this->openedBy->id,
                'name' => $this->openedBy->name,
            ],
            'closed_by' => $this->closedBy ? [
                'id' => $this->closedBy->id,
                'name' => $this->closedBy->name,
            ] : null,
            'opening_cash' => $this->opening_cash,
            'closing_cash' => $this->closing_cash,
            'expected_cash' => $this->expected_cash,
            'cash_difference' => $this->cash_difference,
            'status' => $this->status,
            'opened_at' => $this->opened_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'notes' => $this->notes,
            'sales_count' => $this->whenLoaded('sales', fn() => $this->sales->count()),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
