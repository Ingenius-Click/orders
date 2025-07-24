<?php

namespace Ingenius\Orders\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ingenius\Orders\Models\Order;

class OrderStatusChangedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private Order $order;
    private string $previousStatus;
    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, string $previousStatus)
    {
        $this->order = $order;
        $this->previousStatus = $previousStatus;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getPreviousStatus(): string
    {
        return $this->previousStatus;
    }

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
