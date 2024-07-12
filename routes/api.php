<?php

use App\Http\Controllers\Api\PaymentMandateController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CorporateController;
use App\Http\Controllers\Api\MandateController;
use App\Http\Controllers\Api\OtpController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

//contacts 
Route::post('contacts', [CompanyController::class, 'contacts']);

//setup
Route::post('categories', [CompanyController::class, 'categories']);
Route::get('document_categories', [CompanyController::class, 'document_categories']);
Route::get('countries', [CompanyController::class, 'countries']);
Route::get('regions', [CompanyController::class, 'regions']);

// //company
// //Route::post('companies/information', [CompanyController::class, 'storeCompanyDetails']);


//external API
Route::post('customer', [PaymentMandateController::class, 'customer']);
Route::post('customerInfo', [PaymentMandateController::class, 'customerInfo']);
Route::post('customerUpdateTerms', [PaymentMandateController::class, 'updateCustomerTerms']);
Route::post('customerPayment', [PaymentMandateController::class, 'payment']);

//Corporate APIs
Route::post('corporate/registration', [CorporateController::class, 'registration']);
Route::post('corporate/status', [CorporateController::class, 'status']);

//Mandate APIs
Route::post('mandate/create', [MandateController::class, 'create']);
Route::post('mandate/status', [MandateController::class, 'status']);
Route::post('mandate/cancellation', [MandateController::class, 'cancellation']);

//OTP APIs
Route::post('otp/create', [OtpController::class, 'create']);
Route::post('otp/verify', [OtpController::class, 'verify']);

//Collections APIs
// Route::post('collection/create', [CollectionController::class, 'create']);
// Route::post('collection/status', [CollectionController::class, 'status']);


