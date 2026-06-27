<?php

use App\Http\Controllers\Api\Airtel\CollectionController as AirtelCollectionController;
use App\Http\Controllers\Api\Airtel\MandateController as AirtelMandateController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\Crdb\DisbursementController;
use App\Http\Controllers\Api\NMB\CorporateController;
use App\Http\Controllers\Api\CRDB\MandateController as CRDBMandateController;
use App\Http\Controllers\Api\NMB\MandateController;
use App\Http\Controllers\Api\NMB\OtpController;
use App\Http\Controllers\Api\Yas\MandateController as YasMandateController;
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

//Airtel APIs
Route::post('mandate/airtel', [AirtelMandateController::class, 'create']);
Route::post('mandate/airtel/status', [AirtelMandateController::class, 'query_status']);
Route::post('mandate/airtel/cancel', [AirtelMandateController::class, 'cancel']);
Route::post('mandate/airtel/callback', [AirtelMandateController::class, 'callback'])->middleware('verify.callback:airtel');

Route::post('collection/airtel', [AirtelCollectionController::class, 'create']);
Route::post('collection/airtel/inquiry', [AirtelCollectionController::class, 'inquiry']);
Route::post('collection/airtel/refund-inquiry', [AirtelCollectionController::class, 'refund_inquiry']);
Route::post('collection/airtel/recovery', [AirtelCollectionController::class, 'recovery']);
Route::post('collection/airtel/recovery-inquiry', [AirtelCollectionController::class, 'recovery_inquiry']);
Route::post('collection/airtel/recovery-refund', [AirtelCollectionController::class, 'recovery_refund']);

//CRDB APIs
Route::post('v1/mandate/crdb', [CRDBMandateController::class, 'create']);
Route::post('v1/mandate/crdb/cancel', [CRDBMandateController::class, 'cancel']);
Route::post('v1/mandate/crdb/callback', [CRDBMandateController::class, 'callback']);
Route::post('v1/mandate/crdb/request', [CRDBMandateController::class, 'request']);
Route::post('v1/mandate/crdb/response', [CRDBMandateController::class, 'response']);

Route::prefix('v1/disbursements')->group(function () {
    Route::post('/crdb/batch', [DisbursementController::class, 'postBatch']);
    Route::post('/crdb/callback', [DisbursementController::class, 'callback'])->middleware('verify.callback:crdb');
});

//YAS APIs
Route::post('v1/mandate/yas', [YasMandateController::class, 'create']);
Route::post('v1/mandate/yas/cancel', [YasMandateController::class, 'cancel']);
Route::post('v1/mandate/yas/callback', [YasMandateController::class, 'callback'])->middleware('verify.callback:yas');

//api v1 for mobile and external apps
Route::prefix('v1')->group(base_path('routes/api_external.php'));
Route::prefix('v1')->group(base_path('routes/api_mobile.php'));
