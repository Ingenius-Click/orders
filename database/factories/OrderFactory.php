<?php

namespace Ingenius\Orders\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Ingenius\Orders\Models\Order::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $currencies = ['USD', 'EUR', 'GBP', 'JPY'];
        $baseCurrency = 'USD';
        $currency = $this->faker->randomElement($currencies);

        // Set exchange rate to 1 if the currency is the same as the base currency
        $exchangeRate = ($currency === $baseCurrency) ? 1 : $this->faker->randomFloat(2, 0.5, 2);

        return [
            'order_number' => 'ORD-' . Str::upper(Str::random(8)),
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'customer_phone' => $this->faker->phoneNumber(),
            'customer_address' => $this->faker->address(),
            'items_subtotal' => 0, // Will be calculated after products are added
            'current_base_currency' => $baseCurrency,
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'cancelled']),
            'metadata' => json_encode([
                'notes' => $this->faker->boolean ? $this->faker->sentence() : null,
                'source' => $this->faker->randomElement(['website', 'mobile_app', 'in_store']),
                'payment_method' => $this->faker->randomElement(['credit_card', 'paypal', 'bank_transfer']),
            ]),
        ];
    }

    /**
     * Configure the model factory to create an order with products.
     *
     * @param int $count The number of products to create
     * @return $this
     */
    public function withProducts(int $count = 2): self
    {
        return $this->afterCreating(function ($order) use ($count) {
            // Create order products
            $products = \Ingenius\Orders\Models\OrderProduct::factory()
                ->count($count)
                ->create([
                    'order_id' => $order->id,
                ]);

            // Calculate items subtotal
            $itemsSubtotal = 0;
            foreach ($products as $product) {
                $itemsSubtotal += $product->base_total_in_cents;
            }

            // Update the order with the calculated subtotal
            $order->update([
                'items_subtotal' => $itemsSubtotal,
            ]);
        });
    }

    /**
     * Configure the model factory to create a pending order.
     *
     * @return $this
     */
    public function pending(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
            ];
        });
    }

    /**
     * Configure the model factory to create a processing order.
     *
     * @return $this
     */
    public function processing(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'processing',
            ];
        });
    }

    /**
     * Configure the model factory to create a completed order.
     *
     * @return $this
     */
    public function completed(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
            ];
        });
    }

    /**
     * Configure the model factory to create a cancelled order.
     *
     * @return $this
     */
    public function cancelled(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
            ];
        });
    }
}
