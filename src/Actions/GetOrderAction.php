<?php

namespace Ingenius\Orders\Actions;

use Ingenius\Coins\Services\CurrencyServices;
use Ingenius\Orders\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class GetOrderAction
{
    public function handle(Request $request, ?int $id = null): Order|LengthAwarePaginator
    {
        if ($id) {
            $order = Order::with('products')->findOrFail($id);

            // Warm the currency cache to prevent N+1 queries when converting amounts
            $this->warmCurrencyCache($order);

            return $order;
        }

        $query = Order::query();

        return table_handler_paginate($request->all(), $query);
    }

    /**
     * Pre-warm currency cache with currencies from the order.
     * This prevents N+1 queries when converting amounts in products, discounts, etc.
     *
     * @param Order $order
     * @return void
     */
    protected function warmCurrencyCache(Order $order): void
    {
        $currenciesToCache = [
            $order->currency,
            $order->current_base_currency,
        ];

        // Add current request currency if different
        $currentCurrency = get_current_currency();
        if (!in_array($currentCurrency, $currenciesToCache)) {
            $currenciesToCache[] = $currentCurrency;
        }

        // Warm the cache with all currencies in a single query
        CurrencyServices::warmCache($currenciesToCache);
    }
}
