<?php

namespace Ingenius\Orders\Settings;

use Ingenius\Core\Settings\Settings;

class CheckoutSettings extends Settings
{
    /**
     * Whether the customer must be registered (authenticated) to place an order.
     */
    public bool $require_registration_for_purchase = false;

    public static function group(): string
    {
        return 'checkout';
    }

    public static function encrypted(): array
    {
        return [];
    }
}
