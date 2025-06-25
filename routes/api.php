<?php

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Auth Routes
Route::post('/auth/signup', [AuthController::class, 'signup']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/verify-reset-code', [AuthController::class, 'verifyResetCode']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Dashboard Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'getDashboard']);
    Route::get('/dashboard/stats', [DashboardController::class, 'getDashboardStats']);
    Route::get('/history', [DashboardController::class, 'getHistory']);
    Route::get('/wallet-history', [DashboardController::class, 'getWalletHistory']);
    Route::post('/update-webhook', [DashboardController::class, 'updateWebhookUrl']);
    Route::put('/regenerate-keys', [DashboardController::class, 'regenerateKeys']);
    Route::post('/generate-account', [\App\Http\Controllers\api\VirtualAccountGeneration::class, 'generatePaylony']);
});

require_once 'sdk.php';
