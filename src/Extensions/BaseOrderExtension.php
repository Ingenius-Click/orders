<?php

namespace Ingenius\Orders\Extensions;

use Illuminate\Http\Request;
use Ingenius\Core\Interfaces\IOrderable;
use Ingenius\Orders\Interfaces\OrderExtensionInterface;
use Ingenius\Orders\Models\Order;

abstract class BaseOrderExtension implements OrderExtensionInterface
{
    /**
     * Default implementation returns no validation rules
     */
    public function getValidationRules(Request $request): array
    {
        return [];
    }

    /**
     * Default implementation returns empty array (no additional data)
     */
    public function processOrder(Order $order, array $validatedData, array &$context): array
    {
        return [];
    }

    /**
     * Default implementation returns the same subtotal
     */
    public function calculateSubtotal(Order $order, float $currentSubtotal, array &$context): float
    {
        return $currentSubtotal;
    }

    /**
     * Default implementation returns the same array
     */
    public function extendOrderArray(Order $order, array $orderArray): array
    {
        return $orderArray;
    }

    /**
     * Default priority (middle)
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * Default name is the class name
     */
    public function getName(): string
    {
        $className = get_class($this);
        $parts = explode('\\', $className);
        return end($parts);
    }
}
