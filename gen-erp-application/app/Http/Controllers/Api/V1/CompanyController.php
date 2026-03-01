<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Companies",
 *     description="Company management"
 * )
 * REST API v1 controller for Company CRUD operations.
 */
class CompanyController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/companies",
     *     summary="List all companies",
     *     tags={"Companies"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Company")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     */
    public function index(Request $request): JsonResponse
    {
        $companies = Company::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($companies);
    }

    /**
     * @OA\Get(
     *     path="/companies/{id}",
     *     summary="Get a specific company",
     *     tags={"Companies"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Company ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="data", ref="#/components/schemas/Company")))
     */
    public function show(Company $company): JsonResponse
    {
        $company->load(['users', 'branches', 'warehouses']);

        return $this->success($company);
    }

    /**
     * @OA\Put(
     *     path="/companies/{id}",
     *     summary="Update a company",
     *     tags={"Companies"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Company ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
                @OA\Property(property="name", type="string"
            ), @OA\Property(property="address", type="string"), @OA\Property(property="phone", type="string"), @OA\Property(property="email", type="string"), @OA\Property(property="vat_bin", type="string"), @OA\Property(property="business_type", type="string"), @OA\Property(property="settings", type="array", @OA\Items(type="object")))),
     *     @OA\Response(
     *         response=200,
     *         description="Company updated",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="data", ref="#/components/schemas/Company"), @OA\Property(property="message", type="string")))
     */
    public function update(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'vat_bin' => ['nullable', 'string', 'max:50'],
            'business_type' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
        ]);

        $company->update($validated);

        return $this->success($company->fresh(), 'Company updated');
    }
}
