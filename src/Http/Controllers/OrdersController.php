<?php

namespace Ingenius\Orders\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ingenius\Auth\Helpers\AuthHelper;
use Ingenius\Core\Http\Controllers\Controller;
use Ingenius\Orders\Actions\ChangeOrderStatusAction;
use Ingenius\Orders\Actions\CreateOrderAction;
use Ingenius\Orders\Actions\DeleteOrderAction;
use Ingenius\Orders\Actions\GetOrderAction;
use Ingenius\Orders\Exceptions\InvalidStatusTransitionException;
use Ingenius\Orders\Exceptions\NoProductsFoundException;
use Ingenius\Orders\Http\Requests\ChangeOrderStatusRequest;
use Ingenius\Orders\Http\Requests\CreateOrderRequest;
use Ingenius\Orders\Models\Order;

class OrdersController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, GetOrderAction $action): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'viewAny', Order::class);

        $orders = $action->handle($request);

        return response()->api(data: $orders, message: 'Orders fetched successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateOrderRequest $request, CreateOrderAction $action): JsonResponse
    {
        try {
            $order = $action->handle($request);
        } catch (NoProductsFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }

        return response()->api(data: $order, message: 'Order created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id, Request $request, GetOrderAction $action): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'view', Order::find($id));

        $order = $action->handle($request, $id);

        return response()->api(data: $order, message: 'Order fetched successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id, DeleteOrderAction $action): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'delete', Order::find($id));

        $result = $action->handle($id);

        return response()->api(data: $result, message: $result ? 'Order deleted successfully' : 'Order not found', code: $result ? 200 : 404);
    }

    public function changeStatus(ChangeOrderStatusRequest $request, int $id, ChangeOrderStatusAction $action): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'changeStatus', Order::find($id));

        $validated = $request->validated();

        try {
            $order = $action->handle($id, $validated['status']);
        } catch (InvalidStatusTransitionException $e) {
            return response()->api(message: $e->getMessage(), code: 400);
        } catch (\Exception $e) {
            return response()->api(message: $e->getMessage(), code: 500);
        }

        return response()->api(data: $order, message: 'Order status changed successfully');
    }
}
