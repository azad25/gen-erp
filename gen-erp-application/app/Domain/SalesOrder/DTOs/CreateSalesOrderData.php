<?php

namespace App\Domain\SalesOrder\DTOs;

use Carbon\Carbon;

readonly class CreateSalesOrderData
{
    public function __construct(
        public int $companyId,
        public int $customerId,
        public ?int $warehouseId,
        public Carbon $orderDate,
        public ?Carbon $deliveryDate,
        public ?string $reference,
        public ?string $notes,
        public ?string $termsConditions,
        /** @var CreateSalesOrderItemData[] */
        public array $items,
    ) {}

    /**
     * Create from Form Request.
     */
    public static function fromRequest(\App\Http\Requests\SalesOrder\CreateSalesOrderRequest|\App\Http\Requests\SalesOrder\UpdateSalesOrderRequest $request): self
    {
        $company = $request->user()->activeCompany();

        return new self(
            companyId: $company->id,
            customerId: $request->integer('customer_id'),
            warehouseId: $request->integer('warehouse_id'),
            orderDate: $request->has('order_date') 
                ? Carbon::parse($request->string('order_date')) 
                : now(),
            deliveryDate: $request->has('delivery_date') 
                ? Carbon::parse($request->string('delivery_date')) 
                : null,
            reference: $request->string('reference'),
            notes: $request->string('notes'),
            termsConditions: $request->string('terms_conditions'),
            items: collect($request->array('items'))
                ->map(fn($item) => CreateSalesOrderItemData::fromArray($item))
                ->all(),
        );
    }

    /**
     * Convert to array for model creation.
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'customer_id' => $this->customerId,
            'warehouse_id' => $this->warehouseId,
            'order_date' => $this->orderDate->toDateString(),
            'delivery_date' => $this->deliveryDate?->toDateString(),
            'reference' => $this->reference,
            'notes' => $this->notes,
            'terms_conditions' => $this->termsConditions,
        ];
    }
}