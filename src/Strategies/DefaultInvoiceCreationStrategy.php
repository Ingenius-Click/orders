<?php

namespace Ingenius\Orders\Strategies;

use Ingenius\Core\Interfaces\IOrderable;
use Ingenius\Orders\Actions\CreateInvoiceAction;
use Ingenius\Orders\Interfaces\InvoiceCreationStrategyInterface;
use Ingenius\Orders\Models\Invoice;
use Ingenius\Orders\Settings\InvoiceSettings;

class DefaultInvoiceCreationStrategy implements InvoiceCreationStrategyInterface
{
    protected InvoiceSettings $settings;
    protected CreateInvoiceAction $createInvoiceAction;

    public function __construct(InvoiceSettings $settings, CreateInvoiceAction $createInvoiceAction)
    {
        $this->settings = $settings;
        $this->createInvoiceAction = $createInvoiceAction;
    }

    public function shouldCreateInvoice(IOrderable $orderable, string $status): bool
    {
        if (!$this->settings->auto_create) {
            return false;
        }

        $orderableId = $orderable->getOrderableId();
        $orderableType = get_class($orderable);

        // Check if invoice already exists
        if (Invoice::where('orderable_id', $orderableId)->where('orderable_type', $orderableType)->exists()) {
            return false;
        }

        // Check if status matches configured statuses
        return $status === $this->settings->create_on_status ||
            $status === $this->settings->fallback_status;
    }

    public function createInvoice(IOrderable $orderable, string $paid_at): Invoice
    {
        return $this->createInvoiceAction->handle($orderable, $paid_at);
    }

    public function getPriority(): int
    {
        return 100; // Default strategy runs last
    }

    public function getName(): string
    {
        return 'DefaultInvoiceCreation';
    }
}
