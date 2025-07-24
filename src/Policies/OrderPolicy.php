<?php

namespace Ingenius\Orders\Policies;

use Ingenius\Auth\Models\User;
use Ingenius\Orders\Constants\OrderPermissions;
use Ingenius\Orders\Models\Order;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(OrderPermissions::ORDER_VIEW_ANY);
    }

    public function view(?User $user, Order $order): bool
    {
        if (!$user) {
            return $order->session_id === session()->getId();
        }

        return $user->can(OrderPermissions::ORDER_VIEW_ANY) || ($order->userable_id === $user->id && $order->userable_type === get_class($user));
    }

    public function delete(User $user, Order $_): bool
    {
        return $user->can(OrderPermissions::ORDER_DELETE);
    }

    public function changeStatus(User $user, Order $_): bool
    {
        return $user->can(OrderPermissions::ORDER_CHANGE_STATUS);
    }
}
