<?php

use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Stripe\WebhookController as StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return view('welcome', ['canRegister' => Features::enabled(Features::registration())]);
})->name('home');

Route::get('/docs', fn () => view('docs'))->name('docs');
Route::get('/faq', fn () => view('faq'))->name('faq');

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

    // Registration management
    Route::get('admin/registrations', [RegistrationController::class, 'index'])->name('admin.registrations');
    Route::patch('admin/registrations/mode', [RegistrationController::class, 'updateMode'])->name('admin.registrations.mode');
    Route::post('admin/registrations/invitations', [RegistrationController::class, 'storeInvitation'])->name('admin.registrations.invitations.store');
    Route::delete('admin/registrations/invitations/{invitation}', [RegistrationController::class, 'destroyInvitation'])->name('admin.registrations.invitations.destroy');
});

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

require __DIR__.'/settings.php';
require __DIR__.'/waste.php';
