<?php

namespace App\Domain\Invoice\EventSourcing;

use App\Domain\Shared\EventSourcing\DomainEvent;

/**
 * Event sourcing event for invoice creation.
 */
class InvoiceCreatedEvent extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public readonly int $companyId,
        public readonly int $customerId,
        public readonly ?int $warehouseId,
        public readonly string $invoiceDate,
        public readonly ?string $dueDate,
        public readonly int $subtotal,
        public readonly int $taxAmount,
        public readonly int $totalAmount,
        public readonly array $items,
        int $version = 1
    ) {
        parent::__construct($aggregateId, 'invoice', $version);
    }

    public function getEventData(): array
    {
        return [
            'company_id' => $this->companyId,
            'customer_id' => $this->customerId,
            'warehouse_id' => $this->warehouseId,
            'invoice_date' => $this->invoiceDate,
            'due_date' => $this->dueDate,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->taxAmount,
            'total_amount' => $this->totalAmount,
            'items' => $this->items,
        ];
    }

    public static function fromEventData(string $aggregateId, string $aggregateType, array $data, int $version): static
    {
        return new static(
            $aggregateId,
            $data['company_id'],
            $data['customer_id'],
            $data['warehouse_id'],
            $data['invoice_date'],
            $data['due_date'],
            $data['subtotal'],
            $data['tax_amount'],
            $data['total_amount'],
            $data['items'],
            $version
        );
    }
}