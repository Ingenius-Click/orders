<?php

namespace Ingenius\Orders\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Ingenius\Auth\Helpers\AuthHelper;
use Ingenius\Core\Http\Controllers\Controller;
use Ingenius\Orders\Models\Invoice;

class InvoicesController extends Controller
{
    use AuthorizesRequests;

    public function show(Invoice $invoice): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'view', $invoice);

        return response()->api(data: $invoice, message: 'Invoice retrieved successfully');
    }
}
