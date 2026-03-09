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
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SpeciesController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DemandController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\CrmDashboardController;
use App\Http\Controllers\ClientActivityController;
use App\Http\Controllers\SuperAdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->isSuperAdmin() ? 'super-admin.dashboard' : 'dashboard');
    }
    return view('welcome');
})->name('home');

Route::get('/trace/{slug}', [\App\Http\Controllers\TraceabilityController::class, 'show'])->name('traceability.show');

// Temporary: debug why /businesses/{id}/facilities 404 on cPanel. Remove after fixing.
Route::get('/debug-facilities-route', function () {
    if (! config('app.debug')) {
        abort(404);
    }
    $businessId = (int) (request('business_id', 5));
    $business = \App\Models\Business::find($businessId);
    $user = auth()->user();
    $facilitiesRouteExists = collect(\Illuminate\Support\Facades\Route::getRoutes())->contains(fn ($r) => $r->getName() === 'businesses.facilities.index');

    return response()->json([
        'message' => 'Debug: facilities route check',
        'business_id_checked' => $businessId,
        'business_exists' => (bool) $business,
        'business_user_id' => $business?->user_id,
        'logged_in_user_id' => $user?->id,
        'logged_in_email' => $user?->email,
        'ownership_match' => $business && $user ? ($business->user_id === $user->id) : false,
        'facilities_route_registered' => $facilitiesRouteExists,
        'suggestion' => ! $facilitiesRouteExists
            ? 'Run: php artisan route:clear && php artisan config:clear'
            : ($business && $user && $business->user_id !== $user->id
                ? 'Business belongs to another user. Update businesses.user_id to ' . $user->id . ' for this business.'
                : 'Route and ownership OK. If still 404, check document root points to /public and .htaccess is in use.'),
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
})->middleware('auth');

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

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::resource('species', SpeciesController::class)->except('show');
    Route::resource('units', UnitController::class)->except('show');

    // CRM / HR modules (full CRUD)
    Route::resource('employees', EmployeeController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::get('contracts/{contract}/file/{type}/{filename}', [ContractController::class, 'downloadFile'])->name('contracts.file.download');
    Route::resource('contracts', ContractController::class);
    Route::get('crm', [CrmDashboardController::class, 'index'])->name('crm.dashboard');
    Route::resource('clients', ClientController::class);
    Route::post('clients/{client}/activities', [ClientController::class, 'storeActivity'])->name('clients.activities.store');
    Route::resource('demands', DemandController::class);
    Route::get('recipients', [RecipientController::class, 'index'])->name('recipients.index');
    Route::delete('client-activities/{client_activity}', [App\Http\Controllers\ClientActivityController::class, 'destroy'])->name('client-activities.destroy');

    Route::middleware('super_admin')->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
