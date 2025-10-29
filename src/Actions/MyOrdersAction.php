<?php

namespace Ingenius\Orders\Actions;

use Ingenius\Orders\Models\Order;

class MyOrdersAction {

    public function handle(array $filters = [], $userId) {

        $user_class = tenant_user_class();

        $query = Order::query()->where('userable_id', $userId)->where('userable_type', $user_class);

        return table_handler_paginate($filters, $query);
    }

}