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
