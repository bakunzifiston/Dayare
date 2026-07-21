<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileBusinessController;
use App\Http\Controllers\Api\MobileCollectionController;
use App\Http\Responses\ApiJson;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile JSON API (/api/v1)
|--------------------------------------------------------------------------
| Bearer auth via mobile_api_tokens. Envelope: ApiJson (success, message, data|errors).
| Future: REST-style show/update/destroy routes may be added here (e.g. GET/PATCH/DELETE on IDs).
*/

Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return ApiJson::success([
            'name' => 'Butchapro API',
            'version' => '1',
            'documentation' => url('/api/documentation'),
        ], __('Butchapro mobile API.'));
    });

    Route::get('verify/permit/{identifier}', [\App\Http\Controllers\PublicPermitVerificationController::class, 'api'])
        ->middleware('throttle:60,1');

    Route::post('auth/login', [MobileAuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::post('auth/register', [MobileAuthController::class, 'register'])
        ->middleware('throttle:10,1');

    Route::middleware('mobile.auth')->group(function () {
        Route::post('auth/logout', [MobileAuthController::class, 'logout']);
        Route::get('auth/me', [MobileAuthController::class, 'me']);

        Route::post('businesses', [MobileBusinessController::class, 'store']);

        Route::get('dashboard', [MobileCollectionController::class, 'dashboard']);
        Route::get('lookups', [MobileCollectionController::class, 'lookups']);

        Route::get('animal-intakes', [MobileCollectionController::class, 'animalIntakesIndex']);
        Route::post('animal-intakes', [MobileCollectionController::class, 'animalIntakesStore']);
        Route::get('animal-intakes/{animalIntake}', [MobileCollectionController::class, 'animalIntakesShow']);
        Route::put('animal-intakes/{animalIntake}', [MobileCollectionController::class, 'animalIntakesUpdate']);
        Route::post('animal-intakes/{animalIntake}/submit', [MobileCollectionController::class, 'animalIntakesSubmit']);
        Route::delete('animal-intakes/{animalIntake}', [MobileCollectionController::class, 'animalIntakesDestroy']);

        Route::get('slaughter-plans', [MobileCollectionController::class, 'slaughterPlansIndex']);
        Route::post('slaughter-plans', [MobileCollectionController::class, 'slaughterPlansStore']);
        Route::get('slaughter-plans/{slaughterPlan}', [MobileCollectionController::class, 'slaughterPlansShow']);
        Route::put('slaughter-plans/{slaughterPlan}', [MobileCollectionController::class, 'slaughterPlansUpdate']);
        Route::delete('slaughter-plans/{slaughterPlan}', [MobileCollectionController::class, 'slaughterPlansDestroy']);

        Route::get('slaughter-executions', [MobileCollectionController::class, 'slaughterExecutionsIndex']);
        Route::post('slaughter-executions', [MobileCollectionController::class, 'slaughterExecutionsStore']);
        Route::get('slaughter-executions/{slaughterExecution}', [MobileCollectionController::class, 'slaughterExecutionsShow']);
        Route::put('slaughter-executions/{slaughterExecution}', [MobileCollectionController::class, 'slaughterExecutionsUpdate']);
        Route::delete('slaughter-executions/{slaughterExecution}', [MobileCollectionController::class, 'slaughterExecutionsDestroy']);

        Route::get('monthly-inspection-reports', [MobileCollectionController::class, 'monthlyInspectionReportsIndex']);
        Route::post('monthly-inspection-reports/{facility}/closure', [MobileCollectionController::class, 'monthlyInspectionReportsClosure']);
        Route::get('monthly-inspection-reports/{facility}', [MobileCollectionController::class, 'monthlyInspectionReportsShow']);

        Route::get('inspectors', [MobileCollectionController::class, 'inspectorsIndex']);
        Route::post('inspectors', [MobileCollectionController::class, 'inspectorsStore']);
        Route::get('inspectors/{inspector}', [MobileCollectionController::class, 'inspectorsShow']);
        Route::put('inspectors/{inspector}', [MobileCollectionController::class, 'inspectorsUpdate']);
        Route::delete('inspectors/{inspector}', [MobileCollectionController::class, 'inspectorsDestroy']);

        Route::get('batches', [MobileCollectionController::class, 'batchesIndex']);
        Route::get('batches/{batch}', [MobileCollectionController::class, 'batchesShow']);

        Route::get('ante-mortem-inspections', [MobileCollectionController::class, 'anteMortemIndex']);
        Route::post('ante-mortem-inspections', [MobileCollectionController::class, 'anteMortemStore']);
        Route::get('ante-mortem-inspections/{anteMortemInspection}', [MobileCollectionController::class, 'anteMortemShow']);
        Route::put('ante-mortem-inspections/{anteMortemInspection}', [MobileCollectionController::class, 'anteMortemUpdate']);
        Route::delete('ante-mortem-inspections/{anteMortemInspection}', [MobileCollectionController::class, 'anteMortemDestroy']);
        Route::get('post-mortem-inspections', [MobileCollectionController::class, 'postMortemIndex']);
        Route::post('post-mortem-inspections', [MobileCollectionController::class, 'postMortemStore']);
        Route::get('post-mortem-inspections/{postMortemInspection}', [MobileCollectionController::class, 'postMortemShow']);
        Route::put('post-mortem-inspections/{postMortemInspection}', [MobileCollectionController::class, 'postMortemUpdate']);
        Route::delete('post-mortem-inspections/{postMortemInspection}', [MobileCollectionController::class, 'postMortemDestroy']);
        Route::get('certificates', [MobileCollectionController::class, 'certificatesIndex']);
        Route::post('certificates', [MobileCollectionController::class, 'certificatesStore']);
        Route::get('certificates/{certificate}/qr', [MobileCollectionController::class, 'certificatesQr']);
        Route::get('certificates/{certificate}/pdf', [MobileCollectionController::class, 'certificatesPdf']);
        Route::get('certificates/{certificate}', [MobileCollectionController::class, 'certificatesShow']);
        Route::put('certificates/{certificate}', [MobileCollectionController::class, 'certificatesUpdate']);
        Route::delete('certificates/{certificate}', [MobileCollectionController::class, 'certificatesDestroy']);
        Route::get('transport-trips', [MobileCollectionController::class, 'transportTripsIndex']);
        Route::get('transport-trips/{transportTrip}', [MobileCollectionController::class, 'transportTripsShow']);
        Route::post('transport-trips', [MobileCollectionController::class, 'transportTripsStore']);
        Route::get('transport-trips/export', [MobileCollectionController::class, 'transportTripsExport']);
        Route::post('delivery-confirmations', [MobileCollectionController::class, 'deliveryConfirmationsStore']);
        Route::get('delivery-confirmations/export', [MobileCollectionController::class, 'deliveryConfirmationsExport']);
        Route::post('warehouse-storages', [MobileCollectionController::class, 'warehouseStoragesStore']);
    });
});
