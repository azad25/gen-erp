<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Auth\DTOs\UpdateCompanyData;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Services\CompanyService;
use App\Http\Resources\CompanyResource;
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
    public function __construct(
        private readonly CompanyService $companyService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/companies",
     *     summary="List all companies",
     *     tags={"Companies"},
     *
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
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(type="object")
     *             ),
     *
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $companies = $this->companyService->paginateCompanies(
            $request->only(['search']),
            $request->integer('per_page', 15)
        );

        return $this->paginated($companies);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/companies/{id}",
     *     summary="Get a specific company",
     *     tags={"Companies"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Company ID", @OA\Schema(type="integer")),
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
    public function show(Company $company): JsonResponse
    {
        $company = $this->companyService->getCompanyWithRelations($company);

        return $this->success(new CompanyResource($company));
    }

    /**
     * @OA\Put(
     *     path="/api/v1/companies/{id}",
     *     summary="Update a company",
     *     tags={"Companies"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Company ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="vat_bin", type="string"),
     *             @OA\Property(property="business_type", type="string"),
     *             @OA\Property(property="settings", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Company updated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     * )
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

        $company = $this->companyService->updateCompany(
            $company,
            UpdateCompanyData::fromRequest($request)
        );

        return $this->success(new CompanyResource($company), __('Company updated'));
    }
}
