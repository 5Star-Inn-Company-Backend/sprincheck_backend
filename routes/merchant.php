<?php

use App\Http\Controllers\api\sdk\BVNController;
use App\Http\Controllers\api\sdk\FacialController;
use App\Http\Controllers\api\sdk\NINController;
use App\Http\Middleware\MerchantSignatureCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->middleware([\App\Http\Middleware\MerchantApiKey::class, MerchantSignatureCheck::class])->group(function () {
    Route::post('bvn', [BVNController::class, 'merchant']);

    Route::post('nin', [NINController::class, 'merchant']);

    Route::post('facial', [FacialController::class, 'check']);
});
