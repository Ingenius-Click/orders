<?php

namespace Ingenius\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;
use Ingenius\Orders\Services\OrderExtensionManager;

class CreateOrderRequest extends FormRequest
{
    /**
     * @var OrderExtensionManager
     */
    protected OrderExtensionManager $extensionManager;

    /**
     * CreateOrderRequest constructor.
     *
     * @param OrderExtensionManager $extensionManager
     */
    public function __construct(OrderExtensionManager $extensionManager)
    {
        parent::__construct();
        $this->extensionManager = $extensionManager;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $useShopCart = Config::get('orders.use_shop_cart');

        $baseRules = [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'customer_address' => 'nullable|string|max:255',
            'currency' => 'nullable|string|size:3|exists:coins,code',
            'metadata' => 'nullable|array',
            ...(!$useShopCart ? [
                'products' => 'required|array',
                'products.*.productible_id' => 'required|integer',
                'products.*.quantity' => 'required|integer|min:1',
                'products.*.metadata' => 'nullable|array',
            ] : []),
        ];

        // Merge base rules with extension rules
        return array_merge($baseRules, $this->extensionManager->getValidationRules($this));
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
