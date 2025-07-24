<?php

namespace Ingenius\Orders\Enums;

enum OrderStatusEnum: string
{
    case NEW = 'new';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Get the string value of the enum
     */
    public function toString(): string
    {
        return $this->value;
    }
}
