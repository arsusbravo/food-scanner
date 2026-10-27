<?php

namespace App\Http\Controllers\Waste;

use App\Http\Controllers\Controller;
use App\Models\WasteEntry;
use App\Support\ItemPriceSuggester;
use App\Support\WasteReportBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WasteEntryController extends Controller
{
    public function home(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // New users who haven't set a document language yet: send them to settings once.
        if (is_null($user->document_locale) && ! $request->session()->has('doc_lang_onboarded')) {
            $request->session()->put('doc_lang_onboarded', true);

            return redirect()->to(route('waste.settings.index').'#document-language');
        }

        $userId = $user->id;

        $todayStats = WasteEntry::where('user_id', $userId)
            ->whereDate('logged_at', today())
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(weight_kg), 0) as total_kg')
            ->first();

        $weekStats = WasteEntry::where('user_id', $userId)
            ->where('logged_at', '>=', now()->startOfWeek())
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(weight_kg), 0) as total_kg')
            ->first();

        $prices = $user->waste_prices ?? [];
        $weekCostRows = WasteEntry::where('user_id', $userId)
            ->where('logged_at', '>=', now()->startOfWeek())
            ->selectRaw('category, '.WasteReportBuilder::COST_SELECT)
            ->groupBy('category')
            ->get();

        $weekCost = $weekCostRows->sum(fn ($row) => WasteReportBuilder::categoryCost(
            (float) $row->entry_cost,
            (float) $row->unpriced_kg,
            (int) $row->priced_count,
            $prices[$row->category] ?? null,
        ) ?? 0.0);

        $hasCostData = collect($prices)->contains(fn ($price) => $price !== null && $price !== '')
            || WasteEntry::where('user_id', $userId)->whereNotNull('cost')->exists();

        $recentEntries = WasteEntry::where('user_id', $userId)
            ->orderByDesc('logged_at')
            ->limit(5)
            ->get();

        return Inertia::render('waste/Home', [
            'todayKg' => (float) $todayStats->total_kg,
            'todayCount' => (int) $todayStats->count,
            'weekKg' => (float) $weekStats->total_kg,
            'weekCount' => (int) $weekStats->count,
            'weekCost' => round($weekCost, 2),
            'hasCostData' => $hasCostData,
            'currency' => $user->currency,
            'recentEntries' => $recentEntries,
        ]);
    }

    public function index(Request $request, ItemPriceSuggester $suggester): Response
    {
        $entries = WasteEntry::where('user_id', $request->user()->id)
            ->orderBy('logged_at', 'desc')
            ->paginate(20);

        $recentEntries = WasteEntry::where('user_id', $request->user()->id)
            ->orderBy('logged_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('waste/Dashboard', [
            'itemPrices' => (object) $suggester->forUser($request->user()),
            'currency' => $request->user()->currency,
            'entries' => $entries,
            'recentEntries' => $recentEntries,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'in:protein,veg,dairy,prepared'],
            'item_name' => ['required', 'string', 'max:150'],
            'weight_kg' => ['required', 'numeric', 'min:0.0001', 'max:9999'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'reason' => ['required', 'in:spoilage,overproduction,expiry,prep_waste,other'],
            'notes' => ['nullable', 'string', 'max:500'],
            'logged_at' => ['nullable', 'date'],
            'source' => ['nullable', 'in:manual,ai_scan'],
            'photo_path' => ['nullable', 'string'],
            'ai_raw_response' => ['nullable', 'array'],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['logged_at'] ??= now();
        $validated['source'] ??= 'manual';

        WasteEntry::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Waste entry logged successfully.']);

        return to_route('waste.entries.index');
    }

    public function destroy(Request $request, WasteEntry $entry): RedirectResponse
    {
        abort_if($entry->user_id !== $request->user()->id, 403);

        $entry->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Entry deleted.']);

        return to_route('waste.entries.index');
    }
}
