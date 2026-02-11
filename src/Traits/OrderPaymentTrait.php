<?php

namespace Ingenius\Orders\Traits;

use Illuminate\Support\Facades\Log;
use Ingenius\Orders\Enums\OrderStatusEnum;
use Ingenius\Orders\Exceptions\InvalidStatusTransitionException;

trait OrderPaymentTrait
{
    /**
     * Handle successful payment.
     */
    public function onPaymentSuccess(?string $intendedStatus = null): void
    {
        try {
            $this->transitionTo($intendedStatus);

            return;
        } catch (InvalidStatusTransitionException $e) {
            Log::info('Failed to transition order to ' . $intendedStatus . ', transitioning to completed instead');
            $this->transitionTo(OrderStatusEnum::COMPLETED->value);
        }
    }

    /**
     * Handle failed payment.
     */
    public function onPaymentFailed(?string $intendedStatus = null): void
    {
        try {
            $this->transitionTo(OrderStatusEnum::CANCELLED->value);
        } catch (InvalidStatusTransitionException $e) {
            Log::info('Failed to transition order to ' . $intendedStatus . ', transitioning to cancelled instead');
            $this->transitionTo(OrderStatusEnum::CANCELLED->value);
        }
    }

    /**
     * Handle expired payment.
     *
     * Automatically cancels the order when the payment expires.
     */
    public function onPaymentExpired(?string $intendedStatus = null): void
    {
        try {
            $targetStatus = $intendedStatus ?? OrderStatusEnum::CANCELLED->value;

            $this->transitionTo($targetStatus);

            Log::info('Order cancelled due to payment expiration', [
                'order_id' => $this->id,
                'order_number' => $this->order_number,
                'previous_status' => $this->status,
                'new_status' => $targetStatus,
            ]);
        } catch (InvalidStatusTransitionException $e) {
            Log::warning('Failed to transition order on payment expiration', [
                'order_id' => $this->id,
                'order_number' => $this->order_number,
                'intended_status' => $intendedStatus,
                'error' => $e->getMessage(),
            ]);

            // Fallback to cancelled if transition fails
            try {
                $this->transitionTo(OrderStatusEnum::CANCELLED->value);
            } catch (InvalidStatusTransitionException $fallbackException) {
                Log::error('Failed to cancel order on payment expiration', [
                    'order_id' => $this->id,
                    'order_number' => $this->order_number,
                    'error' => $fallbackException->getMessage(),
                ]);
            }
        }
    }
}
