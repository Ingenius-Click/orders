<?php

namespace Ingenius\Orders\Services;

use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Ingenius\Coins\Services\CurrencyServices;
use Ingenius\Orders\Models\Order;
use Illuminate\Http\Response;

class OrderPdfService
{
    /**
     * Extract the free-form notes stored in the order metadata JSON, if any.
     */
    public static function extractNotes(?string $metadataJson): ?string
    {
        if (!$metadataJson) {
            return null;
        }

        $metadata = json_decode($metadataJson, true) ?? [];

        return $metadata['notes'] ?? null;
    }

    /**
     * Determine whether the order's payment has already been settled.
     */
    public static function isPaid(?array $payment): bool
    {
        return in_array($payment['status'] ?? null, ['approved', 'manual'], true);
    }

    /**
     * Extract the payform payment data from the order array, if any.
     */
    public static function extractPayment(array $orderArray): ?array
    {
        return $orderArray['payform'] ?? null;
    }

    /**
     * Build the data array passed to the PDF template.
     */
    protected function buildData(Order $order): array
    {
        $currency = $order->getCurrency();

        $items = array_map(function (array $item) use ($currency) {
            return [
                'sku' => $item['productible_sku'],
                'name' => $item['productible_name'],
                'quantity' => $item['quantity'],
                'unit_price_formatted' => CurrencyServices::formatCurrency($item['base_price_per_unit_in_cents'], $currency),
                'total_price_formatted' => CurrencyServices::formatCurrency($item['base_total_in_cents'], $currency),
            ];
        }, $order->getItems());

        $orderArray = $order->toArray();
        $payment = self::extractPayment($orderArray);

        return [
            'order' => $order,
            'items' => $items,
            'shipment' => $orderArray['shipment'] ?? null,
            'payment' => $payment,
            'notes' => self::extractNotes($order->metadata),
            'is_paid' => self::isPaid($payment),
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Generate PDF for an order.
     */
    public function generatePdf(Order $order): Response
    {
        $pdf = PDF::loadView('orders::pdf.order', $this->buildData($order));

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => false,
            'debugCss' => false,
            'debugLayout' => false,
            'debugLayoutLines' => false,
            'debugLayoutBlocks' => false,
            'debugLayoutInline' => false,
        ]);

        $filename = 'order-' . $order->order_number . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Generate PDF stream for preview (inline display).
     */
    public function generatePdfStream(Order $order): Response
    {
        $pdf = PDF::loadView('orders::pdf.order', $this->buildData($order));

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
        ]);

        $filename = 'order-' . $order->order_number . '.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Generate HTML for debugging purposes.
     */
    public function generateHtml(Order $order): string
    {
        return view('orders::pdf.order', $this->buildData($order))->render();
    }
}
