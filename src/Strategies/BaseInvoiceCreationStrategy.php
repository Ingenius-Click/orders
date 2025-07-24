<?php

namespace Ingenius\Orders\Strategies;

use Ingenius\Core\Interfaces\IOrderable;
use Ingenius\Orders\Interfaces\InvoiceCreationStrategyInterface;
use Ingenius\Orders\Models\Invoice;

abstract class BaseInvoiceCreationStrategy implements InvoiceCreationStrategyInterface
{
    /**
     * Default implementation checks if invoice already exists
     */
    protected function invoiceAlreadyExists(IOrderable $orderable): bool
    {
        $orderableId = $orderable->getOrderableId();
        $orderableType = get_class($orderable);

        return Invoice::where('orderable_id', $orderableId)
            ->where('orderable_type', $orderableType)
            ->exists();
    }

    /**
     * Default priority (middle)
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * Default name is the class name
     */
    public function getName(): string
    {
        $className = get_class($this);
        $parts = explode('\\', $className);
        return end($parts);
    }

    /**
     * Abstract method that must be implemented by subclasses
     */
    abstract public function shouldCreateInvoice(IOrderable $orderable, string $status): bool;

    /**
     * Abstract method that must be implemented by subclasses
     */
    abstract public function createInvoice(IOrderable $orderable, string $paid_at): Invoice;
}
