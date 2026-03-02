<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\DocumentFolder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Document Folders",
 *     description="Document folder management"
 * )
 * REST API v1 controller for Document Folder CRUD operations.
 */
class DocumentFolderController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/document-folders",
     *     summary="List all document folders",
     *     tags={"Document Folders"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="parent_id", in="query", description="Parent folder ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/DocumentFolder")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $folders = DocumentFolder::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->when($request->get('parent_id'), fn ($q, $id) => $q->where('parent_id', $id))
            ->with(['parent', 'children', 'documents'])
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($folders);
    }

    /**
     * @OA\Get(
     *     path="/document-folders/{id}",
     *     summary="Get a specific document folder",
     *     tags={"Document Folders"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Document Folder ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/DocumentFolder")
     *         )
     *     )
     * )
     */
    public function show(DocumentFolder $documentFolder): JsonResponse
    {
        $documentFolder->load(['parent', 'children', 'documents']);

        return $this->success($documentFolder);
    }

    /**
     * @OA\Post(
     *     path="/document-folders",
     *     summary="Create a new document folder",
     *     tags={"Document Folders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="parent_id", type="integer"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Document folder created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/DocumentFolder"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:document_folders,id'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = activeCompany()->id;

        $folder = DocumentFolder::create($validated);

        return $this->success($folder, 'Document folder created', 201);
    }

    /**
     * @OA\Put(
     *     path="/document-folders/{id}",
     *     summary="Update a document folder",
     *     tags={"Document Folders"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Document Folder ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document folder updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/DocumentFolder"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, DocumentFolder $documentFolder): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:document_folders,id'],
            'description' => ['nullable', 'string'],
        ]);

        $documentFolder->update($validated);

        return $this->success($documentFolder->fresh(), 'Document folder updated');
    }

    /**
     * @OA\Delete(
     *     path="/document-folders/{id}",
     *     summary="Delete a document folder",
     *     tags={"Document Folders"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Document Folder ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Document folder deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(DocumentFolder $documentFolder): JsonResponse
    {
        $documentFolder->delete();

        return $this->success(null, 'Document folder deleted');
    }
}
