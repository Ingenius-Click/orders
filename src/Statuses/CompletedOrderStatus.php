<?php

namespace Ingenius\Orders\Statuses;

use Ingenius\Orders\Enums\OrderStatusEnum;
use Ingenius\Orders\Interfaces\OrderStatusInterface;
use Ingenius\Orders\Services\InvoiceCreationManager;
use Ingenius\Orders\Models\Order;

class CompletedOrderStatus implements OrderStatusInterface
{
    /**
     * Get the unique identifier for this status.
     */
    public function getIdentifier(): string
    {
        return OrderStatusEnum::COMPLETED->value;
    }

    /**
     * Get the display name of the status.
     */
    public function getName(): string
    {
        return 'Completed';
    }

    /**
     * Get the description of the status.
     */
    public function getDescription(): string
    {
        return 'The order has been completed successfully.';
    }

    /**
     * Check if the order can transition to the target status.
     */
    public function canTransitionTo(string $targetStatusIdentifier, Order $order): bool
    {
        // Completed orders cannot transition to any other status
        return false;
    }

    /**
     * Called before transitioning from this status to another.
     */
    public function onExit(Order $order, string $targetStatusIdentifier): void
    {
        // Logic to execute when exiting the completed status
        // This should not happen as completed is a terminal status
    }

    /**
     * Called when transitioning to this status from another.
     */
    public function onEnter(Order $order, string $previousStatusIdentifier): void
    {
        // Logic to execute when entering the completed status
        // For example, send a confirmation email to the customer

        $invoiceManager = app(InvoiceCreationManager::class);
        $invoiceManager->attemptToCreateInvoice($order, now()->toDateTimeString(), $this->getIdentifier());
    }
}
