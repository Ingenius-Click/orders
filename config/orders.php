<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can specify configuration options for the orders package.
    |
    */

    'name' => 'Orders',

    'use_shop_cart' => env('ORDERS_USE_SHOP_CART', true),

    /*
    |--------------------------------------------------------------------------
    | Productible Models Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can specify which classes can be used as productible models when
    | creating order products. This allows flexibility to change the
    | product model implementations without modifying the order logic.
    |
    */
    'productible_models' => [
        'product' => env('PRODUCT_MODEL', env('ORDERS_PRODUCT_MODEL', 'Ingenius\Products\Models\Product')),
    ],
    'shop_cart_model' => env('ORDERS_SHOP_CART_MODEL', 'Ingenius\ShopCart\Services\ShopCart'),
    'new_order_status_class' => env('ORDERS_NEW_ORDER_STATUS_CLASS', 'Ingenius\Orders\Statuses\NewOrderStatus'),
    'completed_order_status_class' => env('ORDERS_COMPLETED_ORDER_STATUS_CLASS', 'Ingenius\Orders\Statuses\CompletedOrderStatus'),
    'cancelled_order_status_class' => env('ORDERS_CANCELLED_ORDER_STATUS_CLASS', 'Ingenius\Orders\Statuses\CancelledOrderStatus'),

    /*
    |--------------------------------------------------------------------------
    | Settings Classes
    |--------------------------------------------------------------------------
    |
    | Here you can register settings classes for the orders package.
    |
    */
    'settings_classes' => [
        \Ingenius\Orders\Settings\InvoiceSettings::class,
    ],
];
