<?php

use App\Http\Controllers\mobileapi\FacilityController as CustomFacilityController;
use App\Http\Controllers\mobileapi\InspectorController as MobileInspectorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Custom Mobile API Routes (mobileapi folder)
|--------------------------------------------------------------------------
*/

Route::prefix('mobileapi')->group(function () {
    // Facility Management
    Route::get('businesses/{business}/facilities', [CustomFacilityController::class, 'index']);
    Route::post('businesses/{business}/facilities', [CustomFacilityController::class, 'store']);
    Route::get('businesses/{business}/facilities/{facility}', [CustomFacilityController::class, 'show']);
    Route::put('businesses/{business}/facilities/{facility}', [CustomFacilityController::class, 'update']);
    Route::delete('businesses/{business}/facilities/{facility}', [CustomFacilityController::class, 'destroy']);

    // Inspector Management
    Route::get('inspectors', [MobileInspectorController::class, 'index']);
    Route::post('inspectors', [MobileInspectorController::class, 'store']);
    Route::get('businesses/{business}/inspectors', [MobileInspectorController::class, 'byBusiness']);
    Route::get('inspectors/{inspector}', [MobileInspectorController::class, 'show']);
    Route::put('inspectors/{inspector}', [MobileInspectorController::class, 'update']);
    Route::get('facilities/{facility}/inspectors', [MobileInspectorController::class, 'byFacility']);
});
