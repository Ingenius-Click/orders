<?php

namespace Ingenius\Orders\Actions;

use Ingenius\Core\Interfaces\IPurchasable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ingenius\Auth\Helpers\AuthHelper;
use Ingenius\Coins\Services\CurrencyServices;
use Ingenius\Core\Services\SequenceGeneratorService;
use Ingenius\Orders\Enums\OrderStatusEnum;
use Ingenius\Orders\Events\OrderCreatedEvent;
use Ingenius\Orders\Exceptions\NoProductsFoundException;
use Ingenius\Orders\Models\Order;
use Ingenius\Orders\Http\Requests\CreateOrderRequest;
use Ingenius\Orders\Services\OrderExtensionManager;

class CreateOrderAction
{
    /**
     * @var OrderExtensionManager
     */
    protected OrderExtensionManager $extensionManager;

    /**
     * CreateOrderAction constructor.
     *
     * @param OrderExtensionManager $extensionManager
     */
    public function __construct(OrderExtensionManager $extensionManager)
    {
        $this->extensionManager = $extensionManager;
    }

    public function handle(CreateOrderRequest $request, bool $isManual = false, bool $emitEvents = true): array
    {
        $validated = $request->validated();

        $validated['is_manual'] = $isManual;

        $productibleModel = Config::get('orders.productible_models.product');
        if (!class_exists($productibleModel)) {
            throw new \Exception('Productible model not found');
        }

        $baseCurrency = CurrencyServices::getBaseCurrencyShortName();
        $currentCurrency = CurrencyServices::getCurrencyShortNameFromSession();
        $currency = isset($validated['currency']) ? $validated['currency'] : ($currentCurrency ?? $baseCurrency);

        DB::beginTransaction();

        try {
            $order = $this->createOrder($validated, $currency, $baseCurrency);

            $productsData = $this->getProducts($validated);
            $products = $productsData['products'];
            $shopCart = $productsData['shopCart'];

            $itemsSubtotal = $this->processProducts($order, $products, $productibleModel);

            $order->update(['items_subtotal' => $itemsSubtotal]);
            $order->save();

            // Process the order through all extensions and collect results
            $extensionResults = $this->extensionManager->processOrder($order, $validated);

            if ($emitEvents) {
                event(new OrderCreatedEvent($order));
            }

            if ($shopCart) {
                $shopCart->clearCart();
            }

            DB::commit();

            // Return the order with extension results
            return [
                'order' => $order->fresh('products'),
                'extension_results' => $extensionResults
            ];
        } catch (\Exception $e) {
            Log::error('Error creating order: ' . $e->getMessage());
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create a new order from validated data
     *
     * @param array $validated Validated request data
     * @param string $currency Currency code
     * @param string $baseCurrency Base currency code
     * @return Order Created order
     */
    private function createOrder(array $validated, string $currency, string $baseCurrency): Order
    {
        $user = AuthHelper::getUser();
        $sequenceGenerator = app(SequenceGeneratorService::class);

        return Order::create([
            'order_number' => $sequenceGenerator->generateNumber('order'),
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'customer_address' => $validated['customer_address'] ?? null,
            'userable_type' => $user ? get_class($user) : null,
            'userable_id' => $user ? $user->id : null,
            'items_subtotal' => 0, // Will be calculated based on products
            'current_base_currency' => $baseCurrency,
            'currency' => $currency,
            'exchange_rate' => CurrencyServices::getExchangeRate($currency),
            'status' => OrderStatusEnum::NEW->value,
            'metadata' => $validated['metadata'] ?? null,
            'is_manual' => $validated['is_manual'] ?? false,
            'session_id' => session()->getId()
        ]);
    }

    /**
     * Get products from shop cart or request
     *
     * @param array $validated Validated request data
     * @return array Products array and shop cart
     */
    private function getProducts(array $validated): array
    {
        $useShopCart = Config::get('orders.use_shop_cart');

        if ($useShopCart) {
            $shopCartModel = Config::get('orders.shop_cart_model');
            $shopCart = app($shopCartModel);
            return [
                'products' => $shopCart->getCartItems()->toArray(),
                'shopCart' => $shopCart
            ];
        }

        return [
            'products' => $validated['products'],
            'shopCart' => null
        ];
    }

    /**
     * Process products and add them to the order
     *
     * @param Order $order The order to add products to
     * @param array $products Products to process
     * @param string $productibleModel Product model class
     * @return int Total price of all products
     * @throws \Exception If product is not found or not purchasable
     */
    private function processProducts(Order $order, array $products, string $productibleModel): int
    {
        if (empty($products)) {
            $order->delete();
            throw new NoProductsFoundException('No products found');
        }

        $itemsSubtotal = 0;

        foreach ($products as $product) {
            $productible = $productibleModel::find($product['productible_id']);

            if (!$productible) {
                $order->delete();
                throw new \Exception('Product not found');
            }

            if (!$productible instanceof IPurchasable) {
                $order->delete();
                throw new \Exception('Product is not purchasable');
            }

            $baseTotal = $productible->getFinalPrice() * $product['quantity'];
            $itemsSubtotal += $baseTotal;

            $order->products()->create([
                'productible_type' => $productibleModel,
                'productible_id' => $productible->getId(),
                'quantity' => $product['quantity'],
                'base_price_per_unit_in_cents' => $productible->getFinalPrice(),
                'base_total_in_cents' => $baseTotal,
                'metadata' => $product['metadata'] ?? null,
            ]);
        }

        return $itemsSubtotal;
    }
}
