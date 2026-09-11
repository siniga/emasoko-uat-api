<?php

use App\Http\Controllers\Api\MobileApp\AuthController;
use App\Http\Controllers\Api\MobileApp\DashboardController;
use App\Http\Controllers\Api\MobileApp\LeaderboardController;
use App\Http\Controllers\Api\MobileApp\PaymentCollectionController;
use App\Http\Controllers\Api\MobileApp\PaymentMandateController;
use App\Http\Controllers\Api\MobileApp\UserController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
});

Route::middleware('auth:api')->group(function () {
    Route::get('user/profile', [UserController::class, 'profile']);
    Route::patch('user/profile', [UserController::class, 'updateProfile']);
    Route::post('logout', [UserController::class, 'logout']);
    Route::get('notifications', [UserController::class, 'notifications']);
    Route::patch('notifications/{id}/read', [UserController::class, 'markNotificationAsRead']);
    Route::patch('notifications/read-all', [UserController::class, 'markAllNotificationsAsRead']);
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('leaderboard', [LeaderboardController::class, 'index']);


    Route::get('sp-homepage', [PaymentMandateController::class, 'spHomepage']);
    Route::get('debit-orders-summaries', [PaymentMandateController::class, 'debitOrderSummaries']);
    Route::get('deduction-histories/{uuid}', [PaymentMandateController::class, 'deductionHistories']);
    Route::any('mandates/summaries', [PaymentMandateController::class, 'summaries']);
    Route::get('mandates/all', [PaymentMandateController::class, 'all']);
    Route::get('mandates/channels', [PaymentMandateController::class, 'getPaymentChannels']);
    Route::post('mandates/create', [PaymentMandateController::class, 'create']);
    Route::post('mandates/otp/verify', [PaymentMandateController::class, 'verifyOtp']);
    Route::post('mandates/otp/create', [PaymentMandateController::class, 'createOtp']);
    Route::post('mandates/status', [PaymentMandateController::class, 'fetchStatus']);
    Route::post('mandates/cancellation', [PaymentMandateController::class, 'cancellation']);
    Route::post('mandates/archive', [PaymentMandateController::class, 'archiveMandate']);
    Route::post('mandates/close', [PaymentMandateController::class, 'closeMandate']);
    Route::get('mandates/{reference}', [PaymentMandateController::class, 'getMandate'])
        ->where('reference', '^(?!summaries$|all$|channels$|create$|otp$|status$|cancellation$|archive$|close$).+');

    Route::post('collections/all', [PaymentCollectionController::class, 'all']);
    Route::post('collections/filtering', [PaymentCollectionController::class, 'filtering']);
    Route::post('collections/actual', [PaymentCollectionController::class, 'actualCollections']);
});
