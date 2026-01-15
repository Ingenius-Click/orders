<?php

namespace Ingenius\Orders\Statuses;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ingenius\Core\Interfaces\IInventoriable;
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
        return __('Completed');
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
        // Create invoice
        $invoiceManager = app(InvoiceCreationManager::class);
        $invoiceManager->attemptToCreateInvoice($order, now()->toDateTimeString(), $this->getIdentifier());

        // Deduct inventory only if transitioning from 'new' status
        // If coming from 'paid', inventory was already deducted
        if ($previousStatusIdentifier === 'new') {
            $this->deductInventory($order);
        }
    }

    /**
     * Deduct inventory for all products in the order.
     * Only called when completing an order directly from 'new' status (skipping 'paid').
     * Uses database transactions to ensure atomic operations.
     *
     * @param Order $order
     * @return void
     */
    protected function deductInventory(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->products as $orderProduct) {
                // Get the actual product model (Product, etc.)
                $product = $orderProduct->productible;

                // Skip if product doesn't exist or doesn't implement IInventoriable
                if (!$product || !($product instanceof IInventoriable)) {
                    continue;
                }

                // Only deduct stock if product handles stock management
                if (!$product->handleStock()) {
                    continue;
                }

                // Validate sufficient stock before deduction
                if (!$product->hasEnoughStock($orderProduct->quantity)) {
                    Log::warning('Insufficient stock for order product during completion', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'product_id' => $product->id,
                        'product_name' => $product->name ?? 'Unknown',
                        'quantity_ordered' => $orderProduct->quantity,
                        'stock_available' => $product->getStock(),
                    ]);
                }

                // Deduct the stock
                $stockBefore = $product->getStock();
                $product->removeStock($orderProduct->quantity);
                $stockAfter = $product->getStock();

                // Log inventory deduction for audit trail
                Log::info('Inventory deducted for completed order', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'product_id' => $product->id,
                    'product_name' => $product->name ?? 'Unknown',
                    'product_type' => \get_class($product),
                    'quantity_deducted' => $orderProduct->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'previous_status' => 'new',
                ]);
            }
        });
    }
}
