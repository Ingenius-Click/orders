<?php

namespace Ingenius\Orders\Interfaces;

use Ingenius\Orders\Models\Order;

interface OrderStatusInterface
{
    /**
     * Get the unique identifier for this status.
     */
    public function getIdentifier(): string;

    /**
     * Get the display name of the status.
     */
    public function getName(): string;

    /**
     * Get the description of the status.
     */
    public function getDescription(): string;

    /**
     * Check if the order can transition to the target status.
     */
    public function canTransitionTo(string $targetStatusIdentifier, Order $order): bool;

    /**
     * Called before transitioning from this status to another.
     */
    public function onExit(Order $order, string $targetStatusIdentifier): void;

    /**
     * Called when transitioning to this status from another.
     */
    public function onEnter(Order $order, string $previousStatusIdentifier): void;
}
