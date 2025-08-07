<?php

namespace Ingenius\Orders\Actions;

use Ingenius\Orders\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class GetOrderAction
{
    public function handle(Request $request, ?int $id = null): Order|LengthAwarePaginator
    {
        if ($id) {
            return Order::with('products')->findOrFail($id);
        }

        $query = Order::query();

        return table_handler_paginate($request->all(), $query);
    }
}
