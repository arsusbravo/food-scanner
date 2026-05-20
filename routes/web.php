<?php

use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\Stripe\WebhookController as StripeWebhookController;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Refresh-only endpoint for SPA fetch callers. Reading this in a GET ensures
// the response sets/refreshes the XSRF cookie and returns the current token,
// so a stale meta tag (after long idle) can be recovered without a reload.
Route::get('/csrf-token', fn () => response()->json(['token' => csrf_token()]));

Route::get('/', function (Request $request) {
    $token = $request->query('token');

    if ($token) {
        $invitation = Invitation::valid()->where('token', $token)->first();
        if ($invitation) {
            // Count click once per session per token
            if ($request->session()->get('invite_click_counted') !== $token) {
                $invitation->increment('clicks');
                $request->session()->put('invite_click_counted', $token);
            }
            $request->session()->put('invite_token', $token);
        }
    }

    return view('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'inviteToken' => $request->session()->get('invite_token'),
    ]);
})->name('home');

// Public, no-signup demo (real AI scan + report). Metered per device by
// DemoQuota; Turnstile-gated; throttled as a network-level backstop.
Route::prefix('demo')->name('demo.')->middleware('throttle:20,1')->group(function () {
    Route::get('/',           [DemoController::class, 'index'])->name('index');
    Route::post('scan',       [DemoController::class, 'scan'])->name('scan');
    Route::post('report',     [DemoController::class, 'report'])->name('report');
    Route::post('report/pdf', [DemoController::class, 'reportPdf'])->name('report.pdf');
});

Route::get('/docs', fn () => view('docs'))->name('docs');
Route::get('/faq', fn () => view('faq'))->name('faq');
Route::get('/privacy', fn () => view('privacy'))->name('privacy');
Route::get('/terms', fn () => view('terms'))->name('terms');
Route::get('/cookies', fn () => view('cookies'))->name('cookies');

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Users management
    Route::get('admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('admin/users/{user}', [AdminController::class, 'showUser'])->name('admin.users.show');
    Route::patch('admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('admin/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
    Route::patch('admin/users/{user}/toggle-active', [AdminController::class, 'toggleActive'])->name('admin.users.toggle-active');
    Route::patch('admin/users/{user}/password', [AdminController::class, 'updatePassword'])->name('admin.users.update-password');
    Route::patch('admin/users/{user}/plan', [AdminController::class, 'updatePlan'])->name('admin.users.update-plan');

    // Entry management (admin)
    Route::patch('admin/entries/{entry}', [AdminController::class, 'updateEntry'])->name('admin.entries.update');
    Route::delete('admin/entries/{entry}', [AdminController::class, 'destroyEntry'])->name('admin.entries.destroy');

    // Contact conversations
    Route::get('admin/contact',                                   [\App\Http\Controllers\Admin\ContactController::class, 'index'])->name('admin.contact');
    Route::get('admin/contact/{conversation}',                    [\App\Http\Controllers\Admin\ContactController::class, 'show'])->name('admin.contact.show');
    Route::post('admin/contact/{conversation}/reply',             [\App\Http\Controllers\Admin\ContactController::class, 'reply'])->name('admin.contact.reply');

    // Registration management
    Route::get('admin/registrations', [RegistrationController::class, 'index'])->name('admin.registrations');
    Route::patch('admin/registrations/mode', [RegistrationController::class, 'updateMode'])->name('admin.registrations.mode');
    Route::post('admin/registrations/invitations', [RegistrationController::class, 'storeInvitation'])->name('admin.registrations.invitations.store');
    Route::delete('admin/registrations/invitations/{invitation}', [RegistrationController::class, 'destroyInvitation'])->name('admin.registrations.invitations.destroy');
});

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

require __DIR__.'/settings.php';
require __DIR__.'/waste.php';
