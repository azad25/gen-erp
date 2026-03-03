<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\CustomField;
use App\Domain\System\Services\SystemService;
use App\Http\Resources\CustomFieldResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Custom Fields",
 *     description="Custom field management"
 * )
 * REST API v1 controller for Custom Field operations.
 */
class CustomFieldController extends BaseApiController
{
    public function __construct(
        private SystemService $systemService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/v1/custom-fields",
     *     summary="List all custom fields",
     *     tags={"Custom Fields"},
     *
     *     @OA\Parameter(name="entity_type", in="query", description="Entity type", @OA\Schema(type="string")),
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
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
        $fields = $this->systemService->getCustomFields(
            activeCompany()->id,
            $request->get('entity_type'),
            $request->get('search'),
            $request->integer('per_page', 15)
        );

        return $this->paginated($fields);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/custom-fields/{id}",
     *     summary="Get a specific custom field",
     *     tags={"Custom Fields"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Custom Field ID", @OA\Schema(type="integer")),
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
    public function show(CustomField $customField): JsonResponse
    {
        return $this->success(new CustomFieldResource($customField));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/custom-fields",
     *     summary="Create a new custom field",
     *     tags={"Custom Fields"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="entity_type", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="field_type", type="string"),
     *             @OA\Property(property="required", type="boolean"),
     *             @OA\Property(property="options", type="array"),
     *             @OA\Property(property="default_value", type="string"),
     *             @OA\Property(property="validation_rules", type="array")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Custom field created",
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
            'entity_type' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'field_type' => ['required', 'string'],
            'required' => ['sometimes', 'boolean'],
            'options' => ['nullable', 'array'],
            'default_value' => ['nullable'],
            'validation_rules' => ['nullable', 'array'],
        ]);

        $validated['company_id'] = activeCompany()->id;

        $field = $this->systemService->createCustomField($validated);

        return $this->success(new CustomFieldResource($field), __('Custom field created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/custom-fields/{id}",
     *     summary="Update a custom field",
     *     tags={"Custom Fields"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Custom Field ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="field_type", type="string"),
     *             @OA\Property(property="required", type="boolean"),
     *             @OA\Property(property="options", type="array"),
     *             @OA\Property(property="default_value", type="string"),
     *             @OA\Property(property="validation_rules", type="array")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Custom field updated",
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
    public function update(Request $request, CustomField $customField): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'field_type' => ['sometimes', 'string'],
            'required' => ['sometimes', 'boolean'],
            'options' => ['nullable', 'array'],
            'default_value' => ['nullable'],
            'validation_rules' => ['nullable', 'array'],
        ]);

        $updatedField = $this->systemService->updateCustomField($customField, $validated);

        return $this->success(new CustomFieldResource($updatedField), __('Custom field updated'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/custom-fields/{id}",
     *     summary="Delete a custom field",
     *     tags={"Custom Fields"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Custom Field ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Custom field deleted",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(CustomField $customField): JsonResponse
    {
        $this->systemService->deleteCustomField($customField);

        return $this->success(null, __('Custom field deleted'));
    }
}
