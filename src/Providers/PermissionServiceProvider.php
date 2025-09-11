<?php

namespace Ingenius\Orders\Providers;

use Illuminate\Support\ServiceProvider;
use Ingenius\Core\Support\PermissionsManager;
use Ingenius\Orders\Constants\InvoicePermissions;
use Ingenius\Orders\Constants\OrderPermissions;
use Ingenius\Orders\Constants\OrderStatusPermissions;

class PermissionServiceProvider extends ServiceProvider
{
    /**
     * The module name.
     *
     * @var string
     */
    protected string $moduleName = 'Orders';

    /**
     * Boot the application events.
     */
    public function boot(PermissionsManager $permissionsManager): void
    {
        $this->registerPermissions($permissionsManager);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Register module-specific permission config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/permissions.php',
            'orders.permissions'
        );
    }

    /**
     * Register the module's permissions.
     */
    protected function registerPermissions(PermissionsManager $permissionsManager): void
    {
        // Register Orders module permissions
        $permissionsManager->registerMany([
            OrderStatusPermissions::ORDER_STATUS_TRANSITIONS_CREATE => 'Create order status transitions',
            OrderStatusPermissions::ORDER_STATUS_TRANSITIONS_DELETE => 'Delete order status transitions',
        ], $this->moduleName, 'tenant');

        $permissionsManager->registerMany([
            OrderPermissions::ORDER_VIEW_ANY => 'View any order',
            OrderPermissions::ORDER_DELETE => 'Delete order',
            OrderPermissions::ORDER_CHANGE_STATUS => 'Change order status',
        ], $this->moduleName, 'tenant');

        $permissionsManager->registerMany([
            InvoicePermissions::INVOICE_VIEW => 'View invoice',
            InvoicePermissions::INVOICE_VIEW_ANY => 'View any invoice',
            InvoicePermissions::INVOICE_CREATE_MANUAL => 'Create manual invoice',
        ], $this->moduleName, 'tenant');
    }
}
