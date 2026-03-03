<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Domain\SalesOrder\Services\SalesOrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesOrderController extends Controller
{
    public function __construct(
        private readonly SalesOrderService $salesOrderService,
    ) {}

    /**
     * Display the sales orders page with initial data.
     */
    public function index(Request $request): Response
    {
        $company = activeCompany();

        // Get initial sales orders data (first page)
        $salesOrders = $this->salesOrderService->paginateOrders(
            $company,
            $request->only(['search', 'status', 'customer_id']),
            15
        );

        // Get customers for the dropdown
        $customers = $company->customers()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Get warehouses for the dropdown
        $warehouses = $company->warehouses()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Get products for the dropdown
        $products = $company->products()
            ->select('id', 'name', 'selling_price')
            ->orderBy('name')
            ->get();

        return Inertia::render('Sales/Orders', [
            'initialOrders' => $salesOrders->items(),
            'initialCustomers' => $customers,
            'initialWarehouses' => $warehouses,
            'initialProducts' => $products,
            'pagination' => [
                'current_page' => $salesOrders->currentPage(),
                'per_page' => $salesOrders->perPage(),
                'total' => $salesOrders->total(),
                'last_page' => $salesOrders->lastPage(),
                'from' => $salesOrders->firstItem(),
                'to' => $salesOrders->lastItem(),
            ],
        ]);
    }
}
