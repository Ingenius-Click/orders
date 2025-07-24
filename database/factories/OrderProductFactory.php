<?php

namespace Ingenius\Orders\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Config;
use Ingenius\Orders\Models\Order;

class OrderProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Ingenius\Orders\Models\OrderProduct::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Get product model from config
        $productModel = Config::get('orders.productible_models.product', 'Ingenius\Products\Models\Product');

        // Create a product or get a random ID
        $productId = $this->faker->numberBetween(1, 100);
        $productInstance = null;

        // Check if the product model class exists and has a factory
        if (class_exists($productModel)) {
            // Check if the model has a factory method
            if (method_exists($productModel, 'factory')) {
                // Create two products using the factory
                static $products = null;

                if ($products === null) {
                    $products = [
                        $productModel::factory()->create(),
                        $productModel::factory()->create()
                    ];
                }

                // Use one of the created products
                $productInstance = $this->faker->randomElement($products);
                $productId = $productInstance->id;
            }
        }

        return [
            'order_id' => Order::factory(),
            'productible_id' => $productId,
            'productible_type' => $productModel,
            'quantity' => $this->faker->numberBetween(1, 10),
            'base_price_per_unit_in_cents' => $productInstance && method_exists($productInstance, 'getFinalPrice')
                ? $productInstance->getFinalPrice()
                : $this->faker->numberBetween(500, 100000),
            'base_total_in_cents' => function (array $attributes) {
                return $attributes['base_price_per_unit_in_cents'] * $attributes['quantity'];
            },
            'metadata' => json_encode([
                'options' => $this->faker->boolean ? ['color' => $this->faker->colorName(), 'size' => $this->faker->randomElement(['S', 'M', 'L', 'XL'])] : null,
                'notes' => $this->faker->boolean ? $this->faker->sentence() : null,
            ]),
        ];
    }

    /**
     * Configure the factory to use a specific product instance.
     *
     * @param mixed $product An instance of a product model
     * @return $this
     */
    public function forProduct($product): self
    {
        return $this->state([
            'productible_id' => $product->id,
            'productible_type' => get_class($product),
            'base_price_per_unit_in_cents' => method_exists($product, 'getFinalPrice')
                ? $product->getFinalPrice()
                : $this->faker->numberBetween(500, 100000),
        ]);
    }
}
