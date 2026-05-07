<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WasteEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function dashboard(): Response
    {
        $stats = [
            'total_users'       => User::where('is_admin', false)->count(),
            'total_entries'     => WasteEntry::count(),
            'ai_scan_count'     => WasteEntry::where('source', 'ai_scan')->count(),
            'this_week_entries' => WasteEntry::where('logged_at', '>=', now()->startOfWeek())->count(),
            'this_week_kg'      => round((float) WasteEntry::where('logged_at', '>=', now()->startOfWeek())->sum('weight_kg'), 2),
            'active_users'      => User::whereHas('wasteEntries', fn($q) => $q->where('logged_at', '>=', now()->subDays(7)))->count(),
        ];

        $recentEntries = WasteEntry::with('user:id,name,email')
            ->orderByDesc('logged_at')
            ->limit(10)
            ->get(['id', 'user_id', 'item_name', 'category', 'weight_kg', 'reason', 'source', 'logged_at']);

        $openrouter = null;
        try {
            $response = Http::withToken(config('services.openrouter.key'))
                ->timeout(5)
                ->get('https://openrouter.ai/api/v1/auth/key');
            if ($response->successful()) {
                $openrouter = $response->json('data');
            }
        } catch (\Throwable $e) {
            Log::error('OpenRouter key check failed', ['error' => $e->getMessage()]);
        }

        return Inertia::render('Dashboard', [
            'stats'         => $stats,
            'recentEntries' => $recentEntries,
            'openrouter'    => $openrouter,
        ]);
    }

    public function users(): Response
    {
        $users = User::where('is_admin', false)
            ->withCount([
                'wasteEntries',
                'wasteEntries as ai_scan_count' => fn($q) => $q->where('source', 'ai_scan'),
            ])
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'email', 'is_active', 'plan', 'plan_expires_at', 'ai_scan_limit', 'export_limit', 'created_at'])
            ->map(function (User $user) {
                $lastEntry = WasteEntry::where('user_id', $user->id)
                    ->orderByDesc('logged_at')
                    ->value('logged_at');

                return [
                    'id'               => $user->id,
                    'name'             => $user->name,
                    'email'            => $user->email,
                    'is_active'        => $user->is_active,
                    'plan'             => $user->effectivePlan(),
                    'plan_raw'         => $user->plan,
                    'plan_expires_at'  => $user->plan_expires_at?->toDateString(),
                    'created_at'       => $user->created_at,
                    'total_entries'    => $user->waste_entries_count,
                    'ai_scans_used'    => $user->aiScansUsedThisMonth(),
                    'ai_scan_quota'    => $user->aiScanQuota(),
                    'ai_scan_count'    => $user->ai_scan_count,
                    'exports_used'     => $user->exportsUsedThisMonth(),
                    'export_quota'     => $user->exportQuota(),
                    'last_entry_at'    => $lastEntry,
                ];
            });

        return Inertia::render('admin/Users', [
            'users' => $users,
        ]);
    }

    public function showUser(User $user): Response
    {
        abort_if($user->is_admin, 404);

        $entries = WasteEntry::where('user_id', $user->id)
            ->orderByDesc('logged_at')
            ->get(['id', 'item_name', 'category', 'weight_kg', 'reason', 'notes', 'source', 'logged_at']);

        $stats = [
            'total_entries' => $entries->count(),
            'total_kg'      => round((float) $entries->sum('weight_kg'), 2),
            'ai_scan_count' => $entries->where('source', 'ai_scan')->count(),
            'this_month'    => $entries->where('logged_at', '>=', now()->startOfMonth())->count(),
        ];

        return Inertia::render('admin/UserShow', [
            'user'    => [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'is_active'       => $user->is_active,
                'plan'            => $user->effectivePlan(),
                'plan_raw'        => $user->plan,
                'plan_expires_at' => $user->plan_expires_at?->toDateString(),
                'ai_scan_limit'   => $user->ai_scan_limit,
                'export_limit'    => $user->export_limit,
                'ai_scans_used'   => $user->aiScansUsedThisMonth(),
                'ai_scan_quota'   => $user->aiScanQuota(),
                'exports_used'    => $user->exportsUsedThisMonth(),
                'export_quota'    => $user->exportQuota(),
                'created_at'      => $user->created_at,
            ],
            'entries' => $entries,
            'stats'   => $stats,
        ]);
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is_admin, 403);

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        return back()->with('success', 'User updated.');
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is_admin, 403);

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = bcrypt($request->password);
        $user->save();

        return back()->with('success', 'Password updated.');
    }

    public function updatePlan(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is_admin, 403);

        $validated = $request->validate([
            'plan'            => ['required', Rule::in(['free', 'pro'])],
            'plan_expires_at' => ['nullable', 'date'],
            'ai_scan_limit'   => ['nullable', 'integer', 'min:0'],
            'export_limit'    => ['nullable', 'integer', 'min:0'],
        ]);

        $user->plan            = $validated['plan'];
        $user->plan_expires_at = $validated['plan_expires_at'] ?? null;
        $user->ai_scan_limit   = $validated['ai_scan_limit'] ?? null;
        $user->export_limit    = $validated['export_limit'] ?? null;
        $user->save();

        return back()->with('success', 'Subscription updated.');
    }

    public function destroyUser(User $user): RedirectResponse
    {
        abort_if($user->is_admin, 403);

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User deleted.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        abort_if($user->is_admin, 403);

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('success', $user->is_active ? 'User activated.' : 'User deactivated.');
    }

    public function updateEntry(Request $request, WasteEntry $entry): RedirectResponse
    {
        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:150'],
            'category'  => ['required', Rule::in(['protein', 'veg', 'dairy', 'prepared'])],
            'weight_kg' => ['required', 'numeric', 'min:0.001', 'max:9999'],
            'reason'    => ['required', Rule::in(['spoilage', 'overproduction', 'expiry', 'prep_waste', 'other'])],
            'notes'     => ['nullable', 'string', 'max:500'],
            'logged_at' => ['required', 'date'],
        ]);

        $entry->update($validated);

        return back()->with('success', 'Entry updated.');
    }

    public function destroyEntry(WasteEntry $entry): RedirectResponse
    {
        $entry->delete();

        return back()->with('success', 'Entry deleted.');
    }
}
