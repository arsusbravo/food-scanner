<?php

namespace App\Http\Controllers\Waste;

use App\Http\Controllers\Controller;
use App\Models\DishDescription;
use App\Support\DishWriter;
use App\Support\FoodWasteScanException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DishController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('waste/Dishes', [
            'recent' => $user->dishDescriptions()
                ->latest()
                ->latest('id')
                ->limit(20)
                ->get(['id', 'dish_name', 'origin', 'title', 'description', 'created_at']),
            'languages' => DishWriter::LANGUAGES,
            'defaultLocale' => $this->locale($request),
            'quota' => [
                'used' => $user->dishesUsedThisMonth(),
                'limit' => $user->dishQuota(),
            ],
        ]);
    }

    public function store(Request $request, DishWriter $writer): JsonResponse
    {
        $user = $request->user();

        if (! $user->canWriteDish()) {
            return response()->json([
                'error' => "Monthly dish limit reached ({$user->dishQuota()} dishes). Upgrade to Pro for more.",
            ], 403);
        }

        $validated = $request->validate([
            'photo' => ['required', 'string', 'min:100', 'max:7000000'],
            'name' => ['nullable', 'string', 'max:120'],
            'origin' => ['nullable', 'string', 'max:120'],
            'locale' => ['nullable', Rule::in(array_keys(DishWriter::LANGUAGES))],
        ]);

        $binary = base64_decode($validated['photo'], true);

        if ($binary === false || @getimagesizefromstring($binary) === false) {
            return response()->json(['error' => 'The file is not a valid image.'], 422);
        }

        $locale = $validated['locale'] ?? $this->locale($request);

        try {
            $result = $writer->write($validated['photo'], $locale, [
                'name' => $validated['name'] ?? null,
                'origin' => $validated['origin'] ?? null,
            ]);
        } catch (FoodWasteScanException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dish = $user->dishDescriptions()->create($result + ['locale' => $locale]);

        return response()->json($dish->only(['id', 'dish_name', 'origin', 'title', 'description', 'confidence']));
    }

    public function update(Request $request, DishDescription $dish): JsonResponse
    {
        abort_unless($dish->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $dish->update($validated);

        return response()->json($dish->only(['id', 'title', 'description']));
    }

    public function destroy(Request $request, DishDescription $dish): JsonResponse
    {
        abort_unless($dish->user_id === $request->user()->id, 404);

        $dish->delete();

        return response()->json(['deleted' => true]);
    }

    private function locale(Request $request): string
    {
        $locale = (string) $request->cookie('locale', 'en');

        return array_key_exists($locale, DishWriter::LANGUAGES) ? $locale : 'en';
    }
}
