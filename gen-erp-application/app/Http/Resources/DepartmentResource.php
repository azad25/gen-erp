<?php

namespace App\Http\Resources;

use App\Domain\HR\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Department
 */
class DepartmentResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'parent_id' => $this->parent_id,
            'manager_id' => $this->manager_id,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'parent' => new self($this->whenLoaded('parent')),
            'children' => self::collection($this->whenLoaded('children')),
            'manager' => new EmployeeResource($this->whenLoaded('manager')),
            'employees' => EmployeeResource::collection($this->whenLoaded('employees')),
            'designations' => DesignationResource::collection($this->whenLoaded('designations')),
        ];
    }
}