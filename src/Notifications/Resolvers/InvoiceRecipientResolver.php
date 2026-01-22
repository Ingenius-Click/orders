<?php

namespace Ingenius\Orders\Notifications\Resolvers;

use Ingenius\Core\Interfaces\RecipientResolverInterface;
use Ingenius\Core\Support\Recipient;
use Ingenius\Orders\Models\Invoice;

class InvoiceRecipientResolver implements RecipientResolverInterface
{
    /**
     * Resolve recipients for invoice-related notifications
     *
     * @return array<Recipient> Array of Recipient objects
     */
    public function resolve($event): array
    {
        $invoice = $this->extractInvoice($event);

        if (!$invoice) {
            return [];
        }

        // Create recipient from invoice customer data
        $recipient = new Recipient(
            name: $invoice->customer_name,
            email: $invoice->customer_email,
            isCustomer: true,
            phone: $invoice->customer_phone
        );

        return [$recipient];
    }

    /**
     * Transform event into notification-ready data
     *
     * @param object $event
     * @return array
     */
    public function getNotificationData($event): array
    {
        $invoice = $this->extractInvoice($event);

        if (!$invoice) {
            return [];
        }

        // Load the order relationship
        $order = $invoice->orderable;

        // Get full order data including extensions (shipment, payment, discounts, etc.)
        $fullOrderData = $order ? $order->toArray() : [];

        // Build notification data structure (using objects for template compatibility)
        return [
            'invoice' => (object) [
                'invoice_number' => $invoice->invoice_number,
                'id' => $invoice->id,
                'payment_date' => $invoice->payment_date,
                'payment_date_formatted' => $invoice->payment_date ? \Carbon\Carbon::parse($invoice->payment_date)->format('d/m/Y H:i') : 'N/A',
                'total' => $invoice->total_amount_current_formatted,
                'currency' => $invoice->currency ?? config('app.currency', 'USD'),
                'status' => $invoice->status,
                'status_name' => __($invoice->status?->value),
            ],
            'order' => $order ? (object) [
                'order_number' => $order->order_number,
                'id' => $order->id,
                'status' => $order->status,
                'status_name' => $order->status_name,
                'currency' => $order->currency ?? config('app.currency', 'USD'),
                'subtotal' => $order->base_total_amount_formatted,
                'total' => $order->total_amount_formatted,
                'created_at' => $order->created_at,
                'created_at_formatted' => $order->created_at?->format('d/m/Y H:i'),
            ] : null,
            'customer' => (object) [
                'name' => $invoice->customer_name,
                'email' => $invoice->customer_email,
                'phone' => $invoice->customer_phone,
                'address' => $invoice->customer_address,
            ],
            'items' => $this->transformItems($invoice),

            // Extension data from order - automatically populated by ExtensionManager
            // These will only be present if the corresponding packages are registered
            'shipment' => $fullOrderData['shipment'] ?? null,
            'payment' => $fullOrderData['payform'] ?? null,
            'discounts' => $fullOrderData['discounts'] ?? null,

            // Additional context for invoice events
            'event_type' => 'invoice_created',
        ];
    }

    /**
     * Extract invoice from event (shared logic)
     *
     * @param object $event
     * @return Invoice|null
     */
    protected function extractInvoice(object $event): ?Invoice
    {
        // Check if event has getInvoice() method
        if (!method_exists($event, 'getInvoice')) {
            return null;
        }

        $invoice = $event->getInvoice();

        // Ensure we have a valid Invoice instance
        if (!$invoice instanceof Invoice) {
            return null;
        }

        // Load relationships if not already loaded
        if (!$invoice->relationLoaded('orderable')) {
            $invoice->load('orderable');
        }

        return $invoice;
    }

    /**
     * Transform invoice items into array format
     *
     * @param Invoice $invoice
     * @return array
     */
    protected function transformItems(Invoice $invoice): array
    {
        $items = $invoice->items ?? [];

        if (!is_array($items) || empty($items)) {
            return [];
        }

        return collect($items)->map(function($item) use ($invoice) {
            // Get product name from productible_name field (stored at invoice creation)
            // Fallback to metadata for backward compatibility with old invoices
            $productName = $item['productible_name']
                ?? $item['metadata']['name']
                ?? $item['metadata']['product_name']
                ?? 'N/A';

            // Calculate price in current currency
            $pricePerUnitConverted = ($item['base_price_per_unit_in_cents'] ?? 0) * $invoice->exchange_rate;
            $totalConverted = ($item['base_total_in_cents'] ?? 0) * $invoice->exchange_rate;

            return [
                'name' => $productName,
                'quantity' => $item['quantity'] ?? 1,
                'price_in_cents' => $pricePerUnitConverted,
                'price' => \Ingenius\Coins\Services\CurrencyServices::formatCurrency($pricePerUnitConverted, $invoice->currency),
                'total_in_cents' => $totalConverted,
                'total' => \Ingenius\Coins\Services\CurrencyServices::formatCurrency($totalConverted, $invoice->currency),
            ];
        })->toArray();
    }
}
