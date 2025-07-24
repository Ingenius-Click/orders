<?php

namespace Ingenius\Orders\Services;

use Ingenius\Orders\Interfaces\OrderStatusInterface;
use Ingenius\Orders\Models\Order;
use Ingenius\Orders\Models\OrderStatusTransition;
use Ingenius\Orders\Exceptions\InvalidStatusTransitionException;

class OrderStatusManager
{
    /**
     * Collection of registered statuses
     *
     * @var array<string, OrderStatusInterface>
     */
    protected array $statuses = [];

    /**
     * Register a new order status
     *
     * @param OrderStatusInterface $status
     * @return void
     */
    public function register(OrderStatusInterface $status): void
    {
        $this->statuses[$status->getIdentifier()] = $status;
    }

    /**
     * Get all registered statuses
     *
     * @return array<string, OrderStatusInterface>
     */
    public function getStatuses(): array
    {
        return $this->statuses;
    }

    /**
     * Get a specific status by identifier
     *
     * @param string $identifier
     * @return OrderStatusInterface|null
     */
    public function getStatus(string $identifier): ?OrderStatusInterface
    {
        return $this->statuses[$identifier] ?? null;
    }

    /**
     * Check if a status transition is allowed
     *
     * @param string $fromStatus
     * @param string $toStatus
     * @param Order $order
     * @return bool
     */
    public function canTransition(string $fromStatus, string $toStatus, Order $order): bool
    {
        // First check if the transition is allowed in the database
        $transition = OrderStatusTransition::where('from_status', $fromStatus)
            ->where('to_status', $toStatus)
            ->where('is_enabled', true)
            ->first();

        if ($transition) {
            return true;
        }

        // If no database record, check with the status implementation
        $fromStatusObj = $this->getStatus($fromStatus);

        if (!$fromStatusObj) {
            return false;
        }

        // Check if the transition is allowed
        return $fromStatusObj->canTransitionTo($toStatus, $order);
    }

    /**
     * Transition an order from one status to another
     *
     * @param Order $order
     * @param string $toStatus
     * @return Order
     * @throws InvalidStatusTransitionException
     */
    public function transition(Order $order, string $toStatus): Order
    {
        $fromStatus = $order->status;

        // Check if the transition is allowed
        if (!$this->canTransition($fromStatus, $toStatus, $order)) {
            throw new InvalidStatusTransitionException("Cannot transition from {$fromStatus} to {$toStatus}");
        }

        // Get status objects
        $fromStatusObj = $this->getStatus($fromStatus);
        $toStatusObj = $this->getStatus($toStatus);

        if (!$fromStatusObj || !$toStatusObj) {
            throw new InvalidStatusTransitionException("One or both statuses not found: {$fromStatus}, {$toStatus}");
        }

        // Execute transition hooks
        $fromStatusObj->onExit($order, $toStatus);
        $toStatusObj->onEnter($order, $fromStatus);

        // Update the order status
        $order->status = $toStatus;
        $order->save();

        return $order;
    }

    /**
     * Get all allowed transitions for a status
     *
     * @param string $fromStatus
     * @param Order|null $order
     * @return array
     */
    public function getAllowedTransitions(string $fromStatus, ?Order $order = null): array
    {
        // Get transitions from the database
        $dbTransitions = OrderStatusTransition::where('from_status', $fromStatus)
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->pluck('to_status')
            ->toArray();

        // Get code-defined transitions
        $codeTransitions = [];
        $fromStatusObj = $this->getStatus($fromStatus);

        if ($fromStatusObj) {
            // Use the provided order or create a mock one
            $orderToCheck = $order ?? new Order(['status' => $fromStatus]);

            foreach ($this->statuses as $identifier => $status) {
                if ($fromStatusObj->canTransitionTo($identifier, $orderToCheck)) {
                    $codeTransitions[] = $identifier;
                }
            }
        }

        // Combine both sets of transitions and remove duplicates
        return array_unique(array_merge($dbTransitions, $codeTransitions));
    }
}
