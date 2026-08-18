<?php

namespace Ingenius\Orders\Exceptions;

use Exception;
use Throwable;

/**
 * Thrown by a deferred order extension when post-commit work failed in a way
 * that leaves no external side effect behind, so the order must be compensated
 * rather than left standing.
 *
 * Extensions must not throw this when the outcome of the external call is
 * unknown: cancelling an order whose payment did in fact start is worse than
 * leaving it pending for reconciliation.
 */
class OrderFinalizationFailedException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $extensionName = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
