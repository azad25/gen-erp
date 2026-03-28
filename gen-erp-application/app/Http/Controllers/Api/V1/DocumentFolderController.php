<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Document\Contracts\DocumentServiceInterface;
use App\Http\Resources\DocumentFolderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Document Folders",
 *     description="Document folder management"
 * )
 * REST API v1 controller for Document Folder CRUD operations.
 */
class DocumentFolderController extends BaseApiController
{
    public function __construct(
        private readonly DocumentServiceInterface $documentService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/v1/document-folders",
     *     summary="List all document folders",
     *     tags={"Document Folders"},
     *
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="parent_id", in="query", description="Parent folder ID", @OA\Schema(type="integer")),
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
        // Debug company context
        $company = activeCompany();
        if (!$company) {
            \Log::error('[DocumentFolderController] No active company found', [
                'user_id' => auth()->id(),
                'session_company_id' => session('active_company_id'),
                'request_headers' => $request->headers->all(),
            ]);
            return $this->error('No active company found', 403);
        }

        $folders = $this->documentService->getFolders(
            $company->id,
            $request->get('search'),
            $request->get('parent_id'),
            $request->integer('per_page', 15)
        );

        return $this->paginated($folders, DocumentFolderResource::class);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/document-folders/{id}",
     *     summary="Get a specific document folder",
     *     tags={"Document Folders"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Document Folder ID", @OA\Schema(type="integer")),
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
    public function show(int $id): JsonResponse
    {
        $company = activeCompany();
        if (!$company) {
            return $this->error('No active company found', 403);
        }

        $documentFolder = $this->documentService->getFolder($company->id, $id);
        $documentFolder->load(['parent', 'children', 'documents']);

        return $this->success(new DocumentFolderResource($documentFolder));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/document-folders",
     *     summary="Create a new document folder",
     *     tags={"Document Folders"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="parent_id", type="integer"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Document folder created",
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
        $company = activeCompany();
        if (!$company) {
            return $this->error('No active company found', 403);
        }

        $companyId = $company->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', Rule::exists('document_folders', 'id')->where('company_id', $companyId)],
            'description' => ['nullable', 'string'],
        ]);

        $folder = $this->documentService->createFolder(
            $companyId,
            $validated['name'],
            $validated['parent_id'] ?? null,
            auth()->id()
        );

        return $this->success(new DocumentFolderResource($folder), 'Document folder created', 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/document-folders/{id}",
     *     summary="Update a document folder",
     *     tags={"Document Folders"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Document Folder ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Document folder updated",
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
    public function update(Request $request, int $id): JsonResponse
    {
        $company = activeCompany();
        if (!$company) {
            return $this->error('No active company found', 403);
        }

        $companyId = $company->id;
        $documentFolder = $this->documentService->getFolder($companyId, $id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'parent_id' => ['nullable', Rule::exists('document_folders', 'id')->where('company_id', $companyId)],
            'description' => ['nullable', 'string'],
        ]);

        $updatedFolder = $this->documentService->updateFolder($documentFolder, $validated);

        return $this->success(new DocumentFolderResource($updatedFolder), 'Document folder updated');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/document-folders/{id}",
     *     summary="Delete a document folder",
     *     tags={"Document Folders"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Document Folder ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Document folder deleted",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $company = activeCompany();
        if (!$company) {
            return $this->error('No active company found', 403);
        }

        $documentFolder = $this->documentService->getFolder($company->id, $id);
        $this->documentService->deleteFolder($documentFolder);

        return $this->success(null, 'Document folder deleted');
    }
}
