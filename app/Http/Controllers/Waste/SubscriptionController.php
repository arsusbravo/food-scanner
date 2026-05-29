<?php

namespace App\Http\Controllers\Waste;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $user      = $request->user();
        $isPro     = $user->effectivePlan() === 'pro';
        $expiresAt = $isPro && $user->plan_expires_at
            ? $user->plan_expires_at->format('d M Y')
            : null;

        return Inertia::render('waste/Subscription', [
            'plan'        => $user->effectivePlan(),
            'scans_used'  => $user->aiScansUsedThisMonth(),
            'scan_quota'  => $user->aiScanQuota(),
            'exports_used'=> $user->exportsUsedThisMonth(),
            'export_quota'=> $user->exportQuota(),
            'expires_at'  => $expiresAt,
            'flash'       => session('subscription_success') ? 'activated' : null,
        ]);
    }

    public function checkout(Request $request): mixed
    {
        $request->validate(['interval' => 'required|in:monthly,annual']);

        $interval = $request->input('interval');
        $priceId  = $interval === 'annual'
            ? env('STRIPE_PRICE_ANNUAL')
            : env('STRIPE_PRICE_MONTHLY');

        $checkout = $request->user()->checkout($priceId, [
            'success_url'                => route('waste.subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'                 => route('waste.subscription.index'),
            'metadata'                   => ['interval' => $interval],
            'automatic_tax'              => ['enabled' => true],
            'tax_id_collection'          => ['enabled' => true],
            'billing_address_collection' => 'required',
            'customer_update'            => ['address' => 'auto', 'name' => 'auto'],
        ]);

        // From the in-app Inertia <Form> use Inertia::location so the SPA does
        // a clean redirect. From the public /prices Blade form we get a normal
        // browser POST — return a real HTTP redirect so the browser follows it
        // to Stripe instead of receiving the Inertia-only 409 response.
        return $request->header('X-Inertia')
            ? Inertia::location($checkout->url)
            : redirect()->away($checkout->url);
    }

    public function success(): RedirectResponse
    {
        session()->flash('subscription_success', true);

        return redirect()->route('waste.subscription.index');
    }
}
