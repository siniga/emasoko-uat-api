<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Api\DashboardChartController;
use App\Http\Controllers\Api\PaymentMandateController as ApiPaymentMandateController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
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


Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('index', [DashboardController::class, 'index'])->name('index');

//registration of seller
Route::get('register/{uuid?}', [RegisterController::class, 'registrationForm'])->name('company.register');
Route::post('store-company-details', [RegisterController::class, 'storeCompanyDetails'])->name('company.store-company');

//company documents
Route::get('company-documents/{uuid}', [RegisterController::class, 'companyDocumentsForm'])->name('company.register-documents');
Route::post('store-company-documents', [RegisterController::class, 'storeCompanyDocuments'])->name('company.register-documents');

//company accounts
Route::get('company-accounts/{uuid}', [RegisterController::class, 'companyAccountForm'])->name('company.register-accounts');
Route::post('store-company-accounts', [RegisterController::class, 'storeCompanyAccounts'])->name('company.register-accounts');

//company staff
Route::get('company-staff/{uuid}', [RegisterController::class, 'companyStaffForm'])->name('company.register-staff');
Route::post('store-company-staff', [RegisterController::class, 'storeCompanyStaff'])->name('company.register-staff');

//company authorized person
Route::get('company-authorized-person/{uuid}', [RegisterController::class, 'companyAuthorizedPersonForm'])->name('company.register-authorized-person');
Route::post('store-company-authorized-person', [RegisterController::class, 'storeCompanyAuthorizedPerson'])->name('company.register-authorized-person');

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

//resources routes
Route::resources([
    'roles' => RoleController::class,
    'users' => UserController::class,
    'companies' => CompanyController::class,
    //'customers' => CustomerController::class
]);

//companies
Route::get('companies/{id}/assessment', [CompanyController::class, 'companyAssessment'])->name('companies.assessment');
Route::post('companies/store-assessment', [CompanyController::class, 'store_assessment'])->name('companies.store-assessment');
Route::post('companies/confirm-assessment', [CompanyController::class, 'confirm_assessment'])->name('companies.confirm-assessment');

//customers
Route::get('payment-mandate/lists', [PaymentMandateController::class, 'lists'])->name('payment-mandate.lists');

//account details
Route::get('payment-mandate/create-account/{uuid?}', [PaymentMandateController::class, 'accountDetailsForm'])->name('payment-mandate.create-account');
Route::post('payment-mandate/store-account', [PaymentMandateController::class, 'storeAccountDetails'])->name('payment-mandate.store-account-details');

//deduction details
Route::get('payment-mandate/deduction-details/{uuid}', [PaymentMandateController::class, 'deductionDetailsForm'])->name('payment-mandate.create-deduction');
Route::post('payment-mandate/store-deduction-details', [PaymentMandateController::class, 'storeDeductionDetails'])->name('payment-mandate.store-deduction-details');

//preview
Route::get('payment-mandate/preview/{uuid}', [PaymentMandateController::class, 'preview'])->name('payment-mandate.preview');
Route::get('payment-mandate/cancel-registration/{uuid}', [PaymentMandateController::class, 'cancelRegistration'])->name('payment-mandate.cancel-registration');
Route::post('payment-mandate/confirm-registration', [PaymentMandateController::class, 'confirmRegistration'])->name('payment-mandate.confirm-registration');

//details
Route::get('payment-mandate/details/{uuid}', [PaymentMandateController::class, 'details'])->name('payment-mandate.details');
Route::get('payment-mandate/edit/{uuid}', [ApiPaymentMandateController::class, 'edit'])->name('payment-mandate.edit');
Route::post('customers/update-details', [CustomerController::class, 'update'])->name('customers.update');

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
Route::get('api/dashboard/consent-percentage', [DashboardChartController::class, 'getPercentageConsent']);
Route::get('api/dashboard/consent-per-channel', [DashboardChartController::class, 'getConsentPerChannel']);
