<?php

namespace Ingenius\Orders\QueriesDrawer;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BestSellingProductsQuery 
{
    public function handle(Builder $inQuery = null, array $statuses = ['completed']): Builder {

        $productModel = config('orders.productible_models.product');
        $query = $inQuery ? $inQuery : $productModel::query();

        $query->select($productModel::make()->getTable() . '.*', DB::raw('SUM(order_products.quantity) as total_quantity_sold'))
            ->join('order_products', function ($join) use ($productModel) {
                $join
                    ->on('order_products.productible_id', '=', $productModel::make()->getTable() . '.id')
                    ->where('order_products.productible_type', '=', $productModel);
            })
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->whereIn('orders.status', $statuses)
            ->groupBy($productModel::make()->getTable() . '.id')
            ->orderByDesc('total_quantity_sold');
            ;

        return $query;
    }
}