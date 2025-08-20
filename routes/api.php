<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BacaraAnalyticsController;
use App\Http\Controllers\Api\AiPredictionController;
use App\Http\Controllers\Api\ChartController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/analyze-baccarat', [BacaraAnalyticsController::class, 'analyze'])
    ->middleware('auth:sanctum');

Route::post('/ai-predict', [AiPredictionController::class, 'predict'])
    ->middleware('auth:sanctum');

    Route::get('/virtual-stats', [ChartController::class, 'getVirtualStats'])->middleware('auth:sanctum');