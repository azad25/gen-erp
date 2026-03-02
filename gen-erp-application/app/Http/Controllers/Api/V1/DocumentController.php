<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
     *     path="/documents",
     *     summary="List all documents",
     *     tags={"Documents"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="mime_type", in="query", description="MIME type", @OA\Schema(type="string")),
     *     @OA\Parameter(name="folder_id", in="query", description="Folder ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Document")})),
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
            ->with(['folder', 'uploader'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($documents);
    }

    /**
     * @OA\Get(
     *     path="/documents/{id}",
     *     summary="Get a specific document",
     *     tags={"Documents"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Document ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Document")
     *         )
     *     )
     * )
     */
    public function show(Document $document): JsonResponse
    {
        $document->load(['folder', 'uploader']);

        return $this->success($document);
    }

    /**
     * @OA\Post(
     *     path="/documents",
     *     summary="Upload a new document",
     *     tags={"Documents"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object"))
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Document uploaded",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Document"),
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
        ]);

        $validated['company_id'] = activeCompany()->id;
        $validated['uploaded_by'] = auth()->id();

        $file = $request->file('file');
        $document = $this->documentService->upload($file, $validated);

        return $this->success($document->load(['folder', 'uploader']), 'Document uploaded', 201);
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
     *     path="/documents/{document}/download",
     *     summary="Download document",
     *     tags={"Documents"},
     *     @OA\Parameter(name="document", in="path", required=true, description="Document ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Download URL generated",
     *         @OA\JsonContent(
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
     *     path="/documents/{document}/thumbnail",
     *     summary="Get document thumbnail",
     *     tags={"Documents"},
     *     @OA\Parameter(name="document", in="path", required=true, description="Document ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Thumbnail URL generated",
     *         @OA\JsonContent(
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
     *     path="/documents/{document}/preview",
     *     summary="Get document preview",
     *     tags={"Documents"},
     *     @OA\Parameter(name="document", in="path", required=true, description="Document ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Preview URL generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="preview_url", type="string")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function preview(Document $document): JsonResponse
    {
        $url = $this->documentService->getPreviewUrl($document);

        return $this->success(['preview_url' => $url]);
    }
}
