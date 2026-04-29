<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Users management
    Route::get('admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('admin/users/{user}', [AdminController::class, 'showUser'])->name('admin.users.show');
    Route::patch('admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('admin/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
    Route::patch('admin/users/{user}/toggle-active', [AdminController::class, 'toggleActive'])->name('admin.users.toggle-active');
    Route::patch('admin/users/{user}/password', [AdminController::class, 'updatePassword'])->name('admin.users.update-password');

    // Entry management (admin)
    Route::patch('admin/entries/{entry}', [AdminController::class, 'updateEntry'])->name('admin.entries.update');
    Route::delete('admin/entries/{entry}', [AdminController::class, 'destroyEntry'])->name('admin.entries.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/waste.php';
