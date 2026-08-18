<?php

namespace Ingenius\Orders\Services;

use Illuminate\Http\Request;
use Ingenius\Orders\Interfaces\DeferredOrderExtensionInterface;
use Ingenius\Orders\Interfaces\OrderExtensionInterface;
use Ingenius\Orders\Models\Order;

class OrderExtensionManager
{
    /**
     * Collection of registered extensions
     *
     * @var array<OrderExtensionInterface>
     */
    protected array $extensions = [];

    /**
     * Register a new order extension
     *
     * @param OrderExtensionInterface $extension
     * @return void
     */
    public function register(OrderExtensionInterface $extension): void
    {
        $this->extensions[] = $extension;
        // Sort extensions by priority when a new one is added
        $this->sortExtensions();
    }

    /**
     * Get all registered extensions, sorted by priority
     *
     * @return array<OrderExtensionInterface>
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * Get validation rules from all registered extensions
     *
     * @return array<string, mixed>
     */
    public function getValidationRules(Request $request): array
    {
        $rules = [];

        foreach ($this->extensions as $extension) {
            $rules = array_merge($rules, $extension->getValidationRules($request));
        }

        return $rules;
    }

    /**
     * Process order through all extensions
     *
     * Extensions can modify the context to contribute to the final total.
     * The context['total'] starts as items_subtotal and extensions add/subtract from it.
     *
     * @param Order $order
     * @param array $validatedData
     * @param array $initialContext Optional initial context data (e.g., pre-calculated discounts)
     * @return array Contains 'results' from extensions and 'context' with final totals
     */
    public function processOrder(Order $order, array $validatedData, array $initialContext = []): array
    {
        $results = [];
        $context = array_merge([
            'subtotal' => $order->getItemsSubtotal(),
            'total' => $order->getItemsSubtotal(), // Start with items_subtotal, extensions modify this
            'metadata' => []
        ], $initialContext);

        foreach ($this->extensions as $extension) {
            $extensionResult = $extension->processOrder($order, $validatedData, $context);

            if (!empty($extensionResult)) {
                $results[$extension->getName()] = $extensionResult;
            }
        }

        return [
            'results' => $results,
            'context' => $context,
        ];
    }

    /**
     * Run the post-commit step of every extension that declares one.
     *
     * Called after the order transaction has been committed, so extensions can
     * safely perform external work here without holding the transaction open.
     *
     * @param Order $order The committed order
     * @param array $validatedData The validated request data
     * @param array $context The context produced by processOrder()
     * @return array Results keyed by extension name, to be merged over the processOrder() results
     *
     * @throws \Ingenius\Orders\Exceptions\OrderFinalizationFailedException If an extension requires the order to be compensated
     */
    public function finalizeOrder(Order $order, array $validatedData, array $context): array
    {
        $results = [];

        foreach ($this->extensions as $extension) {
            if (!$extension instanceof DeferredOrderExtensionInterface) {
                continue;
            }

            $extensionResult = $extension->finalizeOrder($order, $validatedData, $context);

            if (!empty($extensionResult)) {
                $results[$extension->getName()] = $extensionResult;
            }
        }

        return $results;
    }

    /**
     * Calculate the final subtotal by running through all extensions
     *
     * @param Order $order
     * @param float $initialSubtotal
     * @return float
     */
    public function calculateFinalSubtotal(Order $order, float $initialSubtotal): float
    {
        $subtotal = $initialSubtotal;
        $context = ['metadata' => []];

        foreach ($this->extensions as $extension) {
            $subtotal = $extension->calculateSubtotal($order, $subtotal, $context);
        }

        return $subtotal;
    }

    /**
     * Extend the order array with data from all extensions
     *
     * @param Order $order
     * @param array $orderArray
     * @return array
     */
    public function extendOrderArray(Order $order, array $orderArray): array
    {
        $result = $orderArray;

        foreach ($this->extensions as $extension) {
            $result = $extension->extendOrderArray($order, $result);
        }

        return $result;
    }

    /**
     * Sort extensions by priority
     *
     * @return void
     */
    protected function sortExtensions(): void
    {
        usort($this->extensions, function (OrderExtensionInterface $a, OrderExtensionInterface $b) {
            return $a->getPriority() <=> $b->getPriority();
        });
    }
}
