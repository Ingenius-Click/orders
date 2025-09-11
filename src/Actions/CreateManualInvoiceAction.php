<?php

namespace Ingenius\Orders\Actions;

use Illuminate\Support\Facades\Config;
use Ingenius\Orders\Http\Requests\CreateManualInvoiceRequest;
use Ingenius\Orders\Models\Order;

class CreateManualInvoiceAction
{
    public function handle(CreateManualInvoiceRequest $request)
    {
        $createOrderAction = app(CreateOrderAction::class);

        try {
            $result = $createOrderAction->handle($request, true);
            $order = $result['order'];
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

        return $invoice;
    }
}
