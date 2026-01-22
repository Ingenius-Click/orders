<?php

namespace Ingenius\Orders\Actions;

use Ingenius\Orders\Enums\OrderStatusEnum;
use Ingenius\Orders\Http\Requests\CreateManualInvoiceRequest;
use Ingenius\Orders\Models\Order;

class CreateManualInvoiceAction
{
    public function handle(CreateManualInvoiceRequest $request)
    {
        $createOrderAction = app(CreateOrderAction::class);

        try {
            $result = $createOrderAction->handle($request, true);
            // Refresh order from database to ensure we have the final calculated total_amount
            $order = Order::find($result['order']->id);
        } catch (\Exception $e) {
            throw $e;
        }

        $createInvoiceAction = app(CreateInvoiceAction::class);

        try {
            $invoice = $createInvoiceAction->handle($order, $request->payment_date);
        } catch (\Exception $e) {
            throw $e;
        }

        $invoice->update(['is_manual' => true]);

        // Transition order to specified status (defaults to 'completed')
        $targetStatus = $request->order_status ?? OrderStatusEnum::COMPLETED->value;
        $order->transitionTo($targetStatus);

        return $invoice;
    }
}
