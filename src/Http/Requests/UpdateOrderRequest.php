<?php

namespace Ingenius\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ingenius\Orders\Models\Order;
use Ingenius\Orders\Services\OrderStatusManager;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'customer_name' => 'sometimes|string|max:255',
            'customer_email' => 'sometimes|email|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'customer_address' => 'nullable|string|max:255',
            'current_base_currency' => 'sometimes|string|size:3',
            'currency' => 'sometimes|string|size:3',
            'exchange_rate' => 'nullable|numeric',
            'metadata' => 'nullable|array',
            'products' => 'sometimes|array',
            'products.*.productible_type' => 'required|string',
            'products.*.productible_id' => 'required|integer',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.base_price_per_unit_in_cents' => 'required|integer|min:0',
            'products.*.metadata' => 'nullable|array',
        ];

        // For status validation, we'll validate in the action class
        // since we need the order instance to check allowed transitions
        $rules['status'] = 'sometimes|string';

        return $rules;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
