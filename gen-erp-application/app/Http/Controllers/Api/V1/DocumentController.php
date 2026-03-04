<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Document\Models\Document;
use App\Domain\Document\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Documents",
 *     description="Document management"
 * )
 * REST API v1 controller for Document operations.
 */
class DocumentController extends BaseApiController
{
    public function __construct(
        private DocumentService $documentService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/documents",
     *     summary="List all documents",
     *     tags={"Documents"},
     *
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="mime_type", in="query", description="MIME type", @OA\Schema(type="string")),
     *     @OA\Parameter(name="folder_id", in="query", description="Folder ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $documents = Document::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->when($request->get('mime_type'), fn ($q, $s) => $q->where('mime_type', $s))
            ->when($request->get('folder_id'), fn ($q, $id) => $q->where('folder_id', $id))
            ->with(['folder', 'uploadedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($documents);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/documents/{id}",
     *     summary="Get a specific document",
     *     tags={"Documents"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Document ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function show(Document $document): JsonResponse
    {
        $document->load(['folder', 'uploadedBy']);

        return $this->success($document);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/documents",
     *     summary="Upload a new document",
     *     tags={"Documents"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object"))
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Document uploaded",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'folder_id' => ['nullable', 'exists:document_folders,id'],
            'name' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240'], // 10MB max
            'description' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        $document = $this->documentService->upload(
            $file,
            activeCompany()->id,
            null, // entityType
            null, // entityId
            $validated['folder_id'] ?? null,
            $validated['description'] ?? null,
            auth()->id()
        );

        return $this->success($document->load(['folder', 'uploadedBy']), 'Document uploaded', 201);
    }

    public function update(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'folder_id' => ['nullable', 'exists:document_folders,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'custom_fields' => ['nullable', 'array'],
        ]);

        $document->update($validated);

        return $this->success($document->fresh(), 'Document updated');
    }

    public function destroy(Document $document): JsonResponse
    {
        $document->delete();

        return $this->success(null, 'Document deleted');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/documents/{document}/download",
     *     summary="Download document",
     *     tags={"Documents"},
     *
     *     @OA\Parameter(name="document", in="path", required=true, description="Document ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Download URL generated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="download_url", type="string")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function download(Document $document): JsonResponse
    {
        $url = $this->documentService->getDownloadUrl($document);

        return $this->success(['download_url' => $url]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/documents/{document}/thumbnail",
     *     summary="Get document thumbnail",
     *     tags={"Documents"},
     *
     *     @OA\Parameter(name="document", in="path", required=true, description="Document ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Thumbnail URL generated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="thumbnail_url", type="string")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function thumbnail(Document $document): JsonResponse
    {
        $url = $this->documentService->getThumbnailUrl($document);

        return $this->success(['thumbnail_url' => $url]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/documents/{document}/preview",
     *     summary="Get document preview",
     *     tags={"Documents"},
     *
     *     @OA\Parameter(name="document", in="path", required=true, description="Document ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Preview URL generated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="preview_url", type="string")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    /**
     * @OA\Get(
     *     path="/api/v1/documents/storage-info",
     *     summary="Get storage information",
     *     tags={"Documents"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Storage information",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function storageInfo(): JsonResponse
    {
        $companyId = activeCompany()->id;
        
        $used = $this->documentService->companyStorageUsed($companyId);
        $quota = $this->documentService->getStorageQuota($companyId);
        $usagePercent = $this->documentService->storageUsagePercent($companyId);
        $remaining = $this->documentService->storageRemaining($companyId);

        return $this->success([
            'used_bytes' => $used,
            'quota_bytes' => $quota,
            'usage_percent' => $usagePercent,
            'remaining' => $remaining,
            'used_formatted' => $this->formatBytes($used),
            'quota_formatted' => $this->formatBytes($quota),
        ]);
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 1) . ' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        
        return $bytes . ' B';
    }
}
