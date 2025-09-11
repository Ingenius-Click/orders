<?php

namespace Ingenius\Orders\InvoiceData;

use Ingenius\Orders\Data\InvoiceDataSection;
use Ingenius\Orders\Interfaces\InvoiceDataProviderInterface;
use Ingenius\Orders\Models\Invoice;
use Ingenius\Orders\Models\Order;
use Ingenius\Coins\Services\CurrencyServices;

class OrderInvoiceDataProvider implements InvoiceDataProviderInterface
{
    /**
     * Get the invoice data sections.
     *
     * @param Invoice $invoice
     * @return array
     */
    public function getInvoiceData(Invoice $invoice): array
    {
        $orderable = $invoice->orderable;

        if (!$orderable || !($orderable instanceof Order)) {
            return [];
        }

        $sections = [];

        // Create Order Information section
        $orderProperties = [
            __('Order Number') => $orderable->order_number,
            __('Order Status') => __($orderable->status_name),
        ];

        $sections[] = new InvoiceDataSection(__('Order Information'), $orderProperties, 10);

        // Create Products section
        $productProperties = [];
        foreach ($orderable->products as $index => $product) {
            $productKey = __('Product') . ' ' . ($index + 1);
            $unitPrice = CurrencyServices::formatCurrency($product->base_price_per_unit_in_cents, $orderable->getBaseCurrency());
            $totalPrice = CurrencyServices::formatCurrency($product->base_total_in_cents, $orderable->getBaseCurrency());

            $productProperties[$productKey] = sprintf(
                __('%s (Qty: %d) - %s each, Total: %s'),
                $product->productible_name,
                $product->quantity,
                $unitPrice,
                $totalPrice
            );
        }

        if (!empty($productProperties)) {
            $sections[] = new InvoiceDataSection(__('Products'), $productProperties, 15);
        }

        return $sections;
    }

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'OrderInvoiceDataProvider';
    }

    /**
     * Get the provider priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 10;
    }
}
