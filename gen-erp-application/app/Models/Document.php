<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;

/**
 * A file stored in the GenERP BD document management system.
 */
class Document extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'folder_id',
        'documentable_type',
        'documentable_id',
        'name',
        'disk_path',
        'mime_type',
        'size_bytes',
        'description',
        'metadata',
        'uploaded_by',
        'uploaded_at',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'size_bytes' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<DocumentFolder, $this> */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    /** Polymorphic relation to the owning entity (Product, Invoice, etc.). */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Generate a time-limited signed URL for secure download.
     */
    public function signedUrl(int $expiryMinutes = 60): string
    {
        return URL::temporarySignedRoute(
            'documents.download',
            now()->addMinutes($expiryMinutes),
            ['document' => $this->id],
        );
    }

    /** Human-readable file size. */
    public function formattedSize(): string
    {
        $bytes = $this->size_bytes;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }

    /** Get a Heroicon name for this MIME type. */
    public function mimeIcon(): string
    {
        return match (true) {
            str_starts_with($this->mime_type, 'image/') => 'heroicon-o-photo',
            str_starts_with($this->mime_type, 'video/') => 'heroicon-o-film',
            str_starts_with($this->mime_type, 'audio/') => 'heroicon-o-musical-note',
            $this->mime_type === 'application/pdf' => 'heroicon-o-document-text',
            str_contains($this->mime_type, 'spreadsheet') || str_contains($this->mime_type, 'excel') => 'heroicon-o-table-cells',
            str_contains($this->mime_type, 'word') || str_contains($this->mime_type, 'document') => 'heroicon-o-document',
            str_contains($this->mime_type, 'zip') || str_contains($this->mime_type, 'compressed') => 'heroicon-o-archive-box',
            str_starts_with($this->mime_type, 'text/') => 'heroicon-o-document-text',
            default => 'heroicon-o-paper-clip',
        };
    }

    /** Check if this is a previewable file (image or PDF). */
    public function isPreviewable(): bool
    {
        return str_starts_with($this->mime_type, 'image/')
            || $this->mime_type === 'application/pdf';
    }

    /** Check if this is an image. */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /** File extension from name. */
    public function extension(): string
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }
}
