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

// Login not working? Visit this URL on your site to see what to fix (e.g. https://dayare.sandbox.rw/login-check)
Route::get('/login-check', function () {
    $secure = request()->secure();
    $host = request()->getHost();
    $sessionSecure = config('session.secure');
    $sessionDomain = config('session.domain');
    $sessionDriver = config('session.driver');
    $lines = [
        '=== Login check ===',
        'Your request: ' . request()->getScheme() . '://' . $host,
        'Site is HTTPS: ' . ($secure ? 'yes' : 'no'),
        '',
        'Current session config:',
        'SESSION_DRIVER=' . $sessionDriver,
        'SESSION_SECURE_COOKIE=' . ($sessionSecure ? 'true' : 'false or unset'),
        'SESSION_DOMAIN=' . ($sessionDomain ?: '(empty)'),
        '',
    ];
    if ($secure && ! $sessionSecure) {
        $lines[] = '>>> FIX: Site is HTTPS but session cookie is not Secure — browser may not send it.';
        $lines[] = '>>> In .env on the server add: SESSION_SECURE_COOKIE=true';
        $lines[] = '>>> Run: php artisan config:clear';
        $lines[] = '>>> Clear browser cookies, then try login again.';
    } elseif (! $secure && $sessionSecure) {
        $lines[] = '>>> FIX: Site is HTTP but SESSION_SECURE_COOKIE is true — cookie will not be sent.';
        $lines[] = '>>> In .env set: SESSION_SECURE_COOKIE=false (or remove the line)';
        $lines[] = '>>> Run: php artisan config:clear';
    } else {
        $lines[] = '>>> If login still fails, try in .env: SESSION_DRIVER=cookie';
        $lines[] = '>>> Run: php artisan config:clear and clear browser cookies.';
    }
    return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('login.check');

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
