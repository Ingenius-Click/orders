<?php

namespace Ingenius\Orders\Actions;

use Ingenius\Core\Interfaces\IOrderable;
use Ingenius\Core\Services\SequenceGeneratorService;
use Ingenius\Orders\Enums\InvoiceStatus;
use Ingenius\Orders\Events\InvoiceCreatedEvent;
use Ingenius\Orders\Models\Invoice;

class CreateInvoiceAction
{
    /**
     * @var SequenceGeneratorService
     */
    protected SequenceGeneratorService $sequenceGenerator;

    /**
     * CreateInvoiceAction constructor.
     *
     * @param SequenceGeneratorService $sequenceGenerator
     */
    public function __construct(SequenceGeneratorService $sequenceGenerator)
    {
        $this->sequenceGenerator = $sequenceGenerator;
    }

    public function handle(IOrderable $orderable, string $paymentDate, bool $emit = true): Invoice
    {
        if (!isset($orderable->id)) {
            throw new \Exception('Orderable ID is required');
        }

        $invoice = Invoice::create([
            'orderable_id' => $orderable->id,
            'orderable_type' => get_class($orderable),
            'invoice_number' => $this->sequenceGenerator->generateNumber('invoice'),
            'customer_name' => $orderable->getCustomerName(),
            'customer_email' => $orderable->getCustomerEmail(),
            'customer_phone' => $orderable->getCustomerPhone(),
            'customer_address' => $orderable->getCustomerAddress(),
            'currency' => $orderable->getCurrency(),
            'base_currency' => $orderable->getBaseCurrency(),
            'exchange_rate' => $orderable->getExchangeRate(),
            'total_amount' => $orderable->getTotalAmount(),
            'status' => InvoiceStatus::PAID->value,
            'items' => $orderable->getItems(),
            'payment_date' => $paymentDate,
        ]);

        if ($emit) {
            event(new InvoiceCreatedEvent($invoice));
        }

        return $invoice;
    }
}
