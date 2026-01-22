<?php

namespace Ingenius\Orders\Http\Requests;

use Ingenius\Orders\Services\OrderStatusManager;

class CreateManualInvoiceRequest extends CreateOrderRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $orderStatusManager = app(OrderStatusManager::class);
        $validStatuses = implode(',', array_keys($orderStatusManager->getStatuses()));

        $manualInvoiceRules = [
            'payment_date' => 'required|date',
            // Price overrides for manual invoices (keyed by productible_id, values in cents)
            'product_price_overrides' => 'nullable|array',
            'product_price_overrides.*' => 'integer|min:0',
            // Fixed shipping price override in cents (bypasses shipping method calculation)
            'shipping_price_override' => 'nullable|integer|min:0',
            // Order status to set after invoice creation (defaults to 'completed')
            'order_status' => 'nullable|string|in:' . $validStatuses,
        ];

        return array_merge($manualInvoiceRules, $rules);
    }
}
