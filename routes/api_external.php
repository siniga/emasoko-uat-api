<?php

use App\Http\Controllers\Api\ExternalApp\AuthController;
use App\Http\Controllers\Api\ExternalApp\PaymentCollectionController;
use App\Http\Controllers\Api\ExternalApp\PaymentMandateController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('token', 'token');
});

Route::middleware('auth:api')->group(function () {
    //Route::post('mandate/summaries', [PaymentMandateController::class, 'summaries']);
    Route::get('mandate/all', [PaymentMandateController::class, 'all']);
    Route::get('mandate/{reference}', [PaymentMandateController::class, 'getMandate'])
        ->where('reference', '^(?!channels$).+');
    Route::get('mandate/channels', [PaymentMandateController::class, 'getPaymentChannels']);
    Route::post('mandate/create', [PaymentMandateController::class, 'create']);
    Route::post('mandate/otp/verify', [PaymentMandateController::class, 'verifyOtp']);
    Route::post('mandate/otp/create', [PaymentMandateController::class, 'createOtp']);
    Route::post('mandate/status', [PaymentMandateController::class, 'fetchStatus']);
    Route::post('mandate/cancellation', [PaymentMandateController::class, 'cancellation']);

    Route::post('collections/all', [PaymentCollectionController::class, 'all']);
    Route::post('collections/filtering', [PaymentCollectionController::class, 'filtering']);
    Route::post('collections/actual', [PaymentCollectionController::class, 'actualCollections']);
});
