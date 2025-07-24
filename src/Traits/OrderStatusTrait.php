<?php

namespace Ingenius\Orders\Traits;

use Ingenius\Orders\Services\OrderStatusManager;

trait OrderStatusTrait
{
    /**
     * Get the status name attribute.
     */
    public function getStatusNameAttribute(): string
    {
        // Get the status object from the status manager
        $statusManager = app(OrderStatusManager::class);
        $status = $statusManager->getStatus($this->status);

        if ($status) {
            return $status->getName();
        }

        return ucfirst($this->status);
    }

    /**
     * Get the allowed next statuses attribute.
     */
    public function getAllowedNextStatusesAttribute(): array
    {
        $statusManager = app(OrderStatusManager::class);
        $allowedTransitions = $statusManager->getAllowedTransitions($this->status, $this);

        $result = [];
        foreach ($allowedTransitions as $statusIdentifier) {
            $status = $statusManager->getStatus($statusIdentifier);
            if ($status) {
                $result[] = [
                    'identifier' => $status->getIdentifier(),
                    'name' => $status->getName(),
                ];
            }
        }

        return $result;
    }

    /**
     * Transition the order to a new status.
     */
    public function transitionTo(string $newStatus): self
    {
        app(OrderStatusManager::class)->transition($this, $newStatus);
        return $this;
    }
}
