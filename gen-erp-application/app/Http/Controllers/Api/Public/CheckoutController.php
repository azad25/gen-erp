<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Domain\CMS\Contracts\CartServiceInterface;
use App\Domain\CMS\DTOs\CreateOrderData;
use App\Domain\CMS\Enums\PaymentMethod;
use App\Domain\CMS\Models\Site;
use App\Http\Resources\PublicOrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public API controller for checkout process.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartServiceInterface $cartService
    ) {}

    /**
     * Get available payment methods.
     */
    public function paymentMethods(Request $request, string $tenant): JsonResponse
    {
        $methods = collect(PaymentMethod::cases())->map(function (PaymentMethod $method) {
            return [
                'value' => $method->value,
                'label' => $method->label(),
                'description' => $method->description(),
                'icon' => $method->icon(),
                'requires_immediate_payment' => $method->requiresImmediatePayment(),
                'processing_time' => $method->getProcessingTime(),
                'enabled_by_default' => $method->isEnabledByDefault(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * Place an order.
     */
    public function placeOrder(Request $request, string $tenant): JsonResponse
    {
        $site = Site::where('subdomain', $tenant)
                   ->orWhere('domain', $tenant)
                   ->firstOrFail();

        $validated = $request->validate([
            'customer_email' => 'required|email',
            'customer_first_name' => 'required|string|max:100',
            'customer_last_name' => 'required|string|max:100',
            'customer_phone' => 'nullable|string|max:20',
            
            'billing_address_line_1' => 'required|string|max:255',
            'billing_address_line_2' => 'nullable|string|max:255',
            'billing_city' => 'required|string|max:100',
            'billing_state' => 'required|string|max:100',
            'billing_postal_code' => 'required|string|max:20',
            'billing_country' => 'required|string|size:2',
            
            'shipping_address_line_1' => 'required|string|max:255',
            'shipping_address_line_2' => 'nullable|string|max:255',
            'shipping_city' => 'required|string|max:100',
            'shipping_state' => 'required|string|max:100',
            'shipping_postal_code' => 'required|string|max:20',
            'shipping_country' => 'required|string|size:2',
            
            'payment_method' => 'required|string|in:' . implode(',', array_column(PaymentMethod::cases(), 'value')),
            'customer_notes' => 'nullable|string|max:1000',
        ]);

        $sessionId = $request->session()->getId();
        $customerId = $request->user()?->id;

        $cart = $this->cartService->getCart($site->id, $sessionId, $customerId);
        
        if ($cart->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot place order with empty cart.',
            ], 422);
        }

        $totals = $cart->getTotals();
        $paymentMethod = PaymentMethod::from($validated['payment_method']);

        $data = new CreateOrderData(
            siteId: $site->id,
            customerId: $customerId,
            customerEmail: $validated['customer_email'],
            customerFirstName: $validated['customer_first_name'],
            customerLastName: $validated['customer_last_name'],
            customerPhone: $validated['customer_phone'],
            billingAddressLine1: $validated['billing_address_line_1'],
            billingAddressLine2: $validated['billing_address_line_2'],
            billingCity: $validated['billing_city'],
            billingState: $validated['billing_state'],
            billingPostalCode: $validated['billing_postal_code'],
            billingCountry: $validated['billing_country'],
            shippingAddressLine1: $validated['shipping_address_line_1'],
            shippingAddressLine2: $validated['shipping_address_line_2'],
            shippingCity: $validated['shipping_city'],
            shippingState: $validated['shipping_state'],
            shippingPostalCode: $validated['shipping_postal_code'],
            shippingCountry: $validated['shipping_country'],
            subtotal: $totals['subtotal'],
            shippingCost: $totals['shipping_cost'],
            taxAmount: $totals['tax_amount'],
            discountAmount: $totals['discount_amount'],
            totalAmount: $totals['total'],
            paymentMethod: $paymentMethod,
            customerNotes: $validated['customer_notes'],
        );

        $order = $this->cartService->convertToOrder($cart->id, $data);
        $order->load(['items.product', 'items.productVariant']);

        return response()->json([
            'success' => true,
            'data' => new PublicOrderResource($order),
            'message' => 'Order placed successfully.',
        ], 201);
    }
}