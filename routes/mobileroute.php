<?php

use App\Http\Controllers\mobileapi\FacilityController as CustomFacilityController;
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
});
