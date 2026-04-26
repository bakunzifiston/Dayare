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

    Route::post('auth/login', [MobileAuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::post('auth/register', [MobileAuthController::class, 'register'])
        ->middleware('throttle:10,1');

    Route::middleware('mobile.auth')->group(function () {
        Route::post('auth/logout', [MobileAuthController::class, 'logout']);
        Route::get('auth/me', [MobileAuthController::class, 'me']);

        Route::post('businesses', [MobileBusinessController::class, 'store']);

        Route::get('lookups', [MobileCollectionController::class, 'lookups']);
        Route::get('suppliers', [MobileCollectionController::class, 'suppliersIndex']);
        Route::post('suppliers', [MobileCollectionController::class, 'suppliersStore']);

        Route::get('animal-intakes', [MobileCollectionController::class, 'animalIntakesIndex']);
        Route::post('animal-intakes', [MobileCollectionController::class, 'animalIntakesStore']);
        Route::get('animal-intakes/{animalIntake}', [MobileCollectionController::class, 'animalIntakesShow']);
        Route::put('animal-intakes/{animalIntake}', [MobileCollectionController::class, 'animalIntakesUpdate']);
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

        Route::get('batches', [MobileCollectionController::class, 'batchesIndex']);
        Route::post('batches', [MobileCollectionController::class, 'batchesStore']);

        Route::post('ante-mortem-inspections', [MobileCollectionController::class, 'anteMortemStore']);
        Route::post('post-mortem-inspections', [MobileCollectionController::class, 'postMortemStore']);
        Route::post('certificates', [MobileCollectionController::class, 'certificatesStore']);
        Route::post('transport-trips', [MobileCollectionController::class, 'transportTripsStore']);
        Route::post('delivery-confirmations', [MobileCollectionController::class, 'deliveryConfirmationsStore']);
        Route::post('warehouse-storages', [MobileCollectionController::class, 'warehouseStoragesStore']);

        // Include custom mobile API routes
        require base_path('routes/mobileroute.php');
    });
});
