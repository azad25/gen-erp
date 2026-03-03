<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\HR\Models\PayslipDeduction
 */
class PayslipDeductionResource extends JsonResource
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
            'component_name' => $this->component_name,
            'amount' => $this->amount,
            'description' => $this->description,
        ];
    }
}