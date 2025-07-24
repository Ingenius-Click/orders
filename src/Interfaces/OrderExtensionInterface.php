<?php

namespace Ingenius\Orders\Interfaces;

use Illuminate\Http\Request;
use Ingenius\Orders\Models\Order;

interface OrderExtensionInterface
{
    /**
     * Get additional validation rules for order creation
     *
     * @return array<string, mixed>
     */
    public function getValidationRules(Request $request): array;

    /**
     * Process the order after creation, with access to a shared context
     *
     * @param Order $order The order being processed
     * @param array $validatedData The validated request data
     * @param array $context Shared context data that can be modified
     * @return array Additional data to be returned to the client
     */
    public function processOrder(Order $order, array $validatedData, array &$context): array;

    /**
     * Calculate subtotal modifications
     *
     * @param Order $order The order
     * @param float $currentSubtotal The current subtotal
     * @param array $context Shared context data
     * @return float The modified subtotal
     */
    public function calculateSubtotal(Order $order, float $currentSubtotal, array &$context): float;

    /**
     * Extend the order array with additional data
     *
     * @param Order $order The order
     * @param array $orderArray The current order array
     * @return array The modified order array
     */
    public function extendOrderArray(Order $order, array $orderArray): array;

    /**
     * Get the priority of this extension
     * Lower numbers run first
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Get the name of this extension
     *
     * @return string
     */
    public function getName(): string;
}
