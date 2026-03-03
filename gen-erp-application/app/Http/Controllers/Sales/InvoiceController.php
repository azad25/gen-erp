<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Domain\Invoice\Services\InvoiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService
    ) {}

    /**
     * Display the invoices page with initial data.
     */
    public function index(Request $request): Response
    {
        $company = activeCompany();

        // Get initial invoices data (first page)
        $invoices = $this->invoiceService->paginateInvoices(
            $company,
            $request->only(['search', 'status', 'customer_id']),
            15
        );

        // Get customers for the dropdown
        $customers = $company->customers()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Get products for the dropdown
        $products = $company->products()
            ->select('id', 'name', 'selling_price')
            ->orderBy('name')
            ->get();

        return Inertia::render('Sales/Invoices', [
            'initialInvoices' => $invoices->items(),
            'initialCustomers' => $customers,
            'initialProducts' => $products,
            'pagination' => [
                'current_page' => $invoices->currentPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
                'last_page' => $invoices->lastPage(),
                'from' => $invoices->firstItem(),
                'to' => $invoices->lastItem(),
            ],
        ]);
    }
}
