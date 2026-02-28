<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REST API v1 controller for Supplier CRUD operations.
 */
class SupplierController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $suppliers = Supplier::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($suppliers);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return $this->success($supplier);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'vat_bin' => ['nullable', 'string', 'max:50'],
        ]);

        $validated['company_id'] = activeCompany()?->id;
        $supplier = Supplier::create($validated);

        return $this->success($supplier, 'Supplier created', 201);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'vat_bin' => ['nullable', 'string', 'max:50'],
        ]);

        $supplier->update($validated);

        return $this->success($supplier->fresh(), 'Supplier updated');
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return $this->success(null, 'Supplier deleted');
    }
}
