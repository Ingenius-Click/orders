<?php

namespace Ingenius\Orders\Services;

use Ingenius\Core\Interfaces\IOrderable;
use Ingenius\Orders\Actions\CreateInvoiceAction;
use Ingenius\Orders\Interfaces\InvoiceCreationInterface;
use Ingenius\Orders\Models\Invoice;
use Ingenius\Orders\Settings\InvoiceSettings;

class InvoiceService implements InvoiceCreationInterface
{
    protected InvoiceSettings $settings;
    protected CreateInvoiceAction $createInvoiceAction;

    public function __construct(InvoiceSettings $settings, CreateInvoiceAction $createInvoiceAction)
    {
        $this->settings = $settings;
        $this->createInvoiceAction = $createInvoiceAction;
    }

    public function attemptToCreateInvoice(IOrderable $orderable, string $paid_at, string $status): ?Invoice
    {
        if (!$this->settings->auto_create) {
            return null;
        }

        $orderableId = $orderable->getOrderableId();
        $orderableType = get_class($orderable);

        if (Invoice::where('orderable_id', $orderableId)->where('orderable_type', $orderableType)->exists()) {
            return null;
        }

        if ($status === $this->settings->create_on_status) {
            return $this->createInvoice($orderable, $paid_at);
        }

        if ($status === $this->settings->fallback_status) {
            return $this->createInvoice($orderable, $paid_at);
        }

        return null;
    }

    /**
     * Create an invoice for an orderable item
     *
     * @param IOrderable $orderable The orderable item
     * @param string $paid_at The date the order was paid
     * @return Invoice
     */
    public function createInvoice(IOrderable $orderable, string $paid_at): Invoice
    {
        $invoice = $this->createInvoiceAction->handle($orderable, $paid_at);

        return $invoice;
    }
}
