<?php

namespace Ingenius\Orders\Tests\Feature\Pdf;

use Ingenius\Orders\Tests\TestCase;
use Illuminate\Support\Facades\View;

class OrderPdfTemplateTest extends TestCase
{
    protected function fakeOrder(): object
    {
        return new class {
            public string $order_number = 'ORD-TEST01';
            public string $status = 'processing';
            public string $status_name = 'Processing';
            public string $total_amount_formatted = '$25.00';
            public string $base_total_amount_formatted = '$25.00';
            public string $currency = 'USD';
            public string $current_base_currency = 'USD';
            public float $exchange_rate = 1.0;
            public string $created_at = '2026-07-20 10:00:00';

            public function getCustomerName(): string
            {
                return 'Fallback Name';
            }

            public function getCustomerEmail(): string
            {
                return 'fallback@example.com';
            }

            public function getCustomerPhone(): ?string
            {
                return '000-0000';
            }

            public function getCustomerAddress(): ?string
            {
                return 'Fallback Address';
            }
        };
    }

    protected function baseViewData(array $overrides = []): array
    {
        return array_merge([
            'order' => $this->fakeOrder(),
            'items' => [],
            'shipment' => null,
            'payment' => null,
            'notes' => null,
            'is_paid' => false,
            'generated_at' => '2026-07-20 12:00:00',
        ], $overrides);
    }

    public function test_renders_collect_on_delivery_badge_when_shipment_is_external_and_unpaid(): void
    {
        $html = View::make('orders::pdf.order', $this->baseViewData([
            'shipment' => [
                'is_external' => true,
                'price_formatted' => '$5.00',
                'external_payment_instructions' => 'Exact cash only',
                'beneficiary_name' => 'Juan Perez',
                'beneficiary_phone' => '555-1234',
                'beneficiary_address' => 'Calle 1',
                'beneficiary_city' => 'Havana',
                'pickup_address' => null,
            ],
        ]))->render();

        $this->assertStringContainsString('Collect on Delivery', $html);
        $this->assertStringContainsString('$5.00', $html);
        $this->assertStringContainsString('Exact cash only', $html);
        $this->assertStringContainsString('Juan Perez', $html);
        $this->assertStringContainsString('555-1234', $html);
        $this->assertStringContainsString('Deliver To', $html);
        $this->assertStringContainsString('Shipping not included, collect separately upon delivery', $html);
        $this->assertStringContainsString('is-cod', $html);
    }

    public function test_renders_paid_badge_when_is_paid_is_true(): void
    {
        $html = View::make('orders::pdf.order', $this->baseViewData([
            'is_paid' => true,
        ]))->render();

        $this->assertStringContainsString('Paid', $html);
        $this->assertStringContainsString('is-paid', $html);
        $this->assertStringNotContainsString('Collect on Delivery', $html);
    }

    public function test_renders_notes_box_when_notes_present(): void
    {
        $html = View::make('orders::pdf.order', $this->baseViewData([
            'notes' => 'Ring the bell twice',
        ]))->render();

        $this->assertStringContainsString('Ring the bell twice', $html);
    }

    public function test_renders_pickup_title_and_address_when_pickup_address_present(): void
    {
        $html = View::make('orders::pdf.order', $this->baseViewData([
            'shipment' => [
                'is_external' => false,
                'pickup_address' => 'Bodega Central',
                'beneficiary_name' => 'Ana Lopez',
                'beneficiary_phone' => '555-9999',
            ],
        ]))->render();

        $this->assertStringContainsString('Pickup At', $html);
        $this->assertStringContainsString('Bodega Central', $html);
        $this->assertStringNotContainsString('Deliver To', $html);
    }

    public function test_falls_back_to_order_name_and_phone_when_shipment_has_empty_strings(): void
    {
        $html = View::make('orders::pdf.order', $this->baseViewData([
            'shipment' => [
                'is_external' => false,
                'beneficiary_name' => '',  // empty string, not null
                'beneficiary_phone' => '', // empty string, not null
                'beneficiary_address' => 'Shipment Address',
                'beneficiary_city' => 'City',
            ],
        ]))->render();

        // Should fall back to order's getCustomerName() and getCustomerPhone()
        $this->assertStringContainsString('Fallback Name', $html);
        $this->assertStringContainsString('000-0000', $html);
        // Should not show "-" for name or phone
        $this->assertStringNotContainsString('<div class="delivery-recipient">-</div>', $html);
        $this->assertStringNotContainsString('<div class="delivery-phone">-</div>', $html);
    }

    public function test_falls_back_to_order_address_when_shipment_array_present_but_all_fields_null(): void
    {
        $html = View::make('orders::pdf.order', $this->baseViewData([
            'shipment' => [
                'is_external' => false,
                'beneficiary_name' => null,
                'beneficiary_phone' => null,
                'beneficiary_address' => null,
                'beneficiary_city' => null,
                'beneficiary_state' => null,
                'beneficiary_zip' => null,
                'beneficiary_country' => null,
                'pickup_address' => null,
            ],
        ]))->render();

        $this->assertStringContainsString('Fallback Address', $html);
        $this->assertStringNotContainsString('<div class="delivery-address"></div>', $html);
    }
}
