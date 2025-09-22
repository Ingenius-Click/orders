<?php

namespace Ingenius\Orders\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Ingenius\Core\Helpers\AuthHelper;
use Ingenius\Core\Http\Controllers\Controller;
use Ingenius\Orders\Actions\CreateManualInvoiceAction;
use Ingenius\Orders\Http\Requests\CreateManualInvoiceRequest;
use Ingenius\Orders\Models\Invoice;

class ManualInvoiceController extends Controller
{
    use AuthorizesRequests;

    public function store(CreateManualInvoiceAction $createManualInvoiceAction)
    {
        $user = AuthHelper::getUser();

        Config::set('orders.use_shop_cart', false);

        $request = app(CreateManualInvoiceRequest::class);

        $this->authorizeForUser($user, 'createManual', Invoice::class);

        $invoice = $createManualInvoiceAction->handle($request);

        return Response::api(data: $invoice, message: 'Invoice created successfully');
    }
}
