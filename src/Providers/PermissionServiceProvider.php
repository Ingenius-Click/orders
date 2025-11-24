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
        // Order Status Transitions permissions
        $permissionsManager->register(
            OrderStatusPermissions::ORDER_STATUS_TRANSITIONS_CREATE,
            'Create order status transitions',
            $this->moduleName,
            'tenant',
            'Create order status transitions',
            'Order Status'
        );

        $permissionsManager->register(
            OrderStatusPermissions::ORDER_STATUS_TRANSITIONS_DELETE,
            'Delete order status transitions',
            $this->moduleName,
            'tenant',
            'Delete order status transitions',
            'Order Status'
        );

        // Order permissions
        $permissionsManager->register(
            OrderPermissions::ORDER_VIEW_ANY,
            'View any order',
            $this->moduleName,
            'tenant',
            'View any order',
            'Orders'
        );

        $permissionsManager->register(
            OrderPermissions::ORDER_DELETE,
            'Delete order',
            $this->moduleName,
            'tenant',
            'Delete order',
            'Orders'
        );

        $permissionsManager->register(
            OrderPermissions::ORDER_CHANGE_STATUS,
            'Change order status',
            $this->moduleName,
            'tenant',
            'Change order status',
            'Orders'
        );

        // Invoice permissions
        $permissionsManager->register(
            InvoicePermissions::INVOICE_VIEW,
            'View invoice',
            $this->moduleName,
            'tenant',
            'View invoice',
            'Invoices'
        );

        $permissionsManager->register(
            InvoicePermissions::INVOICE_VIEW_ANY,
            'View any invoice',
            $this->moduleName,
            'tenant',
            'View any invoice',
            'Invoices'
        );

        $permissionsManager->register(
            InvoicePermissions::INVOICE_CREATE_MANUAL,
            'Create manual invoice',
            $this->moduleName,
            'tenant',
            'Create manual invoice',
            'Invoices'
        );
    }
}
