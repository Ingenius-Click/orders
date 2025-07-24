<?php

namespace Ingenius\Orders\Policies;

use Ingenius\Auth\Models\User;
use Ingenius\Orders\Constants\InvoicePermissions;
use Ingenius\Orders\Constants\OrderPermissions;
use Ingenius\Orders\Models\Invoice;

class InvoicePolicy
{
    public function view(?User $user, Invoice $invoice): bool
    {
        return $user->can(InvoicePermissions::INVOICE_VIEW);
    }
}
