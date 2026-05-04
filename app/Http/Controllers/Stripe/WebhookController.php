<?php

namespace App\Http\Controllers\Stripe;

use App\Models\User;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class WebhookController extends CashierWebhookController
{
    protected function handleCheckoutSessionCompleted(array $payload): void
    {
        $session = $payload['data']['object'];

        if (($session['payment_status'] ?? '') !== 'paid') {
            return;
        }

        $stripeCustomerId = $session['customer'] ?? null;
        if (! $stripeCustomerId) {
            return;
        }

        $interval  = $session['metadata']['interval'] ?? 'monthly';
        $expiresAt = $interval === 'annual' ? now()->addYear() : now()->addMonth();

        User::where('stripe_id', $stripeCustomerId)->update([
            'plan'            => 'pro',
            'plan_expires_at' => $expiresAt,
        ]);
    }
}
