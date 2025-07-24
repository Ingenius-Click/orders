<?php

namespace Ingenius\Orders\Interfaces;

use Ingenius\Orders\Data\InvoiceDataSection;
use Ingenius\Orders\Models\Invoice;

interface InvoiceDataProviderInterface
{
    /**
     * Get the invoice data sections.
     *
     * @param Invoice $invoice
     * @return array<InvoiceDataSection>
     */
    public function getInvoiceData(Invoice $invoice): array;

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the provider priority.
     *
     * @return int
     */
    public function getPriority(): int;
}
