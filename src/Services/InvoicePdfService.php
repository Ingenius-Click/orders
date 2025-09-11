<?php

namespace Ingenius\Orders\Services;

use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Ingenius\Orders\Models\Invoice;
use Illuminate\Http\Response;

class InvoicePdfService
{
    /**
     * Generate PDF for an invoice.
     *
     * @param Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function generatePdf(Invoice $invoice): Response
    {
        // Get invoice data including sections from all providers
        $invoiceData = $invoice->getInvoiceData();

        // Prepare data for the PDF template
        $data = [
            'invoice' => $invoice,
            'sections' => $invoiceData,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        // Debug: Log the data being passed to the template
        \Log::info('PDF Generation Data:', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'sections_count' => count($invoiceData),
            'sections' => array_map(function ($section) {
                return [
                    'title' => $section->getTitle(),
                    'properties_count' => count($section->getProperties())
                ];
            }, $invoiceData)
        ]);

        // Generate PDF using the template
        $pdf = PDF::loadView('orders::pdf.invoice', $data);

        // Set PDF options
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

        // Generate filename
        $filename = 'invoice-' . $invoice->invoice_number . '.pdf';

        // Return PDF as download response
        return $pdf->download($filename);
    }

    /**
     * Generate PDF stream for preview (inline display).
     *
     * @param Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function generatePdfStream(Invoice $invoice): Response
    {
        // Get invoice data including sections from all providers
        $invoiceData = $invoice->getInvoiceData();

        // Prepare data for the PDF template
        $data = [
            'invoice' => $invoice,
            'sections' => $invoiceData,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        // Generate PDF using the template
        $pdf = PDF::loadView('orders::pdf.invoice', $data);

        // Set PDF options
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
        ]);

        // Generate filename
        $filename = 'invoice-' . $invoice->invoice_number . '.pdf';

        // Return PDF as inline response
        return $pdf->stream($filename);
    }

    /**
     * Generate HTML for debugging purposes.
     *
     * @param Invoice $invoice
     * @return string
     */
    public function generateHtml(Invoice $invoice): string
    {
        // Get invoice data including sections from all providers
        $invoiceData = $invoice->getInvoiceData();

        // Prepare data for the PDF template
        $data = [
            'invoice' => $invoice,
            'sections' => $invoiceData,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        // Return rendered HTML for debugging
        return view('orders::pdf.invoice', $data)->render();
    }
}
