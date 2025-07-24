<?php

namespace Ingenius\Orders\Interfaces;

use Ingenius\Core\Interfaces\IOrderable;
use Ingenius\Orders\Models\Invoice;

interface InvoiceCreationInterface
{
    public function createInvoice(IOrderable $orderable, string $paid_at): Invoice;

    public function attemptToCreateInvoice(IOrderable $orderable, string $paid_at, string $status): ?Invoice;
}
