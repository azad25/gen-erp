<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentFolder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Centralized document management — upload, download, organize, delete.
 */
class DocumentService
{
    /** Allowed MIME types for upload. */
    private const ALLOWED_MIMES = [
        // Images
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/bmp',
        // PDFs
        'application/pdf',
        // Office
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        // Text
        'text/plain', 'text/csv', 'text/html', 'text/markdown',
        // Archives
        'application/zip', 'application/x-rar-compressed', 'application/gzip',
        // Video
        'video/mp4', 'video/mpeg', 'video/webm',
        // Audio
        'audio/mpeg', 'audio/wav', 'audio/ogg',
    ];

    /** Max file size in bytes (default 10MB). */
    private const MAX_SIZE_BYTES = 10485760;

    /**
     * Storage quotas per plan tier (in bytes).
     * These defaults are used until the Subscription Engine (Phase 8) is wired.
     * Phase 8 will read these from the `plans` table instead.
     */
    public const STORAGE_QUOTAS = [
        'free'       => 52428800,    // 50 MB
        'pro'        => 1073741824,  // 1 GB
        'enterprise' => 5368709120,  // 5 GB
    ];

    /**
     * Upload a file to the document management system.
     */
    public function upload(
        UploadedFile $file,
        int $companyId,
        int $uploadedBy,
        ?int $folderId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null,
    ): Document {
        $this->validateFile($file);
        $this->enforceStorageQuota($companyId, $file->getSize());

        // Strip EXIF from images
        $tempPath = $file->getPathname();
        if (str_starts_with($file->getMimeType(), 'image/') && function_exists('exif_read_data')) {
            $this->stripExif($tempPath);
        }

        // Build disk path: {company_id}/{year}/{uuid}.{ext}
        $ext = $file->getClientOriginalExtension() ?: 'bin';
        $diskPath = "{$companyId}/" . now()->format('Y') . '/' . Str::uuid() . ".{$ext}";

        // Store in private disk
        Storage::disk('local')->put("private/{$diskPath}", file_get_contents($tempPath));

        // Build metadata
        $metadata = $this->extractMetadata($file);

        return Document::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'folder_id' => $folderId,
            'documentable_type' => $entityType,
            'documentable_id' => $entityId,
            'name' => $file->getClientOriginalName(),
            'disk_path' => $diskPath,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'description' => $description,
            'metadata' => $metadata ?: null,
            'uploaded_by' => $uploadedBy,
            'uploaded_at' => now(),
        ]);
    }

    /**
     * Upload from a raw string (e.g. generated PDF).
     */
    public function uploadRaw(
        string $content,
        string $filename,
        string $mimeType,
        int $companyId,
        int $uploadedBy,
        ?int $folderId = null,
        ?string $entityType = null,
        ?int $entityId = null,
    ): Document {
        $this->enforceStorageQuota($companyId, strlen($content));

        $ext = pathinfo($filename, PATHINFO_EXTENSION) ?: 'bin';
        $diskPath = "{$companyId}/" . now()->format('Y') . '/' . Str::uuid() . ".{$ext}";

        Storage::disk('local')->put("private/{$diskPath}", $content);

        return Document::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'folder_id' => $folderId,
            'documentable_type' => $entityType,
            'documentable_id' => $entityId,
            'name' => $filename,
            'disk_path' => $diskPath,
            'mime_type' => $mimeType,
            'size_bytes' => strlen($content),
            'uploaded_by' => $uploadedBy,
            'uploaded_at' => now(),
        ]);
    }

    /**
     * Get the full storage path for a document.
     */
    public function storagePath(Document $document): string
    {
        return storage_path("app/private/{$document->disk_path}");
    }

    /**
     * Get file contents as a string.
     */
    public function getContents(Document $document): ?string
    {
        $path = "private/{$document->disk_path}";

        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        return Storage::disk('local')->get($path);
    }

    /**
     * Delete a document (soft delete model + optionally remove file).
     */
    public function delete(Document $document, bool $removeFile = false): void
    {
        if ($removeFile) {
            Storage::disk('local')->delete("private/{$document->disk_path}");
        }

        $document->delete();
    }

    /**
     * Permanently delete (force + remove file).
     */
    public function forceDelete(Document $document): void
    {
        Storage::disk('local')->delete("private/{$document->disk_path}");
        $document->forceDelete();
    }

    /**
     * Move a document to a different folder.
     */
    public function move(Document $document, ?int $folderId): void
    {
        $document->update(['folder_id' => $folderId]);
    }

    /**
     * Create a folder.
     */
    public function createFolder(int $companyId, string $name, ?int $parentId = null, ?int $createdBy = null): DocumentFolder
    {
        return DocumentFolder::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'parent_id' => $parentId,
            'name' => $name,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Get all documents attached to a particular entity.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Document>
     */
    public function getByEntity(string $entityType, int $entityId, int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return Document::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('documentable_type', $entityType)
            ->where('documentable_id', $entityId)
            ->orderByDesc('uploaded_at')
            ->get();
    }

    /**
     * Calculate total storage used by a company in bytes.
     */
    public function companyStorageUsed(int $companyId): int
    {
        return (int) Document::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->sum('size_bytes');
    }

    /**
     * Get the storage quota for a company's plan (in bytes).
     *
     * TODO: Phase 8 — read from plans table via SubscriptionService instead of hardcoded tiers.
     */
    public function getStorageQuota(int $companyId): int
    {
        // When the subscription engine is built (Phase 8), this will call:
        // return app(SubscriptionService::class)->getFeatureLimit($companyId, 'storage_bytes');
        $company = \App\Models\Company::find($companyId);
        $plan = $company?->plan ?? 'free';

        // Handle BackedEnum (plan may be cast to an enum)
        if ($plan instanceof \BackedEnum) {
            $plan = $plan->value;
        }

        return self::STORAGE_QUOTAS[$plan] ?? self::STORAGE_QUOTAS['free'];
    }

    /**
     * Get storage usage as a percentage (0-100).
     */
    public function storageUsagePercent(int $companyId): float
    {
        $used = $this->companyStorageUsed($companyId);
        $quota = $this->getStorageQuota($companyId);

        if ($quota <= 0) {
            return 100.0;
        }

        return round(($used / $quota) * 100, 1);
    }

    /**
     * Get human-readable remaining storage.
     */
    public function storageRemaining(int $companyId): string
    {
        $remaining = $this->getStorageQuota($companyId) - $this->companyStorageUsed($companyId);

        if ($remaining <= 0) {
            return '0 B';
        }

        if ($remaining >= 1073741824) {
            return round($remaining / 1073741824, 1) . ' GB';
        }
        if ($remaining >= 1048576) {
            return round($remaining / 1048576, 1) . ' MB';
        }

        return round($remaining / 1024, 1) . ' KB';
    }

    /**
     * Check if the company has enough storage quota for a new upload.
     *
     * @throws ValidationException if quota exceeded
     */
    private function enforceStorageQuota(int $companyId, int $fileSizeBytes): void
    {
        $used = $this->companyStorageUsed($companyId);
        $quota = $this->getStorageQuota($companyId);

        if (($used + $fileSizeBytes) > $quota) {
            $usedMB = round($used / 1048576, 1);
            $quotaMB = round($quota / 1048576, 1);

            throw ValidationException::withMessages([
                'file' => [__('Storage quota exceeded. Used :used MB of :quota MB. Please upgrade your plan or delete unused files.', [
                    'used' => $usedMB,
                    'quota' => $quotaMB,
                ])],
            ]);
        }
    }

    /**
     * Validate file against MIME allowlist and size limit.
     */
    private function validateFile(UploadedFile $file): void
    {
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'file' => [__('File type :type is not allowed.', ['type' => $file->getMimeType()])],
            ]);
        }

        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            throw ValidationException::withMessages([
                'file' => [__('File exceeds the maximum size of :size MB.', ['size' => self::MAX_SIZE_BYTES / 1048576])],
            ]);
        }
    }

    /**
     * Strip EXIF metadata from an image for privacy.
     */
    private function stripExif(string $path): void
    {
        try {
            $image = @imagecreatefromstring(file_get_contents($path));
            if (! $image) {
                return;
            }

            $mime = mime_content_type($path);
            match ($mime) {
                'image/jpeg' => imagejpeg($image, $path, 90),
                'image/png' => imagepng($image, $path),
                'image/webp' => imagewebp($image, $path),
                default => null,
            };

            imagedestroy($image);
        } catch (\Throwable) {
            // Non-critical — skip silently
        }
    }

    /**
     * Extract metadata from uploaded file.
     *
     * @return array<string, mixed>
     */
    private function extractMetadata(UploadedFile $file): array
    {
        $meta = [];

        if (str_starts_with($file->getMimeType(), 'image/')) {
            $size = @getimagesize($file->getPathname());
            if ($size) {
                $meta['width'] = $size[0];
                $meta['height'] = $size[1];
            }
        }

        return $meta;
    }
}
