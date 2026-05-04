<?php

namespace App\Http\Controllers\Waste;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $user         = $request->user();
        $subscription = $user->subscription('default');
        $active       = $subscription && $subscription->active();

        $interval       = null;
        $nextBillingDate = null;
        $onGracePeriod  = false;

        if ($active) {
            $monthlyPriceId = env('STRIPE_PRICE_MONTHLY');
            $interval       = $subscription->stripe_price === $monthlyPriceId ? 'monthly' : 'annual';
            $onGracePeriod  = $subscription->onGracePeriod();

            if ($onGracePeriod) {
                $nextBillingDate = $subscription->ends_at?->format('d M Y');
            } else {
                try {
                    $stripeSub       = $subscription->asStripeSubscription();
                    $nextBillingDate = Carbon::createFromTimestamp($stripeSub->current_period_end)->format('d M Y');
                } catch (\Throwable) {
                    $nextBillingDate = null;
                }
            }
        }

        return Inertia::render('waste/Subscription', [
            'plan'              => $user->effectivePlan(),
            'interval'          => $interval,
            'scans_used'        => $user->aiScansUsedThisMonth(),
            'scan_quota'        => $user->aiScanQuota(),
            'exports_used'      => $user->exportsUsedThisMonth(),
            'export_quota'      => $user->exportQuota(),
            'next_billing_date' => $nextBillingDate,
            'on_grace_period'   => $onGracePeriod,
            'flash'             => session('subscription_success') ? 'activated' : null,
        ]);
    }

    public function checkout(Request $request): mixed
    {
        $request->validate(['interval' => 'required|in:monthly,annual']);

        $priceId = $request->input('interval') === 'annual'
            ? env('STRIPE_PRICE_ANNUAL')
            : env('STRIPE_PRICE_MONTHLY');

        return $request->user()
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url'        => route('waste.subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'         => route('waste.subscription.index'),
                'tax_id_collection'  => ['enabled' => true],
            ]);
    }

    public function success(Request $request): RedirectResponse
    {
        session()->flash('subscription_success', true);

        return redirect()->route('waste.subscription.index');
    }

    public function portal(Request $request): RedirectResponse
    {
        return $request->user()->redirectToBillingPortal(route('waste.subscription.index'));
    }
}
