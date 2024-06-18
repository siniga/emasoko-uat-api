<?php

use App\Http\Controllers\Api\v1\CollectionController;
use App\Http\Controllers\Api\v1\CorporateController;
use App\Http\Controllers\Api\v1\MandateController;
use App\Http\Controllers\Api\v1\OtpController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

//Corporate APIs
Route::post('v1/corporate/registration', [CorporateController::class, 'registration']);
Route::post('v1/corporate/status', [CorporateController::class, 'status']);

//Mandate APIs
Route::post('v1/mandate/create', [MandateController::class, 'create']);
Route::post('v1/mandate/status', [MandateController::class, 'status']);
Route::post('v1/mandate/cancellation', [MandateController::class, 'cancellation']);

//OTP APIs
Route::post('v1/otp/create', [OtpController::class, 'create']);
Route::post('v1/otp/verify', [OtpController::class, 'verify']);

//Collections APIs
Route::post('v1/collection/create', [CollectionController::class, 'create']);
Route::post('v1/collection/status', [CollectionController::class, 'status']);
