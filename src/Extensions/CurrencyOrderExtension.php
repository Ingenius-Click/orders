<?php

namespace Ingenius\Orders\Extensions;

use Ingenius\Orders\Models\Order;

/**
 * CurrencyOrderExtension
 *
 * Adds currency metadata to order responses.
 * Includes symbol, position, and exchange rate information for the frontend to display prices correctly.
 */
class CurrencyOrderExtension extends BaseOrderExtension
{
    /**
     * Add currency metadata to the order array
     *
     * @param Order $order
     * @param array $orderArray
     * @return array
     */
    public function extendOrderArray(Order $order, array $orderArray): array
    {
        // Get currency metadata using helper function (maintains package decoupling)
        $currentCurrency = get_currency_metadata();

        // Add currency information from the order
        $orderArray['currency_info'] = [
            // Current currency details (from helper - what frontend is viewing in)
            'current' => $currentCurrency,

            // Order currency details (currency at order creation time)
            'order_currency' => [
                'code' => $order->currency,
                'exchange_rate' => $order->exchange_rate,
            ],

            // Base currency details
            'base_currency' => [
                'code' => $order->current_base_currency,
            ],
        ];

        return $orderArray;
    }

    /**
     * Run with lower priority so other extensions run first
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 90; // Run later so other extensions process first
    }

    /**
     * Extension name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'currency';
    }
}
