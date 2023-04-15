<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Api\DashboardChartController;
use App\Http\Controllers\Api\PaymentMandateController as ApiPaymentMandateController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FrontPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentChannelController;
use App\Http\Controllers\PaymentMandateController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Auth::routes();
// Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');

// Route::get('/', [App\Http\Controllers\HomeController::class, 'root'])->name('root');

// //Update User Details
// Route::post('/update-profile/{id}', [App\Http\Controllers\HomeController::class, 'updateProfile'])->name('updateProfile');
// Route::post('/update-password/{id}', [App\Http\Controllers\HomeController::class, 'updatePassword'])->name('updatePassword');

// //Language Translation
// Route::get('index/{locale}', [App\Http\Controllers\HomeController::class, 'lang']);



Route::get('/', [FrontPageController::class, 'index']);
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('index', [DashboardController::class, 'index'])->name('index');

//registration of seller
Route::get('register/{uuid?}', [RegisterController::class, 'registrationForm'])->name('company.register');
Route::post('store-company-details', [RegisterController::class, 'storeCompanyDetails'])->name('company.store-company');

//company documents
Route::get('company-documents/{uuid}', [RegisterController::class, 'companyDocumentsForm'])->name('company.register-documents');
Route::post('store-company-documents', [RegisterController::class, 'storeCompanyDocuments'])->name('company.store-documents');

//company accounts
Route::get('company-accounts/{uuid}', [RegisterController::class, 'companyAccountForm'])->name('company.register-accounts');
Route::post('store-company-accounts', [RegisterController::class, 'storeCompanyAccounts'])->name('company.store-accounts');

//company staff
Route::get('company-staff/{uuid}', [RegisterController::class, 'companyStaffForm'])->name('company.register-staff');
Route::post('store-company-staff', [RegisterController::class, 'storeCompanyStaff'])->name('company.store-staff');

//company authorized person
Route::get('company-authorized-person/{uuid}', [RegisterController::class, 'companyAuthorizedPersonForm'])->name('company.register-authorized-person');
Route::post('store-company-authorized-person', [RegisterController::class, 'storeCompanyAuthorizedPerson'])->name('company.store-authorized-person');

//company details overview
Route::get('company-info-overview/{uuid}', [RegisterController::class, 'companyInfoOverview'])->name('company.info-overview');
Route::get('cancel-company-registration/{uuid}', [RegisterController::class, 'cancelCompanyRegistration'])->name('company.cancel-registration');
Route::post('confirm-company-registration', [RegisterController::class, 'confirmCompanyRegistration'])->name('company.confirm-registration');

//track company registration
Route::get('track-company-registration', [RegisterController::class, 'trackCompanyRegistrationForm'])->name('company.track-registration');
Route::post('track-company-registration', [RegisterController::class, 'trackCompanyRegistration'])->name('company.track-registration');

//verify company registration
Route::get('verify-company-registration/{uuid}', [RegisterController::class, 'verifyCompanyRegistrationForm'])->name('company.verify-registration');
Route::post('verify-company-registration/{uuid}', [RegisterController::class, 'verifyCompanyRegistration'])->name('company.verify-registration');

//success verification
Route::get('verify-company-registration-success', [RegisterController::class, 'verifyCompanyRegistrationSuccess'])->name('company.verify-registration-success');

//account types
Route::get('account-types/{id}', [AccountController::class, 'account_types'])->name('account-types');

//verify account
Route::get('verify-account/{token}', [RegisterController::class, 'verify_account'])->name('verify.account.get');

//login
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login'])->name('login');
Route::get('logout', [LoginController::class, 'logout'])->name('logout');

Route::get('forget-password', [ForgotPasswordController::class, 'showForgetPasswordForm'])->name('forget.password.get');
Route::post('forget-password', [ForgotPasswordController::class, 'submitForgetPasswordForm'])->name('forget.password.post');
Route::get('reset-password/{token}', [ForgotPasswordController::class, 'showResetPasswordForm'])->name('reset.password.get');
Route::post('reset-password', [ForgotPasswordController::class, 'submitResetPasswordForm'])->name('reset.password.post');

//companies
Route::any('companies/lists', [CompanyController::class, 'index'])->name('companies.lists');
Route::get('companies/{id}/delete', [CompanyController::class, 'destroy'])->name('companies.delete');
Route::get('companies/export-xls', [CompanyController::class, 'export_xls'])->name('companies.export-xls');
Route::get('companies/export-pdf', [CompanyController::class, 'export_pdf'])->name('companies.export-pdf');

//customer accounts
Route::any('customer-accounts/lists', [CustomerAccountController::class, 'lists'])->name('customer-accounts.lists');
Route::get('customer-accounts/{id}/delete', [CustomerAccountController::class, 'destroy'])->name('customer-accounts.delete');
Route::get('customer-accounts/export-xls', [CustomerAccountController::class, 'export_xls'])->name('customer-accounts.export-xls');
Route::get('customer-accounts/export-pdf', [CustomerAccountController::class, 'export_pdf'])->name('customer-accounts.export-pdf');

//resources routes
Route::resources([
    'payment-channels' => PaymentChannelController::class,
    'roles' => RoleController::class,
    'users' => UserController::class,
    'companies' => CompanyController::class,
    'customer-accounts' => CustomerAccountController::class,
]);

//payment channel
Route::get('payment-channels/{id}/delete', [PaymentChannelController::class, 'delete'])->name('payment-channels.delete');

//companies
Route::get('companies/{id}/assessment', [CompanyController::class, 'companyAssessment'])->name('companies.assessment');
Route::post('companies/store-assessment', [CompanyController::class, 'store_assessment'])->name('companies.store-assessment');
Route::post('companies/confirm-assessment', [CompanyController::class, 'confirm_assessment'])->name('companies.confirm-assessment');


//payment mandate
Route::any('payment-mandate/lists/{consent?}', [PaymentMandateController::class, 'lists'])->name('payment-mandate.lists');
Route::get('payment-mandate/export-xls', [PaymentMandateController::class, 'export_xls'])->name('payment-mandate.export-xls');
Route::get('payment-mandate/export-pdf', [PaymentMandateController::class, 'export_pdf'])->name('payment-mandate.export-pdf');

//account info
Route::get('payment-mandate/create-account/{id?}/{do_id?}', [PaymentMandateController::class, 'create_account'])->name('payment-mandate.create-account');
Route::post('payment-mandate/store-account', [PaymentMandateController::class, 'store_account'])->name('payment-mandate.store-account');

//deduction info
Route::get('payment-mandate/create-deduction-info/{id}/{do_id?}', [PaymentMandateController::class, 'create_deduction_info'])->name('payment-mandate.create-deduction-info');
Route::post('payment-mandate/store-deduction-info', [PaymentMandateController::class, 'store_deduction_info'])->name('payment-mandate.store-deduction-info');

//preview
Route::get('payment-mandate/preview/{id}/{do_id}', [PaymentMandateController::class, 'preview'])->name('payment-mandate.preview');
Route::get('payment-mandate/cancel/{id}/{do_id}', [PaymentMandateController::class, 'cancel'])->name('payment-mandate.cancel');
Route::post('payment-mandate/confirm', [PaymentMandateController::class, 'confirm'])->name('payment-mandate.confirm');

//details
Route::get('payment-mandate/{id}/show', [PaymentMandateController::class, 'show'])->name('payment-mandate.show');
Route::get('payment-mandate/{id}/edit', [PaymentMandateController::class, 'edit'])->name('payment-mandate.edit');
Route::put('payment-mandate/{id}/update', [PaymentMandateController::class, 'update'])->name('payment-mandate.update');
Route::post('payment-mandate/{id}/delete', [PaymentMandateController::class, 'destroy'])->name('payment-mandate.delete');

Route::get('payment-mandate/send-remainder/{id}', [PaymentMandateController::class, 'sendRemainder'])->name('payment-mandate.send-remainder');

//tasks
Route::get('tasks/lists', [TaskController::class, 'lists'])->name('tasks.lists');

//users
Route::get('users/{id}/change-status', [UserController::class, 'change_status'])->name('users.change-status');
Route::put('users/{id}/store-status', [UserController::class, 'store_status'])->name('users.store-status');
Route::get('users/{id}/delete', [UserController::class, 'delete'])->name('users.delete');

//roles
Route::get('roles/{id}/assign-perms', [RoleController::class, 'assign_perms'])->name('roles.assign-perms');
Route::post('roles/{id}/store-perms', [RoleController::class, 'store_perms'])->name('roles.store-perms');


//charts
Route::get('api/dashboard/consent-value', [DashboardChartController::class, 'getConsentValue']);
Route::get('api/dashboard/consent-percentage', [DashboardChartController::class, 'getPercentageConsent']);
Route::get('api/dashboard/consent-per-channel', [DashboardChartController::class, 'getConsentPerChannel']);
