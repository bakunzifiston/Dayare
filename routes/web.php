<?php

use App\Http\Controllers\AnteMortemInspectionController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\InspectorController;
use App\Http\Controllers\PostMortemInspectionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SlaughterExecutionController;
use App\Http\Controllers\SlaughterPlanController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\DeliveryConfirmationController;
use App\Http\Controllers\TransportTripController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
})->name('home');

Route::get('/trace/{slug}', [\App\Http\Controllers\TraceabilityController::class, 'show'])->name('traceability.show');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('dashboard');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::resource('businesses', BusinessController::class);
    Route::resource('businesses.facilities', FacilityController::class);
    Route::resource('inspectors', InspectorController::class);
    Route::resource('slaughter-plans', SlaughterPlanController::class);
    Route::resource('slaughter-executions', SlaughterExecutionController::class);
    Route::resource('batches', BatchController::class);
    Route::resource('post-mortem-inspections', PostMortemInspectionController::class);
    Route::resource('certificates', CertificateController::class);
    Route::get('certificates/{certificate}/qr', [CertificateController::class, 'qr'])->name('certificates.qr');
    Route::resource('transport-trips', TransportTripController::class);
    Route::resource('delivery-confirmations', DeliveryConfirmationController::class);
    Route::get('compliance', [ComplianceController::class, 'index'])->name('compliance.index');
    Route::resource('ante-mortem-inspections', AnteMortemInspectionController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
