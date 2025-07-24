# Orders Package

This package provides order management functionality for the Ingenius platform.

## Features

- Order creation and management
- Order status transitions
- Extensible order processing through extensions
- Multi-tenancy support

## Installation

Add the package to your project's composer.json:

```json
"require": {
    "ingenius/orders": "*"
}
```

Or install via Composer:

```bash
composer require ingenius/orders
```

## Configuration

Publish the configuration files:

```bash
php artisan vendor:publish --provider="Ingenius\Orders\Providers\OrdersServiceProvider" --tag="orders-config"
```

### Environment Variables

```
PRODUCT_MODEL=Ingenius\Products\Models\Product
```

> Note: For backward compatibility, `ORDERS_PRODUCT_MODEL` is still supported but `PRODUCT_MODEL` is preferred as it's used across all packages.

## Usage

### Creating an Order

```php
use Ingenius\Orders\Actions\CreateOrderAction;
use Ingenius\Orders\Http\Requests\CreateOrderRequest;

class YourController
{
    public function store(CreateOrderRequest $request, CreateOrderAction $action)
    {
        $order = $action->handle($request);
        
        return response()->json([
            'data' => $order,
            'message' => 'Order created successfully'
        ]);
    }
}
```

### Changing Order Status

```php
use Ingenius\Orders\Actions\ChangeOrderStatusAction;

$action = app(ChangeOrderStatusAction::class);
$order = $action->handle($orderId, 'completed');
```

### Extending Order Processing

You can extend the order processing by creating a class that implements the `OrderExtensionInterface` or extends the `BaseOrderExtension` class:

```php
use Ingenius\Orders\Extensions\BaseOrderExtension;
use Ingenius\Orders\Models\Order;

class YourOrderExtension extends BaseOrderExtension
{
    public function processOrder(Order $order, array $validatedData, array &$context): array
    {
        // Your custom processing logic
        
        return [
            'custom_data' => 'value'
        ];
    }
    
    public function calculateSubtotal(Order $order, float $currentSubtotal, array &$context): float
    {
        // Your custom subtotal calculation logic
        
        return $currentSubtotal + 500; // Add $5.00 to the subtotal
    }
    
    public function getPriority(): int
    {
        return 10; // Lower numbers run first
    }
}
```

Then register your extension in a service provider:

```php
use Ingenius\Orders\Services\OrderExtensionManager;

public function boot()
{
    $this->app->resolving(OrderExtensionManager::class, function ($manager) {
        $manager->register(new YourOrderExtension());
    });
}
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).