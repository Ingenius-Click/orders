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
     * This method is intentionally left empty as the business logic for handling
     * expired payments has not been defined yet. Depending on requirements, this could:
     * - Transition the order to a cancelled state
     * - Send notifications to admins
     * - Attempt to recover the order via customer notifications
     * - Or implement other recovery strategies
     */
    public function onPaymentExpired(?string $intendedStatus = null): void
    {
        Log::info('Payment expired for order ' . $this->id);
        Log::info('Intended status: ' . $intendedStatus);
    }
}
