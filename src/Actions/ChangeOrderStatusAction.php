<?php

namespace Ingenius\Orders\Actions;

use Ingenius\Orders\Events\OrderStatusChangedEvent;
use Ingenius\Orders\Models\Order;
use Ingenius\Orders\Services\OrderStatusManager;

class ChangeOrderStatusAction
{

    private OrderStatusManager $orderStatusManager;

    public function __construct(OrderStatusManager $orderStatusManager)
    {
        $this->orderStatusManager = $orderStatusManager;
    }

    public function handle(int $id, string $status, bool $emitEvents = true): Order
    {
        $order = Order::findOrFail($id);

        $previousStatus = $order->status;

        $order = $this->orderStatusManager->transition($order, $status);

        if ($emitEvents) {
            event(new OrderStatusChangedEvent($order, $previousStatus));
        }

        return $order;
    }
}
