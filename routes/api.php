<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileCollectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [MobileAuthController::class, 'login']);

    Route::middleware('mobile.auth')->group(function () {
        Route::post('auth/logout', [MobileAuthController::class, 'logout']);
        Route::get('auth/me', [MobileAuthController::class, 'me']);

        Route::get('lookups', [MobileCollectionController::class, 'lookups']);

        Route::get('animal-intakes', [MobileCollectionController::class, 'animalIntakesIndex']);
        Route::post('animal-intakes', [MobileCollectionController::class, 'animalIntakesStore']);

        Route::get('slaughter-plans', [MobileCollectionController::class, 'slaughterPlansIndex']);
        Route::post('slaughter-plans', [MobileCollectionController::class, 'slaughterPlansStore']);

        Route::get('slaughter-executions', [MobileCollectionController::class, 'slaughterExecutionsIndex']);
        Route::post('slaughter-executions', [MobileCollectionController::class, 'slaughterExecutionsStore']);

        Route::post('ante-mortem-inspections', [MobileCollectionController::class, 'anteMortemStore']);
        Route::post('post-mortem-inspections', [MobileCollectionController::class, 'postMortemStore']);
    });
});
