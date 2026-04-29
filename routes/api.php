<?php

use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\LlmSettingController;
use App\Http\Controllers\Api\SalesPageController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('campaigns', CampaignController::class);
    Route::post('campaigns/bulk', [CampaignController::class, 'bulkStore']);

    Route::get('analyses', [AnalysisController::class, 'index']);
    Route::post('analyses', [AnalysisController::class, 'store']);
    Route::get('analyses/{analysis}', [AnalysisController::class, 'show']);
    Route::delete('analyses/{analysis}', [AnalysisController::class, 'destroy']);

    Route::get('settings/llm', [LlmSettingController::class, 'show']);
    Route::put('settings/llm', [LlmSettingController::class, 'upsert']);
    Route::post('settings/llm/key', [LlmSettingController::class, 'saveKey']);
    Route::get('settings/llm/models', [LlmSettingController::class, 'models']);

    Route::get('sales-pages', [SalesPageController::class, 'index']);
    Route::post('sales-pages/generate', [SalesPageController::class, 'generate']);
    Route::post('sales-pages/{salesPage}/regenerate', [SalesPageController::class, 'regenerate']);
    Route::get('sales-pages/{salesPage}/export', [SalesPageController::class, 'export']);
    Route::get('sales-pages/{salesPage}', [SalesPageController::class, 'show']);
    Route::put('sales-pages/{salesPage}', [SalesPageController::class, 'update']);
    Route::delete('sales-pages/{salesPage}', [SalesPageController::class, 'destroy']);
});
