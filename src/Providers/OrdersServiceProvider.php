<?php

namespace Ingenius\Orders\Providers;

use Illuminate\Support\ServiceProvider;
use Ingenius\Core\Traits\RegistersConfigurations;
use Ingenius\Core\Traits\RegistersMigrations;
use Ingenius\Orders\Features\ListInvoicesFeature;
use Ingenius\Orders\Features\ViewInvoiceFeature;
use Ingenius\Orders\Features\ExportInvoiceFeature;
use Ingenius\Orders\Models\Order;
use Ingenius\Orders\Policies\OrderPolicy;
use Ingenius\Orders\Services\OrderStatusManager;
use Ingenius\Orders\Services\OrderExtensionManager;
use Ingenius\Orders\Services\InvoiceDataManager;
use Ingenius\Orders\Statuses\NewOrderStatus;
use Ingenius\Orders\Statuses\CompletedOrderStatus;
use Ingenius\Orders\Statuses\CancelledOrderStatus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Ingenius\Core\Services\FeatureManager;
use Ingenius\Orders\Features\CreateOrderFeature;
use Ingenius\Orders\Features\ListOrdersFeature;
use Ingenius\Orders\Features\ManualInvoiceFeature;
use Ingenius\Orders\Features\UpdateOrderFeature;
use Ingenius\Orders\Features\ViewOrderFeature;
use Ingenius\Orders\Interfaces\InvoiceCreationInterface;
use Ingenius\Orders\Services\InvoiceCreationManager;
use Ingenius\Orders\Strategies\DefaultInvoiceCreationStrategy;
use Ingenius\Orders\InvoiceData\OrderInvoiceDataProvider;
use Ingenius\Orders\Services\InvoicePdfService;

class OrdersServiceProvider extends ServiceProvider
{
    use RegistersMigrations, RegistersConfigurations;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/orders.php', 'orders');
        $this->mergeConfigFrom(__DIR__ . '/../../config/permissions.php', 'orders.permissions');

        // Register configuration with the registry
        $this->registerConfig(__DIR__ . '/../../config/orders.php', 'orders', 'orders');
        $this->registerConfig(__DIR__ . '/../../config/permissions.php', 'orders.permissions', 'orders');

        // Register the route service provider
        $this->app->register(RouteServiceProvider::class);

        // Register the permission service provider
        $this->app->register(PermissionServiceProvider::class);

        // Register the order status manager as a singleton
        $this->app->singleton(OrderStatusManager::class, function ($app) {
            $manager = new OrderStatusManager();

            // Register default statuses
            $manager->register(new NewOrderStatus());
            $manager->register(new CompletedOrderStatus());
            $manager->register(new CancelledOrderStatus());

            return $manager;
        });

        // Register the order extension manager as a singleton
        $this->app->singleton(OrderExtensionManager::class, function ($app) {
            return new OrderExtensionManager();
        });

        // Register the invoice creation manager as a singleton
        $this->app->singleton(InvoiceCreationManager::class, function ($app) {
            $manager = new InvoiceCreationManager();

            // Register default strategy
            $manager->register(new DefaultInvoiceCreationStrategy(
                $app->make(\Ingenius\Orders\Settings\InvoiceSettings::class),
                $app->make(\Ingenius\Orders\Actions\CreateInvoiceAction::class)
            ));

            return $manager;
        });

        // Register the invoice data manager as a singleton
        $this->app->singleton(InvoiceDataManager::class, function ($app) {
            return new InvoiceDataManager();
        });

        // Register the order invoice data provider
        $this->app->afterResolving(InvoiceDataManager::class, function (InvoiceDataManager $manager) {
            $manager->register(new OrderInvoiceDataProvider());
        });

        // Register the invoice PDF service as a singleton
        $this->app->singleton(InvoicePdfService::class, function ($app) {
            return new InvoicePdfService();
        });

        // Register settings classes with the core settings system
        $this->registerSettingsClasses();

        $this->app->afterResolving(FeatureManager::class, function (FeatureManager $manager) {
            $manager->register(new ManualInvoiceFeature());
            $manager->register(new ListInvoicesFeature());
            $manager->register(new ViewInvoiceFeature());
            $manager->register(new ExportInvoiceFeature());
            $manager->register(new ListOrdersFeature());
            $manager->register(new ViewOrderFeature());
            $manager->register(new CreateOrderFeature());
            $manager->register(new UpdateOrderFeature());
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register translations
        $this->registerTranslations();
        // Register migrations with the registry
        $this->registerMigrations(__DIR__ . '/../../database/migrations', 'orders');

        // Check if there's a tenant migrations directory and register it
        $tenantMigrationsPath = __DIR__ . '/../../database/migrations/tenant';
        if (is_dir($tenantMigrationsPath)) {
            $this->registerTenantMigrations($tenantMigrationsPath, 'orders');
        }

        // Register policies
        $this->registerPolicies();

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'orders');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/orders.php' => config_path('orders.php'),
            __DIR__ . '/../../config/permissions.php' => config_path('orders/permissions.php'),
        ], 'orders-config');

        // Publish views
        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/orders'),
        ], 'orders-views');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'orders-migrations');
    }

    /**
     * Register the package policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);
    }

    /**
     * Register settings classes with the core settings system.
     */
    protected function registerSettingsClasses(): void
    {
        // Get existing settings classes from core config
        $coreSettingsClasses = Config::get('settings.settings_classes', []);

        // Get orders settings classes
        $ordersSettingsClasses = Config::get('orders.settings_classes', []);

        // Merge and update the core settings classes
        $mergedSettingsClasses = array_merge($coreSettingsClasses, $ordersSettingsClasses);

        // Update the core settings config
        Config::set('settings.settings_classes', $mergedSettingsClasses);
    }

    /**
     * Register translations.
     */
    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'orders');
        $this->loadJsonTranslationsFrom(__DIR__ . '/../../resources/lang');
    }
}
