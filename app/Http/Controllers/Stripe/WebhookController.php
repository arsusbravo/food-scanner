<?php

namespace App\Http\Controllers\Stripe;

use App\Models\User;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class WebhookController extends CashierWebhookController
{
    protected function handleCustomerSubscriptionCreated(array $payload): void
    {
        parent::handleCustomerSubscriptionCreated($payload);
        $this->syncPlan($payload['data']['object']['customer'], 'pro');
    }

    protected function handleCustomerSubscriptionUpdated(array $payload): void
    {
        parent::handleCustomerSubscriptionUpdated($payload);
        $status = $payload['data']['object']['status'] ?? '';
        $plan   = in_array($status, ['active', 'trialing']) ? 'pro' : 'free';
        $this->syncPlan($payload['data']['object']['customer'], $plan);
    }

    protected function handleCustomerSubscriptionDeleted(array $payload): void
    {
        parent::handleCustomerSubscriptionDeleted($payload);
        $this->syncPlan($payload['data']['object']['customer'], 'free');
    }

    private function syncPlan(string $stripeCustomerId, string $plan): void
    {
        User::where('stripe_id', $stripeCustomerId)
            ->update(['plan' => $plan, 'plan_expires_at' => null]);
    }
}
