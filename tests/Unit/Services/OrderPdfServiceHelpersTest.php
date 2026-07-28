<?php

namespace Ingenius\Orders\Tests\Unit\Services;

use Ingenius\Orders\Services\OrderPdfService;
use PHPUnit\Framework\TestCase;

class OrderPdfServiceHelpersTest extends TestCase
{
    public function test_extract_notes_returns_null_when_metadata_is_null(): void
    {
        $this->assertNull(OrderPdfService::extractNotes(null));
    }

    public function test_extract_notes_returns_null_when_notes_key_missing(): void
    {
        $metadata = json_encode(['source' => 'website']);

        $this->assertNull(OrderPdfService::extractNotes($metadata));
    }

    public function test_extract_notes_returns_notes_when_present(): void
    {
        $metadata = json_encode(['notes' => 'Ring the bell twice']);

        $this->assertSame('Ring the bell twice', OrderPdfService::extractNotes($metadata));
    }

    public function test_is_paid_returns_false_when_payment_is_null(): void
    {
        $this->assertFalse(OrderPdfService::isPaid(null));
    }

    public function test_is_paid_returns_false_when_status_is_pending(): void
    {
        $this->assertFalse(OrderPdfService::isPaid(['status' => 'pending']));
    }

    public function test_is_paid_returns_true_when_status_is_approved(): void
    {
        $this->assertTrue(OrderPdfService::isPaid(['status' => 'approved']));
    }

    public function test_is_paid_returns_true_when_status_is_manual(): void
    {
        $this->assertTrue(OrderPdfService::isPaid(['status' => 'manual']));
    }

    public function test_extract_payment_returns_payform_data_when_present(): void
    {
        $orderArray = ['payform' => ['status' => 'approved']];

        $this->assertSame(['status' => 'approved'], OrderPdfService::extractPayment($orderArray));
    }

    public function test_extract_payment_returns_null_for_wrong_legacy_key(): void
    {
        $orderArray = ['payment' => ['status' => 'approved']];

        $this->assertNull(OrderPdfService::extractPayment($orderArray));
    }

    public function test_extract_payment_returns_null_when_missing(): void
    {
        $this->assertNull(OrderPdfService::extractPayment([]));
    }
}
