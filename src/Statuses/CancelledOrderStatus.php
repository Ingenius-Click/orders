<?php

namespace Ingenius\Orders\Statuses;

use Ingenius\Orders\Enums\OrderStatusEnum;
use Ingenius\Orders\Interfaces\OrderStatusInterface;
use Ingenius\Orders\Models\Order;

class CancelledOrderStatus implements OrderStatusInterface
{
    /**
     * Get the unique identifier for this status.
     */
    public function getIdentifier(): string
    {
        return OrderStatusEnum::CANCELLED->value;
    }

    /**
     * Get the display name of the status.
     */
    public function getName(): string
    {
        return __('Cancelled');
    }

    /**
     * Get the description of the status.
     */
    public function getDescription(): string
    {
        return 'The order has been cancelled and will not be processed.';
    }

    /**
     * Check if the order can transition to the target status.
     */
    public function canTransitionTo(string $targetStatusIdentifier, Order $order): bool
    {
        // Cancelled orders cannot transition to any other status
        return false;
    }

    /**
     * Called before transitioning from this status to another.
     */
    public function onExit(Order $order, string $targetStatusIdentifier): void
    {
        // Logic to execute when exiting the cancelled status
        // This should not happen as cancelled is a terminal status
    }

    /**
     * Called when transitioning to this status from another.
     */
    public function onEnter(Order $order, string $previousStatusIdentifier): void
    {
        // Logic to execute when entering the cancelled status
        // For example, send a cancellation notification to the customer
    }
}
