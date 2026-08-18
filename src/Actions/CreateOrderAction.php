<?php

namespace Ingenius\Orders\Actions;

use Ingenius\Core\Interfaces\IInventoriable;
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
use Ingenius\Orders\Exceptions\OrderFinalizationFailedException;
use Ingenius\Orders\Models\Order;
use Ingenius\Orders\Http\Requests\CreateOrderRequest;
use Ingenius\Orders\Services\OrderExtensionManager;
use Ingenius\Core\Interfaces\StockAvailabilityInterface;
use Ingenius\ShopCart\Exceptions\InsufficientStockException;

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

        // Get currency for this order - uses helper function that checks middleware, session, then base
        $currency = $validated['currency'] ?? get_current_currency();
        $baseCurrency = CurrencyServices::getBaseCurrencyShortName();

        // Phase 1: everything that belongs in a database transaction. No calls
        // to external services happen here — an unanswered HTTP request would
        // hold this transaction, and its row locks, open indefinitely.
        DB::beginTransaction();

        try {
            $order = $this->createOrder($validated, $currency, $baseCurrency);

            $productsData = $this->getProducts($validated);
            $products = $productsData['products'];
            $shopCart = $productsData['shopCart'];

            $productPriceOverrides = $isManual ? ($validated['product_price_overrides'] ?? []) : [];
            $itemsSubtotal = $this->processProducts($order, $products, $productibleModel, $productPriceOverrides);

            $order->update(['items_subtotal' => $itemsSubtotal]);
            $order->save();

            // Build initial context with pre-calculated discounts from cart
            // Note: is_manual_invoice is set internally (not from request) for security
            $initialContext = [
                'discounts' => $productsData['discounts'] ?? [
                    'product_discounts' => [],
                    'cart_discounts' => [],
                ],
                'is_manual_invoice' => $isManual,
                'shipping_price_override' => $isManual ? ($validated['shipping_price_override'] ?? null) : null,
            ];

            // Process the order through all extensions and collect results
            $extensionResults = $this->extensionManager->processOrder($order, $validated, $initialContext);

            // Save the final total_amount calculated by extensions
            $finalTotal = $extensionResults['context']['total'] ?? $itemsSubtotal;
            $order->update(['total_amount' => $finalTotal]);

            // Captured before clearing so the cart can be put back if phase 2 fails.
            $restorableCartItems = $shopCart ? $this->describeOrderItems($order) : [];

            if ($shopCart) {
                $shopCart->clearCart();
            }

            DB::commit();
        } catch (\Exception $e) {
            Log::error('Error creating order: ' . $e->getMessage());
            DB::rollBack();
            throw $e;
        }

        // Phase 2: external work, with the order already durable. Failures here
        // cannot be rolled back, so they are compensated instead.
        try {
            $finalizeResults = $this->extensionManager->finalizeOrder($order, $validated, $extensionResults['context']);
        } catch (OrderFinalizationFailedException $e) {
            Log::error('Order finalization failed, compensating order: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'extension' => $e->extensionName,
            ]);

            $this->compensateOrder($order, $shopCart, $restorableCartItems);

            throw $e;
        }

        foreach ($finalizeResults as $extensionName => $result) {
            $extensionResults['results'][$extensionName] = array_merge(
                $extensionResults['results'][$extensionName] ?? [],
                $result,
            );
        }

        // Emitted only once the order is known to stand: listeners notify the
        // customer, and a compensated order must not generate that notification.
        if ($emitEvents) {
            event(new OrderCreatedEvent($order));
        }

        // Return the order with extension results
        return [
            'order' => $order->fresh('products'),
            'extension_results' => $extensionResults
        ];
    }

    /**
     * Undo a committed order whose finalization failed.
     *
     * Cancelling releases the stock the order was holding, since reservations
     * are counted from orders in the NEW status.
     *
     * @param Order $order The order to cancel
     * @param mixed $shopCart The cart the order was built from, if any
     * @param array $restorableCartItems Items to put back into that cart
     * @return void
     */
    private function compensateOrder(Order $order, $shopCart, array $restorableCartItems): void
    {
        try {
            $order->transitionTo(OrderStatusEnum::CANCELLED->value);
        } catch (\Exception $e) {
            Log::error('Failed to cancel order during compensation', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }

        // The cart model is resolved from config and may not support restoring;
        // losing the cart is worse UX than a failed checkout but not fatal.
        if ($shopCart && $restorableCartItems && method_exists($shopCart, 'restoreItems')) {
            try {
                $shopCart->restoreItems($restorableCartItems);
            } catch (\Exception $e) {
                Log::error('Failed to restore cart during compensation', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Describe an order's products in the shape the cart needs to restore them.
     *
     * @param Order $order
     * @return array
     */
    private function describeOrderItems(Order $order): array
    {
        return $order->products()->get()->map(function ($orderProduct) {
            return [
                'productible_type' => $orderProduct->productible_type,
                'productible_id' => $orderProduct->productible_id,
                'quantity' => $orderProduct->quantity,
            ];
        })->all();
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
            'userable_id' => $user?->id,
            'items_subtotal' => 0, // Will be calculated based on products
            'current_base_currency' => $baseCurrency,
            'currency' => $currency,
            'exchange_rate' => CurrencyServices::getExchangeRate($currency),
            'status' => OrderStatusEnum::NEW->value,
            'metadata' => $validated['metadata'] ?? null,
            'is_manual' => $validated['is_manual'] ?? false,
            'guest_token' => request()->header('X-Guest-Token')
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

            // Collect discount information if the cart supports it
            $discounts = method_exists($shopCart, 'getDiscountsForOrder')
                ? $shopCart->getDiscountsForOrder()
                : ['product_discounts' => [], 'cart_discounts' => []];

            return [
                'products' => $shopCart->getCartItems()->toArray(),
                'shopCart' => $shopCart,
                'discounts' => $discounts,
            ];
        }

        return [
            'products' => $validated['products'],
            'shopCart' => null,
            'discounts' => ['product_discounts' => [], 'cart_discounts' => []],
        ];
    }

    /**
     * Process products and add them to the order
     *
     * @param Order $order The order to add products to
     * @param array $products Products to process
     * @param string $productibleModel Product model class
     * @param array $priceOverrides Optional price overrides keyed by productible_id (for manual invoices)
     * @return int Total price of all products
     * @throws \Exception If product is not found or not purchasable
     */
    private function processProducts(Order $order, array $products, string $productibleModel, array $priceOverrides = []): int
    {
        if (empty($products)) {
            $order->delete();
            throw new NoProductsFoundException('No products found');
        }

        $itemsSubtotal = 0;
        $stockService = app()->bound(StockAvailabilityInterface::class)
            ? app(StockAvailabilityInterface::class)
            : null;
        $affectedProducts = [];

        // Exclude the current user's own cart items from the stock reservation check
        // so their existing cart reservation does not block their own order creation.
        $cartExclusionContext = [];
        $user = AuthHelper::getUser();
        $guestToken = request()->header('X-Guest-Token');
        if ($user) {
            $cartExclusionContext['exclude_cart_owner_id'] = $user->id;
            $cartExclusionContext['exclude_cart_owner_type'] = get_class($user);
        } elseif ($guestToken) {
            $cartExclusionContext['exclude_cart_guest_token'] = $guestToken;
        }

        foreach ($products as $product) {
            // Use productible_type from cart item if available, otherwise fall back to configured model
            $resolveModel = $product['productible_type'] ?? $productibleModel;
            $productible = class_exists($resolveModel)
                ? $resolveModel::find($product['productible_id'])
                : null;

            if (!$productible) {
                $order->delete();
                throw new \Exception('Product not found');
            }

            if (!$productible instanceof IPurchasable) {
                $order->delete();
                throw new \Exception('Product is not purchasable');
            }

            // Validate stock availability before creating order product
            if ($stockService && $productible instanceof IInventoriable && $productible->handleStock()) {
                if (!$stockService->hasAvailableStock($productible, $product['quantity'], $cartExclusionContext)) {
                    $order->delete();
                    throw new InsufficientStockException(
                        $productible->getId(),
                        $product['quantity'],
                        $stockService->getAvailableStock($productible, $cartExclusionContext)
                    );
                }

                $affectedProducts[] = $productible;
            }

            // Use price override if provided (for manual invoices), otherwise use product's final price
            $pricePerUnit = $priceOverrides[$product['productible_id']] ?? $productible->getFinalPrice();
            $baseTotal = $pricePerUnit * $product['quantity'];
            $itemsSubtotal += $baseTotal;

            $order->products()->create([
                'productible_type' => get_class($productible),
                'productible_id' => $productible->getId(),
                'quantity' => $product['quantity'],
                'base_price_per_unit_in_cents' => $pricePerUnit,
                'base_total_in_cents' => $baseTotal,
                'metadata' => $product['metadata'] ?? null,
            ]);
        }

        // Invalidate stock cache for all affected products
        if ($stockService) {
            foreach ($affectedProducts as $productible) {
                $stockService->invalidateCache(get_class($productible), $productible->getId());
            }
        }

        return $itemsSubtotal;
    }
}
