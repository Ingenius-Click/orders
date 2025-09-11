<?php

namespace Ingenius\Orders\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ingenius\Core\Helpers\AuthHelper;
use Ingenius\Core\Http\Controllers\Controller;
use Ingenius\Orders\Actions\ListInvoicesAction;
use Ingenius\Orders\Models\Invoice;
use Ingenius\Orders\Services\InvoicePdfService;

class InvoicesController extends Controller
{
    use AuthorizesRequests;

    public function show(Invoice $invoice): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'view', $invoice);

        return response()->api(data: $invoice, message: 'Invoice retrieved successfully');
    }

    public function index(Request $request, ListInvoicesAction $listInvoicesAction): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'viewAny', Invoice::class);

        $invoices = $listInvoicesAction($request->all());

        return response()->api(data: $invoices, message: 'Invoices retrieved successfully');
    }

    /**
     * Download invoice as PDF.
     *
     * @param Invoice $invoice
     * @param InvoicePdfService $pdfService
     * @return Response
     */
    public function downloadPdf(Invoice $invoice, InvoicePdfService $pdfService): Response
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'view', $invoice);

        return $pdfService->generatePdf($invoice);
    }

    /**
     * Preview invoice as PDF (inline).
     *
     * @param Invoice $invoice
     * @param InvoicePdfService $pdfService
     * @return Response
     */
    public function previewPdf(Invoice $invoice, InvoicePdfService $pdfService): Response
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'view', $invoice);

        return $pdfService->generatePdfStream($invoice);
    }

    /**
     * Debug invoice HTML (for development only).
     *
     * @param Invoice $invoice
     * @param InvoicePdfService $pdfService
     * @return string
     */
    public function debugHtml(Invoice $invoice, InvoicePdfService $pdfService): string
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'view', $invoice);

        return $pdfService->generateHtml($invoice);
    }
}
