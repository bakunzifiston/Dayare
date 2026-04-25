<?php

use App\Http\Controllers\mobileapi\FacilityController as CustomFacilityController;
use App\Http\Controllers\mobileapi\InspectorController as MobileInspectorController;
use App\Http\Controllers\mobileapi\OperatorManagerController as MobileOperatorManagerController;
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

    // Operator Manager Management
    Route::get('operator-managers', [MobileOperatorManagerController::class, 'index']);
    Route::post('operator-managers', [MobileOperatorManagerController::class, 'store']);
    Route::get('businesses/{business}/operator-managers', [MobileOperatorManagerController::class, 'byBusiness']);
    Route::get('operator-managers/{operator_manager}', [MobileOperatorManagerController::class, 'show']);
    Route::put('operator-managers/{operator_manager}', [MobileOperatorManagerController::class, 'update']);
    Route::delete('operator-managers/{operator_manager}', [MobileOperatorManagerController::class, 'destroy']);
    Route::get('facilities/{facility}/operator-managers', [MobileOperatorManagerController::class, 'byFacility']);
});
