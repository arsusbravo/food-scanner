<?php

use App\Http\Controllers\Waste\AIScanController;
use App\Http\Controllers\Waste\ContactController;
use App\Http\Controllers\Waste\InsightsController;
use App\Http\Controllers\Waste\PriceReportController;
use App\Http\Controllers\Waste\PurchaseReportController;
use App\Http\Controllers\Waste\ReceiptController;
use App\Http\Controllers\Waste\ReportController;
use App\Http\Controllers\Waste\SubscriptionController;
use App\Http\Controllers\Waste\WasteEntryController;
use App\Http\Controllers\Waste\WasteSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'user-active'])->prefix('waste')->name('waste.')->group(function () {
    Route::get('/', [WasteEntryController::class, 'home'])->name('home');

    Route::resource('entries', WasteEntryController::class)->only(['index', 'store', 'destroy']);

    Route::get('ai-scan', [AIScanController::class, 'index'])->name('ai-scan.index');
    Route::post('ai-scan', [AIScanController::class, 'store'])->name('ai-scan.store');

    Route::get('receipts', [ReceiptController::class, 'index'])->name('receipts.index');
    Route::get('receipts/scan', [ReceiptController::class, 'create'])->name('receipts.create');
    Route::post('receipts/scan', [ReceiptController::class, 'scan'])->name('receipts.scan');
    Route::get('receipts/export', [ReceiptController::class, 'exportCsv'])->name('receipts.export');
    Route::get('receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::patch('receipts/{receipt}', [ReceiptController::class, 'update'])->name('receipts.update');
    Route::delete('receipts/{receipt}', [ReceiptController::class, 'destroy'])->name('receipts.destroy');
    Route::get('receipts/{receipt}/images/{image}', [ReceiptController::class, 'image'])->name('receipts.image');

    Route::get('insights', [InsightsController::class, 'index'])->name('insights.index');

    Route::get('settings', [WasteSettingsController::class, 'index'])->name('settings.index');
    Route::patch('settings/profile', [WasteSettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::patch('settings/company', [WasteSettingsController::class, 'updateCompany'])->name('settings.company');
    Route::patch('settings/prices', [WasteSettingsController::class, 'updatePrices'])->name('settings.prices');
    Route::patch('settings/password', [WasteSettingsController::class, 'updatePassword'])->name('settings.password');
    Route::patch('settings/document-locale', [WasteSettingsController::class, 'updateDocumentLocale'])->name('settings.document-locale');

    Route::get('report', [ReportController::class, 'index'])->name('report.index');
    Route::get('report/purchases', [PurchaseReportController::class, 'index'])->name('report.purchases');
    Route::get('report/prices', [PriceReportController::class, 'index'])->name('report.prices');
    Route::post('report/prices/links', [PriceReportController::class, 'link'])->name('report.prices.link');
    Route::delete('report/prices/links/{link}', [PriceReportController::class, 'unlink'])->name('report.prices.unlink');
    Route::get('report/csv', [ReportController::class, 'exportCsv'])->name('report.csv');
    Route::get('report/pdf', [ReportController::class, 'exportPdf'])->name('report.pdf');

    Route::get('subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::get('subscription/success', [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('subscription/portal', [SubscriptionController::class, 'portal'])->name('subscription.portal');

    Route::get('contact', [ContactController::class, 'index'])->name('contact.index');
    Route::post('contact', [ContactController::class, 'store'])->name('contact.store');
    Route::get('contact/new', fn () => inertia('waste/ContactNew'))->name('contact.new');
    Route::get('contact/{conversation}', [ContactController::class, 'show'])->name('contact.show');
    Route::post('contact/{conversation}/reply', [ContactController::class, 'reply'])->name('contact.reply');
});
