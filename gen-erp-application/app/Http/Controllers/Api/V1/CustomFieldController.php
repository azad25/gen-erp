<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\CustomField;
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
    /**
     * @OA\Get(
     *     path="/custom-fields",
     *     summary="List all custom fields",
     *     tags={"Custom Fields"},
     *     @OA\Parameter(name="entity_type", in="query", description="Entity type", @OA\Schema(type="string")),
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/CustomField")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $fields = CustomField::query()
            ->when($request->get('entity_type'), fn ($q, $s) => $q->where('entity_type', $s))
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($fields);
    }

    /**
     * @OA\Get(
     *     path="/custom-fields/{id}",
     *     summary="Get a specific custom field",
     *     tags={"Custom Fields"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Custom Field ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/CustomField")
     *         )
     *     )
     * )
     */
    public function show(CustomField $customField): JsonResponse
    {
        return $this->success($customField);
    }

    /**
     * @OA\Post(
     *     path="/custom-fields",
     *     summary="Create a new custom field",
     *     tags={"Custom Fields"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="entity_type", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="field_type", type="string"),
     *             @OA\Property(property="required", type="boolean"),
     *             @OA\Property(property="options", type="array"),
     *             @OA\Property(property="default_value", type="string"),
     *             @OA\Property(property="validation_rules", type="array")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Custom field created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/CustomField"),
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

        $validated['company_id'] = activeCompany()?->id;

        $field = CustomField::create($validated);

        return $this->success($field, 'Custom field created', 201);
    }

    /**
     * @OA\Put(
     *     path="/custom-fields/{id}",
     *     summary="Update a custom field",
     *     tags={"Custom Fields"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Custom Field ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="field_type", type="string"),
     *             @OA\Property(property="required", type="boolean"),
     *             @OA\Property(property="options", type="array"),
     *             @OA\Property(property="default_value", type="string"),
     *             @OA\Property(property="validation_rules", type="array")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Custom field updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/CustomField"),
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

        $customField->update($validated);

        return $this->success($customField->fresh(), 'Custom field updated');
    }

    /**
     * @OA\Delete(
     *     path="/custom-fields/{id}",
     *     summary="Delete a custom field",
     *     tags={"Custom Fields"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Custom Field ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Custom field deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(CustomField $customField): JsonResponse
    {
        $customField->delete();

        return $this->success(null, 'Custom field deleted');
    }
}
