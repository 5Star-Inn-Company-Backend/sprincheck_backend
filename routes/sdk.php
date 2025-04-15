<?php

use App\Http\Controllers\api\sdk\BVNController;
use App\Http\Controllers\api\sdk\NINController;
use App\Http\Middleware\MerchantSignatureCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('sdk')->middleware([\App\Http\Middleware\MerchantApiKey::class, MerchantSignatureCheck::class])->group(function () {
    Route::post('bvn', [BVNController::class, 'check']);
    Route::put('bvn', [BVNController::class, 'sdk_resp'])->withoutMiddleware([MerchantSignatureCheck::class]);

    Route::post('nin', [NINController::class, 'check']);
    Route::put('nin', [NINController::class, 'sdk_resp'])->withoutMiddleware([MerchantSignatureCheck::class]);
});
