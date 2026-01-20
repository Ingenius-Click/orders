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
            __('orders::permissions.display_names.create_order_status_transitions'),
            __('orders::permissions.groups.order_status')
        );

        $permissionsManager->register(
            OrderStatusPermissions::ORDER_STATUS_TRANSITIONS_DELETE,
            'Delete order status transitions',
            $this->moduleName,
            'tenant',
            __('orders::permissions.display_names.delete_order_status_transitions'),
            __('orders::permissions.groups.order_status')
        );

        // Order permissions
        $permissionsManager->register(
            OrderPermissions::ORDER_VIEW_ANY,
            'View any order',
            $this->moduleName,
            'tenant',
            __('orders::permissions.display_names.view_any_order'),
            __('orders::permissions.groups.orders')
        );

        $permissionsManager->register(
            OrderPermissions::ORDER_DELETE,
            'Delete order',
            $this->moduleName,
            'tenant',
            __('orders::permissions.display_names.delete_order'),
            __('orders::permissions.groups.orders')
        );

        $permissionsManager->register(
            OrderPermissions::ORDER_CHANGE_STATUS,
            'Change order status',
            $this->moduleName,
            'tenant',
            __('orders::permissions.display_names.change_order_status'),
            __('orders::permissions.groups.orders')
        );

        // Invoice permissions
        $permissionsManager->register(
            InvoicePermissions::INVOICE_VIEW,
            'View invoice',
            $this->moduleName,
            'tenant',
            __('orders::permissions.display_names.view_invoice'),
            __('orders::permissions.groups.invoices')
        );

        $permissionsManager->register(
            InvoicePermissions::INVOICE_VIEW_ANY,
            'View any invoice',
            $this->moduleName,
            'tenant',
            __('orders::permissions.display_names.view_any_invoice'),
            __('orders::permissions.groups.invoices')
        );

        $permissionsManager->register(
            InvoicePermissions::INVOICE_CREATE_MANUAL,
            'Create manual invoice',
            $this->moduleName,
            'tenant',
            __('orders::permissions.display_names.create_manual_invoice'),
            __('orders::permissions.groups.invoices')
        );
    }
}
