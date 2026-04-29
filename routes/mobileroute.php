<?php

use App\Http\Controllers\mobileapi\FacilityController as CustomFacilityController;
use App\Http\Controllers\mobileapi\InspectorController as MobileInspectorController;
use App\Http\Controllers\mobileapi\OperatorManagerController as MobileOperatorManagerController;
use App\Http\Controllers\mobileapi\SupplierController as MobileSupplierController;
use App\Http\Controllers\mobileapi\ContractController as MobileContractController;
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
    Route::get('{operator_manager_id}/facilities', [CustomFacilityController::class, 'getByOperatorManagerId']);
    Route::get('facilities/{facility}/animal-intakes', [\App\Http\Controllers\Api\MobileCollectionController::class, 'animalIntakesByFacility']);
    Route::get('facilities/{facility}/slaughter-plans', [\App\Http\Controllers\Api\MobileCollectionController::class, 'slaughterPlansByFacility']);
    Route::get('facilities/{facility}/slaughter-executions', [\App\Http\Controllers\Api\MobileCollectionController::class, 'slaughterExecutionsByFacility']);
    Route::get('facilities/{facility}/batches', [\App\Http\Controllers\Api\MobileCollectionController::class, 'batchesByFacility']);

    // Inspector Management
    Route::get('inspectors', [MobileInspectorController::class, 'index']);
    Route::post('inspectors', [MobileInspectorController::class, 'store']);
    Route::get('businesses/{business}/inspectors', [MobileInspectorController::class, 'byBusiness']);
    Route::get('inspectors/{inspector}', [MobileInspectorController::class, 'show']);
    Route::put('inspectors/{inspector}', [MobileInspectorController::class, 'update']);
    Route::get('inspectors/{inspector}/slaughter-plans', [MobileInspectorController::class, 'slaughterPlans']);
    Route::get('inspectors/{inspector}/post-mortem-inspections', [MobileInspectorController::class, 'postMortemInspections']);
    Route::get('inspectors/{inspector}/ante-mortem-inspections', [MobileInspectorController::class, 'anteMortemInspections']);
    Route::get('inspectors/{inspector}/batches', [MobileInspectorController::class, 'batches']);
    Route::get('facilities/{facility}/inspectors', [MobileInspectorController::class, 'byFacility']);

    // Operator Manager Management
    Route::get('operator-managers', [MobileOperatorManagerController::class, 'index']);
    Route::post('operator-managers', [MobileOperatorManagerController::class, 'store']);
    Route::get('businesses/{business}/operator-managers', [MobileOperatorManagerController::class, 'byBusiness']);
    Route::get('operator-managers/{operator_manager}', [MobileOperatorManagerController::class, 'show']);
    Route::put('operator-managers/{operator_manager}', [MobileOperatorManagerController::class, 'update']);
    Route::delete('operator-managers/{operator_manager}', [MobileOperatorManagerController::class, 'destroy']);
    Route::get('facilities/{facility}/operator-managers', [MobileOperatorManagerController::class, 'byFacility']);
    Route::get('operator-managers/{operator_manager}/facility', [MobileOperatorManagerController::class, 'facility']);
    Route::get('operator-managers/{operator_manager}/slaughter-executions', [MobileOperatorManagerController::class, 'slaughterExecutions']);

    // Supplier Management
    Route::get('businesses/{business}/suppliers', [MobileSupplierController::class, 'index']);

    // Contract Management
    Route::get('businesses/{business}/contracts', [MobileContractController::class, 'index']);
    Route::post('businesses/{business}/contracts', [MobileContractController::class, 'store']);
    
    // Batch Management
    Route::get('batches', [\App\Http\Controllers\Api\MobileCollectionController::class, 'batchesIndex']);
    Route::post('batches', [\App\Http\Controllers\Api\MobileCollectionController::class, 'batchesStore']);
});
