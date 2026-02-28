<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REST API v1 controller for Customer CRUD operations.
 */
class CustomerController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($customers);
    }

    public function show(Customer $customer): JsonResponse
    {
        return $this->success($customer);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'district' => ['nullable', 'string', 'max:100'],
            'credit_limit' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['company_id'] = activeCompany()?->id;
        $customer = Customer::create($validated);

        return $this->success($customer, 'Customer created', 201);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'district' => ['nullable', 'string', 'max:100'],
            'credit_limit' => ['nullable', 'integer', 'min:0'],
        ]);

        $customer->update($validated);

        return $this->success($customer->fresh(), 'Customer updated');
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return $this->success(null, 'Customer deleted');
    }
}
