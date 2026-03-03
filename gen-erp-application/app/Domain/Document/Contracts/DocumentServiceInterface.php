<?php

namespace App\Domain\Document\Contracts;

use App\Domain\Document\Models\Document;
use App\Domain\Document\Models\DocumentFolder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

/**
 * Interface for document service operations.
 */
interface DocumentServiceInterface
{
    /**
     * Upload a document.
     */
    public function upload(
        UploadedFile $file,
        int $companyId,
        ?string $entityType = null,
        ?int $entityId = null,
        ?int $folderId = null,
        ?string $description = null,
        ?int $uploadedBy = null
    ): Document;

    /**
     * Get paginated folders for a company.
     */
    public function getFolders(int $companyId, ?string $search = null, ?int $parentId = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a specific folder.
     */
    public function getFolder(int $companyId, int $id): DocumentFolder;

    /**
     * Create a folder.
     */
    public function createFolder(int $companyId, string $name, ?int $parentId = null, ?int $createdBy = null): DocumentFolder;

    /**
     * Update a folder.
     */
    public function updateFolder(DocumentFolder $folder, array $data): DocumentFolder;

    /**
     * Delete a folder.
     */
    public function deleteFolder(DocumentFolder $folder): void;
}