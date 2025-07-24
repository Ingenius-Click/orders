<?php

namespace Ingenius\Orders\Actions;

use Ingenius\Orders\Events\OrderDeletedEvent;
use Ingenius\Orders\Models\Order;

class DeleteOrderAction
{
    public function handle(int $id, bool $emitEvents = true): bool
    {
        $order = Order::findOrFail($id);

        // Delete related products (this should be handled by cascadeOnDelete, but just to be safe)
        $order->products()->delete();

        $deleted = $order->delete();

        if ($emitEvents) {
            event(new OrderDeletedEvent($order));
        }

        return $deleted;
    }
}
