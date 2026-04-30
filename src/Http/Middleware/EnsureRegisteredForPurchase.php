<?php

namespace Ingenius\Orders\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ingenius\Auth\Helpers\AuthHelper;
use Ingenius\Orders\Settings\CheckoutSettings;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegisteredForPurchase
{
    public function __construct(private CheckoutSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->settings->require_registration_for_purchase && !AuthHelper::check()) {
            return response()->json([
                'message' => __('Registration is required to complete the purchase'),
                'code' => 'REGISTRATION_REQUIRED',
            ], 401);
        }

        return $next($request);
    }
}
