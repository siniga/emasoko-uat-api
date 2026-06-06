<?php

use App\Http\Controllers\Api\MobileApp\AuthController;
use App\Http\Controllers\Api\MobileApp\PaymentCollectionController;
use App\Http\Controllers\Api\MobileApp\PaymentMandateController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
});

Route::middleware('auth:api')->group(function () {
    Route::get('mandates', [PaymentMandateController::class, 'all']);
    Route::get('mandates/{reference}', [PaymentMandateController::class, 'getMandate']);
    Route::post('mandates', [PaymentMandateController::class, 'create']);
    Route::post('mandates/otp', [PaymentMandateController::class, 'createOtp']);
    Route::post('mandates/otp/verify', [PaymentMandateController::class, 'verifyOtp']);
    Route::post('mandates/status', [PaymentMandateController::class, 'fetchStatus']);
    Route::post('mandates/cancellation', [PaymentMandateController::class, 'cancellation']);
    Route::post('mandates/summaries', [PaymentMandateController::class, 'summaries']);

    Route::post('collections', [PaymentCollectionController::class, 'all']);
    Route::post('collections/filtering', [PaymentCollectionController::class, 'filtering']);
    Route::post('collections/actual', [PaymentCollectionController::class, 'actual_collections']);
});
