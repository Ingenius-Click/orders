<?php

namespace Ingenius\Orders\Statuses;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ingenius\Core\Interfaces\IInventoriable;
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
        // Restore inventory if the order was previously paid
        // Only restore if inventory was already deducted (from 'paid' or 'completed' status)
        if (in_array($previousStatusIdentifier, ['paid', 'completed'])) {
            $this->restoreInventory($order);
        }
    }

    /**
     * Restore inventory for all products in the cancelled order.
     * Only called when cancelling a paid/completed order.
     * Uses database transactions to ensure atomic operations.
     *
     * @param Order $order
     * @return void
     */
    protected function restoreInventory(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->products as $orderProduct) {
                // Get the actual product model (Product, etc.)
                $product = $orderProduct->productible;

                // Skip if product doesn't exist or doesn't implement IInventoriable
                if (!$product || !($product instanceof IInventoriable)) {
                    continue;
                }

                // Only restore stock if product handles stock management
                if (!$product->handleStock()) {
                    continue;
                }

                // Restore the stock
                $stockBefore = $product->getStock();
                $product->addStock($orderProduct->quantity);
                $stockAfter = $product->getStock();

                // Log inventory restoration for audit trail
                Log::info('Inventory restored for cancelled order', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'product_id' => $product->id,
                    'product_name' => $product->name ?? 'Unknown',
                    'product_type' => get_class($product),
                    'quantity_restored' => $orderProduct->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reason' => 'order_cancelled',
                ]);
            }
        });
    }
}
