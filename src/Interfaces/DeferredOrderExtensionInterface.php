<?php

namespace Ingenius\Orders\Interfaces;

use Ingenius\Orders\Models\Order;

/**
 * Implemented by order extensions that need to perform work which must NOT run
 * inside the order creation database transaction — typically calls to external
 * services.
 *
 * processOrder() still runs inside the transaction and should be limited to
 * database writes. finalizeOrder() runs once the order is committed, so its
 * side effects cannot be undone by a rollback and must be compensated instead.
 *
 * Extensions that do not implement this interface are unaffected.
 */
interface DeferredOrderExtensionInterface
{
    /**
     * Perform the extension's external work after the order has been committed.
     *
     * The returned array is merged over whatever processOrder() returned for
     * this extension, so it can enrich or overwrite that data.
     *
     * Throwing OrderFinalizationFailedException tells the caller that the order
     * could not be completed and must be compensated (cancelled, cart restored).
     * Throw it only when the external side effect provably did not happen —
     * when the outcome is unknown, return normally and let the order stand so a
     * later reconciliation can settle it.
     *
     * @param Order $order The committed order
     * @param array $validatedData The validated request data
     * @param array $context The context produced during processOrder()
     * @return array Additional data to be returned to the client
     *
     * @throws \Ingenius\Orders\Exceptions\OrderFinalizationFailedException
     */
    public function finalizeOrder(Order $order, array $validatedData, array $context): array;
}
