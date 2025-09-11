<?php

namespace Ingenius\Orders\Statuses;

use Ingenius\Orders\Enums\OrderStatusEnum;
use Ingenius\Orders\Interfaces\OrderStatusInterface;
use Ingenius\Orders\Models\Order;

class NewOrderStatus implements OrderStatusInterface
{
    /**
     * Get the unique identifier for this status.
     */
    public function getIdentifier(): string
    {
        return OrderStatusEnum::NEW->value;
    }

    /**
     * Get the display name of the status.
     */
    public function getName(): string
    {
        return __('New');
    }

    /**
     * Get the description of the status.
     */
    public function getDescription(): string
    {
        return 'A new order that has been created but not yet processed.';
    }

    /**
     * Check if the order can transition to the target status.
     */
    public function canTransitionTo(string $targetStatusIdentifier, Order $order): bool
    {
        // New orders can transition to completed or cancelled
        return in_array($targetStatusIdentifier, [
            OrderStatusEnum::COMPLETED->value,
            OrderStatusEnum::CANCELLED->value
        ]);
    }

    /**
     * Called before transitioning from this status to another.
     */
    public function onExit(Order $order, string $targetStatusIdentifier): void
    {
        // Logic to execute when exiting the new status
        // For example, record the time spent in this status
    }

    /**
     * Called when transitioning to this status from another.
     */
    public function onEnter(Order $order, string $previousStatusIdentifier): void
    {
        // Logic to execute when entering the new status
        // This is typically not called for new orders as they start in this status
    }
}
