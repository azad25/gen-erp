<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Document\Models\Document
 */
class DocumentResource extends JsonResource
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
            'original_name' => $this->original_name,
            'file_path' => $this->file_path,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'description' => $this->description,
            'folder_id' => $this->folder_id,
            'folder' => new DocumentFolderResource($this->whenLoaded('folder')),
            'documentable_type' => $this->documentable_type,
            'documentable_id' => $this->documentable_id,
            'uploaded_by' => $this->uploaded_by,
            'uploaded_at' => $this->uploaded_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}