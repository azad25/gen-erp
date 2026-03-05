<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Domain\Customer\Models\CustomerPayment;
use App\Domain\Customer\Models\Customer;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Accounting\Models\PaymentMethod;
use App\Domain\Payment\Contracts\PaymentServiceInterface;
use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentServiceInterface $paymentService
    ) {}

    /**
     * Display a listing of payments.
     */
    public function index(Request $request): Response
    {
        $company = CompanyContext::active();
        
        $payments = CustomerPayment::query()
            ->where('company_id', $company->id)
            ->with(['customer', 'paymentMethod', 'allocations.invoice'])
            ->when($request->get('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('receipt_number', 'LIKE', "%{$search}%")
                      ->orWhereHas('customer', fn($q) => $q->where('name', 'LIKE', "%{$search}%"));
                });
            })
            ->when($request->get('customer_id'), fn($q, $id) => $q->where('customer_id', $id))
            ->orderBy('payment_date', 'desc')
            ->paginate(15);

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'customer_id']),
        ]);
    }

    /**
     * Show the form for creating a new payment.
     */
    public function create(): Response
    {
        $company = CompanyContext::active();
        
        $customers = Customer::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->select('id', 'name', 'email', 'phone', 'balance')
            ->orderBy('name')
            ->get();

        $paymentMethods = PaymentMethod::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->select('id', 'name', 'type')
            ->orderBy('name')
            ->get();

        return Inertia::render('Payments/Create', [
            'customers' => $customers,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request): RedirectResponse
    {
        $company = CompanyContext::active();

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:1'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required_with:allocations', 'exists:invoices,id'],
            'allocations.*.amount' => ['required_with:allocations', 'integer', 'min:1'],
        ]);

        $customer = Customer::where('company_id', $company->id)->findOrFail($validated['customer_id']);

        $paymentData = [
            'payment_date' => $validated['payment_date'],
            'amount' => $validated['amount'],
            'payment_method_id' => $validated['payment_method_id'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        $allocations = $validated['allocations'] ?? [];

        try {
            $payment = $this->paymentService->receivePayment($customer, $paymentData, $allocations);
            
            return redirect()
                ->route('payments.show', $payment->id)
                ->with('success', 'Payment received successfully');
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['allocations' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified payment.
     */
    public function show(int $id): Response
    {
        $company = CompanyContext::active();
        
        $payment = CustomerPayment::query()
            ->where('company_id', $company->id)
            ->with(['customer', 'paymentMethod', 'allocations.invoice', 'createdBy'])
            ->findOrFail($id);

        return Inertia::render('Payments/Show', [
            'payment' => $payment,
            'unallocatedAmount' => $payment->unallocatedAmount(),
        ]);
    }

    /**
     * Show the form for editing the specified payment.
     */
    public function edit(int $id): Response
    {
        $company = CompanyContext::active();
        
        $payment = CustomerPayment::query()
            ->where('company_id', $company->id)
            ->with(['customer', 'allocations.invoice'])
            ->findOrFail($id);

        return Inertia::render('Payments/Edit', [
            'payment' => $payment,
        ]);
    }

    /**
     * Update the specified payment (notes/reference only).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $company = CompanyContext::active();
        
        $payment = CustomerPayment::query()
            ->where('company_id', $company->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
            'reference_number' => ['nullable', 'string', 'max:255'],
        ]);

        $payment->update($validated);

        return redirect()
            ->route('payments.show', $payment->id)
            ->with('success', 'Payment updated successfully');
    }

    /**
     * Show allocation form for a payment.
     */
    public function allocate(int $id): Response
    {
        $company = CompanyContext::active();
        
        $payment = CustomerPayment::query()
            ->where('company_id', $company->id)
            ->with(['customer', 'allocations.invoice'])
            ->findOrFail($id);

        // Get unpaid/partially paid invoices for this customer
        $invoices = Invoice::query()
            ->where('company_id', $company->id)
            ->where('customer_id', $payment->customer_id)
            ->whereIn('status', ['draft', 'sent', 'partial'])
            ->whereColumn('amount_paid', '<', 'total_amount')
            ->with('customer')
            ->orderBy('invoice_date')
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date,
                    'total_amount' => $invoice->total_amount,
                    'amount_paid' => $invoice->amount_paid,
                    'balance' => $invoice->total_amount - $invoice->amount_paid,
                ];
            });

        return Inertia::render('Payments/Allocate', [
            'payment' => $payment,
            'invoices' => $invoices,
            'unallocatedAmount' => $payment->unallocatedAmount(),
        ]);
    }

    /**
     * Store payment allocation.
     */
    public function storeAllocation(Request $request, int $id): RedirectResponse
    {
        $company = CompanyContext::active();
        
        $payment = CustomerPayment::query()
            ->where('company_id', $company->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'invoice_id' => ['required', 'exists:invoices,id'],
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $this->paymentService->allocatePayment($payment, $validated['invoice_id'], $validated['amount']);
            
            return redirect()
                ->route('payments.show', $payment->id)
                ->with('success', 'Payment allocated successfully');
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['amount' => $e->getMessage()]);
        }
    }
}
