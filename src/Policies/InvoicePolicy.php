<?php

namespace Ingenius\Orders\Policies;

use Ingenius\Orders\Constants\InvoicePermissions;
use Ingenius\Orders\Constants\OrderPermissions;
use Ingenius\Orders\Models\Invoice;

class InvoicePolicy
{
    public function view($user, Invoice $invoice): bool
    {
        $userClass = tenant_user_class();

        if ($user && is_object($user) && is_a($user, $userClass)) {
            return $user->can(InvoicePermissions::INVOICE_VIEW);
        }

        return false;
    }
}
