<?php

namespace App\Domain\Invoice\DTOs;

use Carbon\Carbon;

readonly class CreateInvoiceData
{
    public function __construct(
        public int $companyId,
        public int $customerId,
        public ?int $warehouseId,
        public Carbon $invoiceDate,
        public ?Carbon $dueDate,
        public ?string $notes,
        public ?string $termsConditions,
        /** @var CreateInvoiceItemData[] */
        public array $items,
    ) {}

    /**
     * Create from Form Request.
     */
    public static function fromRequest(\App\Http\Requests\Invoice\CreateInvoiceRequest|\App\Http\Requests\Invoice\UpdateInvoiceRequest $request): self
    {
        $user = $request->user();
        $company = $user->activeCompany();

        return new self(
            companyId: $company->id,
            customerId: $request->integer('customer_id'),
            warehouseId: $request->integer('warehouse_id'),
            invoiceDate: $request->has('invoice_date') 
                ? Carbon::parse($request->string('invoice_date')) 
                : now(),
            dueDate: $request->has('due_date') 
                ? Carbon::parse($request->string('due_date')) 
                : null,
            notes: $request->string('notes'),
            termsConditions: $request->string('terms_conditions'),
            items: collect($request->array('items'))
                ->map(fn($item) => CreateInvoiceItemData::fromArray($item))
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
            'invoice_date' => $this->invoiceDate->toDateString(),
            'due_date' => $this->dueDate?->toDateString(),
            'notes' => $this->notes,
            'terms_conditions' => $this->termsConditions,
        ];
    }
}