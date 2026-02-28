<?php

namespace App\Models\Traits;

use App\Models\Document;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Add to any model that needs file attachments via the Document Management System.
 *
 * Usage:
 *   $product->documents         → all attached files
 *   $product->attachDocument()  → attach a new file
 *   $product->latestDocument()  → most recent attachment
 *   $product->images()          → only image attachments
 */
trait HasDocuments
{
    /** @return MorphMany<Document, $this> */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Attach an already-uploaded Document to this entity.
     */
    public function attachDocument(Document $document): void
    {
        $document->update([
            'documentable_type' => $this->getMorphClass(),
            'documentable_id' => $this->getKey(),
        ]);
    }

    /**
     * Get the most recently uploaded document.
     */
    public function latestDocument(): ?Document
    {
        return $this->documents()->latest('uploaded_at')->first();
    }

    /**
     * Get only image documents.
     *
     * @return MorphMany<Document, $this>
     */
    public function images(): MorphMany
    {
        return $this->documents()->where('mime_type', 'like', 'image/%');
    }

    /**
     * Get primary image (first image, used as thumbnail).
     */
    public function primaryImage(): ?Document
    {
        return $this->images()->oldest('uploaded_at')->first();
    }

    /**
     * Get documents by MIME type prefix.
     *
     * @return MorphMany<Document, $this>
     */
    public function documentsByType(string $mimePrefix): MorphMany
    {
        return $this->documents()->where('mime_type', 'like', "{$mimePrefix}%");
    }
}
