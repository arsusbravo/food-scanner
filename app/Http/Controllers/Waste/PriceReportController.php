<?php

namespace App\Http\Controllers\Waste;

use App\Http\Controllers\Controller;
use App\Models\ProductLink;
use App\Support\PriceWatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PriceReportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $watch = new PriceWatch($user);
        $comparisons = $watch->comparisons();

        return Inertia::render('waste/ReportPrices', [
            'currency' => $user->currency,
            'changes' => $watch->changes(),
            'comparisons' => $comparisons,
            'potentialSaving' => round(array_sum(array_column($comparisons, 'monthly_saving')), 2),
            'suggestions' => $watch->suggestions(),
            'links' => $watch->links(),
            'trackedLines' => $watch->trackedLines(),
            'threshold' => (int) (PriceWatch::CHANGE_THRESHOLD * 100),
        ]);
    }

    public function link(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_key' => ['required', 'string', 'max:191'],
            'to_key' => ['required', 'string', 'max:191'],
        ]);

        $linked = (new PriceWatch($request->user()))->link($validated['from_key'], $validated['to_key']);

        abort_unless($linked, 422, 'These products cannot be linked.');

        return back();
    }

    public function unlink(Request $request, ProductLink $link): RedirectResponse
    {
        abort_unless($link->user_id === $request->user()->id, 404);

        $link->delete();

        return back();
    }
}
