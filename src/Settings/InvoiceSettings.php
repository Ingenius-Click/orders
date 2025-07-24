<?php

namespace Ingenius\Orders\Settings;

use Ingenius\Core\Settings\Settings;

class InvoiceSettings extends Settings
{
    /**
     * Whether to create invoices automatically
     */
    public bool $auto_create = true;

    /**
     * The status that triggers invoice creation when orders are completed
     */
    public string $create_on_status = 'completed';

    /**
     * The fallback status to use if no status is provided
     */
    public string $fallback_status = 'completed';

    /**
     * Whether to create invoices when orders are paid
     */
    public bool $create_on_paid = true;

    /**
     * Get the group name for the settings class.
     *
     * @return string
     */
    public static function group(): string
    {
        return 'invoices';
    }

    /**
     * Get the properties that should be encrypted.
     *
     * @return array
     */
    public static function encrypted(): array
    {
        return [];
    }
}
