<?php

namespace App\Http\Controllers\Waste;

use App\Http\Controllers\Controller;
use App\Support\FoodWasteScanException;
use App\Support\FoodWasteScanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AIScanController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        return Inertia::render('waste/AiScan', [
            'quota' => [
                'ai_scans_used'  => $user->aiScansUsedThisMonth(),
                'ai_scan_quota'  => $user->aiScanQuota(),
            ],
        ]);
    }

    public function store(Request $request, FoodWasteScanner $scanner): JsonResponse
    {
        $user = $request->user();

        if (! $user->canAiScan()) {
            $quota = $user->aiScanQuota();
            return response()->json([
                'error' => "Monthly AI scan limit reached ({$quota} scans). Upgrade to Pro for unlimited scans.",
            ], 403);
        }

        $validated = $request->validate([
            'photo' => ['required', 'string', 'min:100'],
        ]);

        $locale = $user->resolveDocumentLocale($request->cookie('locale', 'en'));

        try {
            $result = $scanner->scan($validated['photo'], $locale);
        } catch (FoodWasteScanException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }
}
