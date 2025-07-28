<?php

namespace Ingenius\Orders\Policies;

use Ingenius\Orders\Constants\OrderPermissions;
use Ingenius\Orders\Models\Order;

class OrderPolicy
{
    public function viewAny($user): bool
    {
        $userClass = tenant_user_class();

        if ($user && is_object($user) && is_a($user, $userClass)) {
            return $user->can(OrderPermissions::ORDER_VIEW_ANY);
        }

        return false;
    }

    public function view($user, Order $order): bool
    {
        if (!$user) {
            return $order->session_id === session()->getId();
        }

        $userClass = tenant_user_class();

        if (is_object($user) && is_a($user, $userClass)) {
            return $user->can(OrderPermissions::ORDER_VIEW_ANY) || ($order->userable_id === $user->id && $order->userable_type === get_class($user));
        }

        return false;
    }

    public function delete($user, Order $_): bool
    {
        $userClass = tenant_user_class();

        if ($user && is_object($user) && is_a($user, $userClass)) {
            return $user->can(OrderPermissions::ORDER_DELETE);
        }

        return false;
    }

    public function changeStatus($user, Order $_): bool
    {
        $userClass = tenant_user_class();

        if ($user && is_object($user) && is_a($user, $userClass)) {
            return $user->can(OrderPermissions::ORDER_CHANGE_STATUS);
        }

        return false;
    }
}
