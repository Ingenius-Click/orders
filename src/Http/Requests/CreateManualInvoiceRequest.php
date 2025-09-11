<?php

namespace Ingenius\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;
use Ingenius\Orders\Services\OrderExtensionManager;

class CreateManualInvoiceRequest extends CreateOrderRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules = [
            'payment_date' => 'required|date',
            ...$rules,
        ];

        return $rules;
    }
}
