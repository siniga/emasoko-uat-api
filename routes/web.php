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
//Route::get('/', [App\Http\Controllers\HomeController::class, 'root'])->name('root');

// //Update User Details
// Route::post('/update-profile/{id}', [App\Http\Controllers\HomeController::class, 'updateProfile'])->name('updateProfile');
// Route::post('/update-password/{id}', [App\Http\Controllers\HomeController::class, 'updatePassword'])->name('updatePassword');

// //Language Translation
// Route::get('index/{locale}', [App\Http\Controllers\HomeController::class, 'lang']);


Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::get('auth/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('index', [DashboardController::class, 'index'])->name('index');

// //track company registration
// Route::get('track-company-registration', [RegisterController::class, 'trackCompanyRegistrationForm'])->name('company.track-registration');
// Route::post('track-company-registration', [RegisterController::class, 'trackCompanyRegistration'])->name('company.track-registration');

// //verify company registration
// Route::get('verify-company-registration/{uuid}', [RegisterController::class, 'verifyCompanyRegistrationForm'])->name('company.verify-registration');
// Route::post('verify-company-registration/{uuid}', [RegisterController::class, 'verifyCompanyRegistration'])->name('company.verify-registration');

// //success verification
// Route::get('verify-company-registration-success', [RegisterController::class, 'verifyCompanyRegistrationSuccess'])->name('company.verify-registration-success');


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
Route::get('companies/details/{uuid}', [CompanyController::class, 'show'])->name('companies.details');
Route::get('companies/edit/{uuid}', [CompanyController::class, 'edit'])->name('companies.edit');
Route::post('companies/update', [CompanyController::class, 'update'])->name('companies.update');
Route::get('companies/delete/{uuid}', [CompanyController::class, 'destroy'])->name('companies.delete');
Route::get('companies/export-xls', [CompanyController::class, 'exportXLS'])->name('companies.export-xls');
Route::get('companies/export-pdf', [CompanyController::class, 'exportPDF'])->name('companies.export-pdf');

//initi registration
Route::get('companies/register/{uuid?}', [CompanyController::class, 'registrationForm'])->name('companies.registration');
Route::post('companies/store-detail', [CompanyController::class, 'storeCompanyDetail'])->name('companies.store-detail');

//company documents
Route::get('companies/documents/{uuid}', [CompanyController::class, 'companyDocumentsForm'])->name('companies.register-documents');
Route::post('companies/store-documents', [CompanyController::class, 'storeCompanyDocuments'])->name('companies.store-documents');

//company accounts
Route::get('companies/accounts/{uuid}', [CompanyController::class, 'companyAccountForm'])->name('companies.register-accounts');
Route::post('companies/store-accounts', [CompanyController::class, 'storeCompanyAccounts'])->name('companies.store-accounts');

//company staff
Route::get('companies/staff/{uuid}', [CompanyController::class, 'companyStaffForm'])->name('companies.register-staff');
Route::post('companies/store-staff', [CompanyController::class, 'storeCompanyStaff'])->name('companies.store-staff');

//company authorized person
Route::get('companies/authorized-person/{uuid}', [CompanyController::class, 'companyAuthorizedPersonForm'])->name('companies.register-authorized-person');
Route::post('companies/store-authorized-person', [CompanyController::class, 'storeCompanyAuthorizedPerson'])->name('companies.store-authorized-person');

//company details overview
Route::get('companies/preview/{uuid}', [CompanyController::class, 'companyPreview'])->name('companies.preview');
Route::get('companies/cancel-registration/{uuid}', [CompanyController::class, 'cancelRegistration'])->name('companies.cancel-registration');
Route::post('companies/saving-registration', [CompanyController::class, 'savingRegistration'])->name('companies.saving-registration');
Route::post('companies/confirm-registration', [CompanyController::class, 'confirmRegistration'])->name('companies.confirm-registration');

Route::get('companies/assessment/{uuid}', [CompanyController::class, 'companyAssessment'])->name('companies.assessment');
Route::get('companies/assessment-stages', [CompanyController::class, 'companyAssessmentStages'])->name('companies.assessment-stages');
Route::get('companies/assessment-approval', [CompanyController::class, 'companyAssessmentApproval'])->name('companies.assessment-approval');
Route::post('companies/store-assessment', [CompanyController::class, 'storeAssessment'])->name('companies.store-assessment');
Route::post('companies/confirm-assessment', [CompanyController::class, 'confirmAssessment'])->name('companies.confirm-assessment');


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
    // 'companies' => CompanyController::class,
    'customer-accounts' => CustomerAccountController::class,
]);

//payment channel
Route::get('payment-channels/{id}/delete', [PaymentChannelController::class, 'delete'])->name('payment-channels.delete');

//payment mandate
Route::any('payment-mandate/lists/{consent?}', [PaymentMandateController::class, 'lists'])->name('payment-mandate.lists');
Route::get('payment-mandate/export-xls', [PaymentMandateController::class, 'export_xls'])->name('payment-mandate.export-xls');
Route::get('payment-mandate/export-pdf', [PaymentMandateController::class, 'export_pdf'])->name('payment-mandate.export-pdf');

//account info
Route::get('payment-mandate/create-account/{ca_uuid?}/{do_uuid?}', [PaymentMandateController::class, 'create_account'])->name('payment-mandate.create-account');
Route::post('payment-mandate/store-account', [PaymentMandateController::class, 'store_account'])->name('payment-mandate.store-account');

//deduction info
Route::get('payment-mandate/create-deduction/{ca_uuid}/{do_uuid?}', [PaymentMandateController::class, 'create_deduction'])->name('payment-mandate.create-deduction');
Route::post('payment-mandate/store-deduction', [PaymentMandateController::class, 'store_deduction'])->name('payment-mandate.store-deduction');

//preview 
Route::get('payment-mandate/preview/{do_uuid}', [PaymentMandateController::class, 'preview'])->name('payment-mandate.preview');
Route::get('payment-mandate/cancel/{do_uuid}', [PaymentMandateController::class, 'cancel'])->name('payment-mandate.cancel');
Route::post('payment-mandate/confirm', [PaymentMandateController::class, 'confirm'])->name('payment-mandate.confirm');
Route::post('payment-mandate/saving', [PaymentMandateController::class, 'saving'])->name('payment-mandate.saving');
Route::post('payment-mandate/verify/otp', [PaymentMandateController::class, 'verify_otp'])->name('payment-mandate.verify_otp');

//details
Route::get('payment-mandate/show/{do_uuid}', [PaymentMandateController::class, 'show'])->name('payment-mandate.show');
Route::get('payment-mandate/edit/{do_uuid}', [PaymentMandateController::class, 'edit'])->name('payment-mandate.edit');
Route::put('payment-mandate/{id}/update', [PaymentMandateController::class, 'update'])->name('payment-mandate.update');
Route::get('payment-mandate/delete/{do_uuid}', [PaymentMandateController::class, 'destroy'])->name('payment-mandate.delete');

Route::get('payment-mandate/send-remainder/{do_uuid}', [PaymentMandateController::class, 'sendRemainder'])->name('payment-mandate.send-remainder');

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
Route::get('api/dashboard/mandate-values', [DashboardChartController::class, 'mandate_values']);
Route::get('api/dashboard/mandate-percentages', [DashboardChartController::class, 'mandate_percentages']);
Route::get('api/dashboard/consent-per-channel', [DashboardChartController::class, 'getConsentPerChannel']);
