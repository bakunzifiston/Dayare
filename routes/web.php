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
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DemandController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\CrmDashboardController;
use App\Http\Controllers\ClientActivityController;
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

    // CRM / HR modules (full CRUD)
    Route::resource('employees', EmployeeController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('contracts', ContractController::class);
    Route::get('crm', [CrmDashboardController::class, 'index'])->name('crm.dashboard');
    Route::resource('clients', ClientController::class);
    Route::post('clients/{client}/activities', [ClientController::class, 'storeActivity'])->name('clients.activities.store');
    Route::resource('demands', DemandController::class);
    Route::get('recipients', [RecipientController::class, 'index'])->name('recipients.index');
    Route::delete('client-activities/{client_activity}', [App\Http\Controllers\ClientActivityController::class, 'destroy'])->name('client-activities.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
