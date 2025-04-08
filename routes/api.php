<?php

use App\Http\Controllers\Api\Airtel\CollectionController as AirtelCollectionController;
use App\Http\Controllers\Api\Airtel\MandateController as AirtelMandateController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\PaymentMandateController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CorporateController;
use App\Http\Controllers\Api\CRDB\MandateController as CRDBMandateController;
use App\Http\Controllers\Api\ExternalApp\AuthController;
use App\Http\Controllers\Api\ExternalApp\MandateRegistrationController;
use App\Http\Controllers\Api\ExternalApp\MobileAppController;
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
Route::post('collection/create', [CollectionController::class, 'create']);
Route::post('collection/status', [CollectionController::class, 'status']);

//CRDB apis
Route::post('mandate/crdb', [CRDBMandateController::class, 'create']);
Route::post('mandate/crdb/callback', [CRDBMandateController::class, 'callback']);

//Airtel apis
Route::post('mandate/airtel', [AirtelMandateController::class, 'create']);
Route::post('mandate/airtel/status', [AirtelMandateController::class, 'query_status']);
Route::post('mandate/airtel/cancel', [AirtelMandateController::class, 'cancel']);
Route::post('mandate/airtel/callback', [AirtelMandateController::class, 'callback']);

Route::post('collection/airtel', [AirtelCollectionController::class, 'create']);
Route::post('collection/airtel/inquiry', [AirtelCollectionController::class, 'inquiry']);
Route::post('collection/airtel/refund-inquiry', [AirtelCollectionController::class, 'refund_inquiry']);
Route::post('collection/airtel/recovery', [AirtelCollectionController::class, 'recovery']);
Route::post('collection/airtel/recovery-inquiry', [AirtelCollectionController::class, 'recovery_inquiry']);
Route::post('collection/airtel/recovery-refund', [AirtelCollectionController::class, 'recovery_refund']);


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::controller(AuthController::class)->group(function () {
    Route::post('v1/login', 'login');
});

Route::middleware('auth:api')->group(function () {
    Route::get('v1/sp-homepage', [MobileAppController::class, 'summaries']);
    Route::get('v1/debit-orders', [MobileAppController::class, 'debit_orders']);
    Route::get('v1/debit-orders-summaries', [MobileAppController::class, 'debit_orders_summaries']);
    Route::get('v1/deduction-histories/{uuid}', [MobileAppController::class, 'deduction_histories']);
    Route::get('v1/actual-collections', [MobileAppController::class, 'actual_collections']);

    Route::post('v1/filter-collection-batches', [CollectionController::class, 'filter_collection_batches']);
    Route::post('v1/get-deduction-history', [CollectionController::class, 'get_deduction_history']);

    //debit orders
    Route::post('v1/filter-mandates', [MandateRegistrationController::class, 'filter_mandates']);
    Route::post('v1/create-mandate', [MandateRegistrationController::class, 'create']);
    Route::post('v1/verify-otp', [MandateRegistrationController::class, 'verifyOtp']);
    Route::post('v1/send-otp-code', [MandateRegistrationController::class, 'createOtp']);
    Route::post('v1/get-mandate-by-id', [MandateRegistrationController::class, 'get_mandate_by_id']);
    Route::post('v1/archive-mandate', [MandateRegistrationController::class, 'archive_mandate']);
    Route::post('v1/cancel-mandate', [MandateRegistrationController::class, 'cancel_mandate']);
    Route::post('v1/delete-mandate', [MandateRegistrationController::class, 'delete_mandate']);
});
