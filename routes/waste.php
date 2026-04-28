<?php

use App\Http\Controllers\Waste\AIScanController;
use App\Http\Controllers\Waste\ReportController;
use App\Http\Controllers\Waste\WasteEntryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('waste')->name('waste.')->group(function () {
    Route::get('/', [WasteEntryController::class, 'home'])->name('home');

    Route::resource('entries', WasteEntryController::class)->only(['index', 'store', 'destroy']);

    Route::get('ai-scan', [AIScanController::class, 'index'])->name('ai-scan.index');
    Route::post('ai-scan', [AIScanController::class, 'store'])->name('ai-scan.store');

    Route::get('report', [ReportController::class, 'index'])->name('report.index');
    Route::get('report/csv', [ReportController::class, 'exportCsv'])->name('report.csv');
    Route::get('report/pdf', [ReportController::class, 'exportPdf'])->name('report.pdf');
});
