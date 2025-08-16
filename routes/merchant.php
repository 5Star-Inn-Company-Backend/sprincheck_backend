<?php

use App\Http\Controllers\api\sdk\BVNController;
use App\Http\Controllers\api\sdk\DriverLicenseController;
use App\Http\Controllers\api\sdk\FaceRecognitionController;
use App\Http\Controllers\api\sdk\FacialController;
use App\Http\Controllers\api\sdk\NINController;
use App\Http\Controllers\api\sdk\PassportController;
use App\Http\Controllers\api\sdk\VotersController;
use App\Http\Middleware\MerchantSignatureCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->middleware([\App\Http\Middleware\MerchantApiKey::class, MerchantSignatureCheck::class])->group(function () {

    Route::post('bvn', [BVNController::class, 'merchant']);

    Route::post('nin', [NINController::class, 'merchant']);

    Route::post('passport', [PassportController::class, 'merchant']);

    Route::post('drivers-license', [DriverLicenseController::class, 'merchant']);

    Route::post('voters', [VotersController::class, 'merchant']);

    Route::post('facial', [FacialController::class, 'check']);

    Route::post('face_detection', [FaceRecognitionController::class, 'merchant'])->withoutMiddleware([MerchantSignatureCheck::class]);
    Route::post('face_compare', [FaceRecognitionController::class, 'merchant_compare'])->withoutMiddleware([MerchantSignatureCheck::class]);
    Route::post('face_liveness', [FaceRecognitionController::class, 'merchant_liveness'])->withoutMiddleware([MerchantSignatureCheck::class]);
});
