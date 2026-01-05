<?php

namespace Ingenius\Orders\Notifications\Resolvers;

use Ingenius\Core\Interfaces\RecipientResolverInterface;
use Ingenius\Core\Support\Recipient;
use Ingenius\Orders\Models\Order;
use Ingenius\Orders\Events\OrderCreatedEvent;
use Ingenius\Orders\Events\OrderStatusChangedEvent;

class OrderRecipientResolver implements RecipientResolverInterface
{
    /**
     * Resolve recipients for order-related notifications
     *
     * @return array<Recipient> Array of Recipient objects
     */
    public function resolve($event): array
    {
        $order = $this->extractOrder($event);

        if (!$order) {
            return [];
        }

        // Create recipient from order customer data
        $recipient = new Recipient(
            name: $order->customer_name,
            email: $order->customer_email,
            isCustomer: true,
            phone: $order->customer_phone
        );

        return [$recipient];
    }

    /**
     * Transform event into notification-ready data
     *
     * @param object $event
     * @return array
     */
    public function getNotificationData($event): array
    {
        $order = $this->extractOrder($event);

        if (!$order) {
            return [];
        }

        // Get full order data including extensions (shipment, payment, discounts, etc.)
        $fullOrderData = $order->toArray();

        // Build notification data structure (using objects for template compatibility)
        return [
            'order' => (object) [
                'order_number' => $order->order_number,
                'id' => $order->id,
                'status' => $order->status,
                'status_name' => $order->status_name,
                'currency' => $order->currency ?? config('app.currency', 'USD'),
                'subtotal' => $order->base_total_amount_formatted,
                'total' => $order->total_amount_formatted,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'created_at_formatted' => $order->created_at?->format('d/m/Y H:i'),
                'previous_status' => $event instanceof OrderStatusChangedEvent ? $event->getPreviousStatus() : null,
            ],
            'customer' => (object) [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
                'address' => $order->customer_address,
            ],
            'items' => $this->transformProducts($order),

            // Extension data - automatically populated by ExtensionManager
            // These will only be present if the corresponding packages are registered
            'shipment' => $fullOrderData['shipment'] ?? null,
            'payment' => $fullOrderData['payform'] ?? null,
            'discounts' => $fullOrderData['discounts'] ?? null,

            // Additional context for specific events
            'event_type' => $this->getEventType($event),
        ];
    }

    /**
     * Extract order from event (shared logic)
     *
     * @param object $event
     * @return Order|null
     */
    protected function extractOrder(object $event): ?Order
    {
        // Check if event has getOrder() method
        if (!method_exists($event, 'getOrder')) {
            return null;
        }

        $order = $event->getOrder();

        // Ensure we have a valid Order instance
        if (!$order instanceof Order) {
            return null;
        }

        // Load relationships if not already loaded
        if (!$order->relationLoaded('products')) {
            $order->load('products.productible');
        }

        return $order;
    }

    /**
     * Transform products into array format
     *
     * @param Order $order
     * @return array
     */
    protected function transformProducts(Order $order): array
    {
        if (!$order->relationLoaded('products') || !$order->products) {
            return [];
        }

        return $order->products->map(fn($orderProduct) => [
            'name' => $orderProduct->productible_name,
            'quantity' => $orderProduct->quantity,
            'price_in_cents' => $orderProduct->price_per_unit_in_cents,
            'price' => $orderProduct->price_per_unit_formatted,
            'total_in_cents' => $orderProduct->total_in_cents,
            'total' => $orderProduct->total_formatted,
        ])->toArray();
    }

    /**
     * Get event type identifier
     *
     * @param object $event
     * @return string
     */
    protected function getEventType(object $event): string
    {
        return match (\get_class($event)) {
            OrderCreatedEvent::class => 'order_created',
            OrderStatusChangedEvent::class => 'order_status_changed',
            default => 'order_event',
        };
    }
}
