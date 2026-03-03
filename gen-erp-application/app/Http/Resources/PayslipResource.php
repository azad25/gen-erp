<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\HR\Models\Payslip
 */
class PayslipResource extends JsonResource
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
            'employee_id' => $this->employee_id,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'month' => $this->month,
            'year' => $this->year,
            'basic_salary' => $this->basic_salary,
            'gross_salary' => $this->gross_salary,
            'total_deductions' => $this->total_deductions,
            'net_salary' => $this->net_salary,
            'earnings' => PayslipEarningResource::collection($this->whenLoaded('earnings')),
            'deductions' => PayslipDeductionResource::collection($this->whenLoaded('deductions')),
            'status' => $this->status,
            'generated_at' => $this->generated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}