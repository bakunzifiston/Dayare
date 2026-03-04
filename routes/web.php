<?php

use App\Http\Controllers\AdministrativeDivisionController;
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
use App\Http\Controllers\AnimalIntakeController;
use App\Http\Controllers\SlaughterPlanController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\DeliveryConfirmationController;
use App\Http\Controllers\TransportTripController;
use App\Http\Controllers\WarehouseStorageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
})->name('home');

Route::get('/trace/{slug}', [\App\Http\Controllers\TraceabilityController::class, 'show'])->name('traceability.show');

// Diagnostics (only when APP_DEBUG=true): find why session is lost after login on production
if (config('app.debug')) {
    Route::get('/session-debug', function () {
        return response()->json([
            'request' => [
                'host' => request()->getHost(),
                'scheme' => request()->getScheme(),
                'url' => request()->fullUrl(),
            ],
            'config' => [
                'app_url' => config('app.url'),
                'session_domain' => config('session.domain'),
                'session_secure' => config('session.secure'),
                'session_driver' => config('session.driver'),
                'session_cookie_name' => config('session.cookie'),
            ],
            'session_id' => session()->getId(),
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'session_cookie_received' => request()->hasCookie(config('session.cookie')),
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    })->name('session.debug');

    // Cookie test: visit /cookie-test twice. First visit sets cookie and redirects; second shows if cookie was received.
    Route::get('/cookie-test', function () {
        if (request()->cookie('_dayare_cookie_test') === null) {
            return redirect()->to(url('/cookie-test'))->cookie('_dayare_cookie_test', '1', 5);
        }
        return response()->json([
            'cookie_received' => true,
            'message' => 'Cookie was set and received. If session still fails, use SESSION_DRIVER=cookie or check session domain/secure.',
        ], 200, [], JSON_PRETTY_PRINT);
    });
}

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('dashboard');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::resource('businesses', BusinessController::class);
    Route::resource('businesses.facilities', FacilityController::class);
    Route::resource('inspectors', InspectorController::class);
    Route::resource('animal-intakes', AnimalIntakeController::class);
    Route::resource('slaughter-plans', SlaughterPlanController::class);
    Route::resource('slaughter-executions', SlaughterExecutionController::class);
    Route::resource('batches', BatchController::class);
    Route::resource('post-mortem-inspections', PostMortemInspectionController::class);
    Route::resource('certificates', CertificateController::class);
    Route::get('certificates/{certificate}/qr', [CertificateController::class, 'qr'])->name('certificates.qr');
    Route::resource('warehouse-storages', WarehouseStorageController::class);
    Route::post('warehouse-storages/{warehouse_storage}/temperature-logs', [WarehouseStorageController::class, 'storeTemperatureLog'])->name('warehouse-storages.temperature-logs.store');
    Route::delete('warehouse-storages/{warehouse_storage}/temperature-logs/{temperature_log}', [WarehouseStorageController::class, 'destroyTemperatureLog'])->name('warehouse-storages.temperature-logs.destroy');
    Route::resource('transport-trips', TransportTripController::class);
    Route::resource('delivery-confirmations', DeliveryConfirmationController::class);
    Route::get('compliance', [ComplianceController::class, 'index'])->name('compliance.index');
    Route::get('divisions', [AdministrativeDivisionController::class, 'index'])->name('divisions.index');
    Route::resource('ante-mortem-inspections', AnteMortemInspectionController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
