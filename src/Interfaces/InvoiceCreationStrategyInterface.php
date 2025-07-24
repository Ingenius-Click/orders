<?php

namespace Ingenius\Orders\Interfaces;

use Ingenius\Core\Interfaces\IOrderable;
use Ingenius\Orders\Models\Invoice;

interface InvoiceCreationStrategyInterface
{
    /**
     * Check if this strategy should handle the invoice creation for the given orderable and status
     */
    public function shouldCreateInvoice(IOrderable $orderable, string $status): bool;

    /**
     * Create an invoice for an orderable item
     */
    public function createInvoice(IOrderable $orderable, string $paid_at): Invoice;

    /**
     * Get the priority of this strategy (lower numbers run first)
     */
    public function getPriority(): int;

    /**
     * Get the name of this strategy
     */
    public function getName(): string;
}
