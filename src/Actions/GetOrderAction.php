<?php

namespace Ingenius\Orders\Actions;

use Ingenius\Orders\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class GetOrderAction
{
    public function handle(Request $request, ?int $id = null): Order|Collection|LengthAwarePaginator
    {
        if ($id) {
            return Order::with('products')->findOrFail($id);
        }

        $query = Order::query();

        // Apply filters
        if ($request->has('customer_email')) {
            $query->where('customer_email', $request->input('customer_email'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Return paginated results or all
        return $request->has('per_page')
            ? $query->with('products')->paginate($request->input('per_page'))
            : $query->with('products')->get();
    }
}
