# Invoice Creation Extensibility

This document explains how to extend the invoice creation functionality in the Orders package using the Strategy Pattern.

## Overview

The invoice creation system now uses a **Strategy Pattern** that allows developers to register multiple invoice creation strategies. Each strategy can define its own conditions for when and how to create invoices.

## Architecture

### Core Components

1. **`InvoiceCreationManager`** - Manages multiple strategies and determines which one to use
2. **`InvoiceCreationStrategyInterface`** - Contract that all strategies must implement
3. **`BaseInvoiceCreationStrategy`** - Abstract base class with common functionality
4. **`DefaultInvoiceCreationStrategy`** - Default implementation using existing settings

### How It Works

1. When an order transitions to a status that triggers invoice creation, the `InvoiceCreationManager` is called
2. The manager iterates through registered strategies in priority order
3. Each strategy's `shouldCreateInvoice()` method is called to determine if it should handle the invoice creation
4. The first strategy that returns `true` will create the invoice using its `createInvoice()` method

## Creating Custom Strategies

### Basic Strategy

```php
<?php

namespace App\Services\Invoices;

use Ingenius\Core\Interfaces\IOrderable;
use Ingenius\Orders\Strategies\BaseInvoiceCreationStrategy;
use Ingenius\Orders\Models\Invoice;
use Ingenius\Orders\Actions\CreateInvoiceAction;

class CustomInvoiceStrategy extends BaseInvoiceCreationStrategy
{
    protected CreateInvoiceAction $createInvoiceAction;

    public function __construct(CreateInvoiceAction $createInvoiceAction)
    {
        $this->createInvoiceAction = $createInvoiceAction;
    }

    public function shouldCreateInvoice(IOrderable $orderable, string $status): bool
    {
        // Your custom logic here
        // Return true if this strategy should handle the invoice creation
        
        // Example: Only create invoices for orders > $500
        if ($orderable->getTotalAmount() < 50000) { // $500 in cents
            return false;
        }

        // Don't create if invoice already exists
        if ($this->invoiceAlreadyExists($orderable)) {
            return false;
        }

        return $status === 'completed';
    }

    public function createInvoice(IOrderable $orderable, string $paid_at): Invoice
    {
        // Use the default invoice creation action
        $invoice = $this->createInvoiceAction->handle($orderable, $paid_at);
        
        // Add custom logic here if needed
        $invoice->update([
            'metadata' => array_merge($invoice->metadata ?? [], [
                'custom_strategy' => true,
                'processed_by' => 'CustomInvoiceStrategy'
            ])
        ]);

        return $invoice;
    }

    public function getPriority(): int
    {
        return 10; // Lower numbers run first
    }

    public function getName(): string
    {
        return 'CustomInvoice';
    }
}
```

### Advanced Strategy with Custom Logic

```php
<?php

namespace App\Services\Invoices;

use Ingenius\Core\Interfaces\IOrderable;
use Ingenius\Orders\Strategies\BaseInvoiceCreationStrategy;
use Ingenius\Orders\Models\Invoice;

class SubscriptionInvoiceStrategy extends BaseInvoiceCreationStrategy
{
    public function shouldCreateInvoice(IOrderable $orderable, string $status): bool
    {
        // Only handle subscription orders
        if (!$this->isSubscriptionOrder($orderable)) {
            return false;
        }

        // Only create on 'paid' status
        if ($status !== 'paid') {
            return false;
        }

        return !$this->invoiceAlreadyExists($orderable);
    }

    public function createInvoice(IOrderable $orderable, string $paid_at): Invoice
    {
        // Custom invoice creation logic for subscriptions
        $invoice = Invoice::create([
            'orderable_id' => $orderable->getOrderableId(),
            'orderable_type' => get_class($orderable),
            'invoice_number' => $this->generateSubscriptionInvoiceNumber($orderable),
            'customer_name' => $orderable->getCustomerName(),
            'customer_email' => $orderable->getCustomerEmail(),
            'customer_phone' => $orderable->getCustomerPhone(),
            'customer_address' => $orderable->getCustomerAddress(),
            'currency' => $orderable->getCurrency(),
            'base_currency' => $orderable->getBaseCurrency(),
            'exchange_rate' => $orderable->getExchangeRate(),
            'total_amount' => $orderable->getTotalAmount(),
            'status' => 'paid',
            'items' => $orderable->getItems(),
            'payment_date' => $paid_at,
            'metadata' => [
                'subscription_order' => true,
                'billing_cycle' => $this->getBillingCycle($orderable),
                'processed_by' => 'SubscriptionInvoiceStrategy'
            ]
        ]);

        return $invoice;
    }

    protected function isSubscriptionOrder(IOrderable $orderable): bool
    {
        $items = $orderable->getItems();
        
        foreach ($items as $item) {
            if (isset($item['product_type']) && $item['product_type'] === 'subscription') {
                return true;
            }
        }

        return false;
    }

    protected function getBillingCycle(IOrderable $orderable): string
    {
        $metadata = $orderable->metadata ?? [];
        return $metadata['billing_cycle'] ?? 'monthly';
    }

    protected function generateSubscriptionInvoiceNumber(IOrderable $orderable): string
    {
        // Custom invoice number generation for subscriptions
        return 'SUB-' . $orderable->getOrderableId() . '-' . date('Ymd');
    }

    public function getPriority(): int
    {
        return 5; // High priority - runs before default strategy
    }

    public function getName(): string
    {
        return 'SubscriptionInvoice';
    }
}
```

## Registering Strategies

### Method 1: Service Provider (Recommended)

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Ingenius\Orders\Services\InvoiceCreationManager;
use App\Services\Invoices\CustomInvoiceStrategy;
use App\Services\Invoices\SubscriptionInvoiceStrategy;

class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register custom strategies after the manager is resolved
        $this->app->afterResolving(InvoiceCreationManager::class, function ($manager) {
            $manager->register(new CustomInvoiceStrategy(
                $this->app->make(\Ingenius\Orders\Actions\CreateInvoiceAction::class)
            ));
            
            $manager->register(new SubscriptionInvoiceStrategy());
        });
    }
}
```

### Method 2: Boot Method

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Ingenius\Orders\Services\InvoiceCreationManager;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register strategies in boot method
        $this->app->make(InvoiceCreationManager::class)->register(
            new CustomInvoiceStrategy(
                $this->app->make(\Ingenius\Orders\Actions\CreateInvoiceAction::class)
            )
        );
    }
}
```

## Strategy Priority

Strategies are executed in priority order (lower numbers run first):

- **1-10**: High priority strategies (custom business logic)
- **11-50**: Medium priority strategies (conditional logic)
- **51-100**: Default strategies (fallback logic)

## Best Practices

### 1. Use the Base Class
Extend `BaseInvoiceCreationStrategy` to get common functionality like `invoiceAlreadyExists()`.

### 2. Check for Existing Invoices
Always check if an invoice already exists to prevent duplicates:
```php
if ($this->invoiceAlreadyExists($orderable)) {
    return false;
}
```

### 3. Be Specific in Conditions
Make your `shouldCreateInvoice()` method as specific as possible to avoid conflicts:
```php
// Good - specific conditions
if ($status !== 'completed' || $orderable->getTotalAmount() < 100000) {
    return false;
}

// Bad - too generic
return true;
```

### 4. Use Appropriate Priority
Set priority based on how specific your strategy is:
- Very specific conditions: Low priority (1-10)
- General conditions: High priority (80-100)

### 5. Add Metadata
Include metadata in your invoices for debugging and tracking:
```php
$invoice->update([
    'metadata' => array_merge($invoice->metadata ?? [], [
        'strategy_used' => $this->getName(),
        'custom_field' => 'value'
    ])
]);
```

## Migration from Old System

The old `InvoiceCreationInterface` is still supported for backward compatibility. If you're using the old system:

1. Your existing service will continue to work
2. The new strategy system runs alongside the old system
3. You can gradually migrate to the new system

## Testing Strategies

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Invoices\CustomInvoiceStrategy;
use Ingenius\Orders\Models\Order;

class CustomInvoiceStrategyTest extends TestCase
{
    public function test_strategy_creates_invoice_for_high_value_orders()
    {
        $strategy = new CustomInvoiceStrategy(
            app(\Ingenius\Orders\Actions\CreateInvoiceAction::class)
        );

        $order = Order::factory()->create([
            'total_amount' => 150000, // $1500
            'status' => 'completed'
        ]);

        $this->assertTrue($strategy->shouldCreateInvoice($order, 'completed'));
    }

    public function test_strategy_does_not_create_invoice_for_low_value_orders()
    {
        $strategy = new CustomInvoiceStrategy(
            app(\Ingenius\Orders\Actions\CreateInvoiceAction::class)
        );

        $order = Order::factory()->create([
            'total_amount' => 30000, // $300
            'status' => 'completed'
        ]);

        $this->assertFalse($strategy->shouldCreateInvoice($order, 'completed'));
    }
}
```

## Troubleshooting

### Strategy Not Running
1. Check if the strategy is registered in the service provider
2. Verify the priority is set correctly
3. Ensure `shouldCreateInvoice()` returns `true` for your test case

### Multiple Strategies Running
1. Check strategy priorities
2. Make conditions more specific in `shouldCreateInvoice()`
3. Use `invoiceAlreadyExists()` to prevent duplicate processing

### Performance Issues
1. Avoid expensive operations in `shouldCreateInvoice()`
2. Use database indexes for frequently checked fields
3. Consider caching strategy decisions for high-volume scenarios 