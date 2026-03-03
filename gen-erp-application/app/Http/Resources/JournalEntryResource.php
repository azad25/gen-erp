<?php

namespace App\Http\Resources;

use App\Domain\Accounting\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JournalEntry
 */
class JournalEntryResource extends JsonResource
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
            'reference' => $this->reference,
            'description' => $this->description,
            'entry_date' => $this->entry_date,
            'status' => $this->status,
            'total_debit' => $this->total_debit,
            'total_credit' => $this->total_credit,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'lines' => JournalEntryLineResource::collection($this->whenLoaded('lines')),
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}