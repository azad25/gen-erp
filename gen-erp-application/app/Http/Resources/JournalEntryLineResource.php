<?php

namespace App\Http\Resources;

use App\Domain\Accounting\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JournalEntryLine
 */
class JournalEntryLineResource extends JsonResource
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
            'account_id' => $this->account_id,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'account' => new AccountResource($this->whenLoaded('account')),
            'journal_entry' => new JournalEntryResource($this->whenLoaded('journalEntry')),
        ];
    }
}