<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BIController;
use App\Http\Controllers\DashboardChartController;
use App\Http\Controllers\Auth\AuthOtpController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FrontPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentChannelController;
use App\Http\Controllers\PaymentMandateActionController;
use App\Http\Controllers\PaymentMandateApprovalController;
use App\Http\Controllers\PaymentMandateCreateController;
use App\Http\Controllers\PaymentMandateListController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SmsLogsController;
use App\Http\Controllers\SmsTemplatesController;
use App\Http\Controllers\SelfCompanyRegistrationController;
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


// //Update User Details
// Route::post('/update-profile/{id}', [App\Http\Controllers\HomeController::class, 'updateProfile'])->name('updateProfile');
// Route::post('/update-password/{id}', [App\Http\Controllers\HomeController::class, 'updatePassword'])->name('updatePassword');

// //Language Translation
// Route::get('index/{locale}', [App\Http\Controllers\HomeController::class, 'lang']);

Route::controller(AuthOtpController::class)->group(function () {
    Route::get('/', 'login')->name('otp.login');
    Route::get('/login', 'login')->name('otp.login');
    Route::get('/send-email', 'sendEmail')->name('otp.send-email');
    Route::post('/otp/generate', 'generate')->name('otp.generate');
    Route::get('/otp/verification/{uuid}', 'verification')->name('otp.verification');
    Route::post('/otp/login', 'loginWithOtp')->name('otp.getlogin');
});
Route::get('login', [AuthOtpController::class, 'login'])->name('login');
Route::get('auth/logout', [LoginController::class, 'logout'])->name('auth.logout');

Route::prefix('service-provider/register')
    ->name('self-registration.')
    ->middleware('throttle:60,1')
    ->controller(SelfCompanyRegistrationController::class)
    ->group(function () {
        Route::get('success', 'success')->name('success');
        Route::get('{uuid?}', 'registrationForm')->name('information');
        Route::post('information', 'storeCompanyDetail')->name('store-detail');
        Route::get('{uuid}/documents', 'companyDocumentsForm')->name('documents');
        Route::post('documents', 'storeCompanyDocuments')->name('store-documents');
        Route::get('{uuid}/representative', 'companyStaffForm')->name('staff');
        Route::post('representative', 'storeCompanyStaff')->name('store-staff');
        Route::get('{uuid}/preview', 'companyPreview')->name('preview');
        Route::post('save', 'savingRegistration')->name('save');
        Route::post('cancel', 'cancelSelfRegistration')->name('cancel');
        Route::post('submit', 'confirmRegistration')->name('submit');
    });

Route::middleware('auth')->group(function () {

    //After Login
    Route::any('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::any('index', [DashboardController::class, 'index'])->name('index');
    Route::any('bi/dashboard', [BIController::class, 'index'])->name('bi.dashboard');

    //charts
    Route::get('charts/mandate-values', [DashboardChartController::class, 'mandate_values']);
    Route::get('charts/mandate-percentages', [DashboardChartController::class, 'mandate_percentages']);
    Route::get('charts/mandate-channel', [DashboardChartController::class, 'mandate_channel']);
    Route::get('charts/collected-mandate', [DashboardChartController::class, 'collected_mandates']);
    Route::get('charts/weekly-collected-mandate', [DashboardChartController::class, 'weekly_collected_mandates']);

    //account types
    Route::get('account-types/{id}', [AccountController::class, 'account_types'])->name('account-types');

    //verify account
    Route::get('verify-account/{token}', [RegisterController::class, 'verify_account'])->name('verify.account.get');

    Route::get('forget-password', [ForgotPasswordController::class, 'showForgetPasswordForm'])->name('forget.password.get');
    Route::post('forget-password', [ForgotPasswordController::class, 'submitForgetPasswordForm'])->name('forget.password.post');
    Route::get('reset-password/{token}', [ForgotPasswordController::class, 'showResetPasswordForm'])->name('reset.password.get');
    Route::post('reset-password', [ForgotPasswordController::class, 'submitResetPasswordForm'])->name('reset.password.post');

    //companies
    Route::prefix('companies')->group(function () {});

    Route::any('companies/lists', [CompanyController::class, 'index'])->name('companies.lists');
    Route::get('companies/show/{uuid}', [CompanyController::class, 'show'])->name('companies.show');
    Route::get('companies/edit/{uuid}', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::post('companies/update/{uuid}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('companies/delete/{uuid}', [CompanyController::class, 'destroy'])->name('companies.delete');
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

    //new collection accounts
    Route::get('companies/collection_account/{uuid}', [CompanyController::class, 'newCollectionAccountForm'])->name('companies.collection-account');
    Route::post('companies/store-collection-account', [CompanyController::class, 'storeCollectionAccount'])->name('companies.store-collection-account');

    //company staff
    Route::get('companies/staff/{uuid}', [CompanyController::class, 'companyStaffForm'])->name('companies.register-staff');
    Route::post('companies/store-staff', [CompanyController::class, 'storeCompanyStaff'])->name('companies.store-staff');

    //company authorized person
    Route::get('companies/authorized-person/{uuid}', [CompanyController::class, 'companyAuthorizedPersonForm'])->name('companies.register-authorized-person');
    Route::post('companies/store-authorized-person', [CompanyController::class, 'storeCompanyAuthorizedPerson'])->name('companies.store-authorized-person');

    //company details overview
    Route::get('companies/preview/{uuid}', [CompanyController::class, 'companyPreview'])->name('companies.preview');
    Route::delete('companies/cancel-registration/{uuid}', [CompanyController::class, 'cancelRegistration'])->name('companies.cancel-registration');
    Route::post('companies/saving-registration', [CompanyController::class, 'savingRegistration'])->name('companies.saving-registration');
    Route::post('companies/confirm-registration', [CompanyController::class, 'confirmRegistration'])->name('companies.confirm-registration');

    //Route::get('companies/assessment/{uuid}', [CompanyController::class, 'companyAssessment'])->name('companies.assessment');
    //Route::get('companies/assessment-stages', [CompanyController::class, 'companyAssessmentStages'])->name('companies.assessment-stages');
    //Route::get('companies/assessment-approval', [CompanyController::class, 'companyAssessmentApproval'])->name('companies.assessment-approval');
    //Route::post('companies/store-assessment', [CompanyController::class, 'storeAssessment'])->name('companies.store-assessment');
    Route::post('companies/confirm-assessment', [CompanyController::class, 'confirmAssessment'])->name('companies.confirm-assessment');
    Route::post('companies/fetch-corporate-status', [CompanyController::class, 'fetchCorporateStatus'])->name('companies.fetch-corporate-status');
    Route::get('companies/create-invoice/{uuid}', [CompanyController::class, 'createInvoice'])->name('companies.create-invoice');

    //customer accounts
    Route::any('customer-accounts/lists', [CustomerAccountController::class, 'lists'])->name('customer-accounts.lists');
    Route::get('customer-accounts/{id}/delete', [CustomerAccountController::class, 'destroy'])->name('customer-accounts.delete');
    Route::get('customer-accounts/export-xls', [CustomerAccountController::class, 'export_xls'])->name('customer-accounts.export-xls');
    Route::get('customer-accounts/export-pdf', [CustomerAccountController::class, 'export_pdf'])->name('customer-accounts.export-pdf');

    //users
    Route::any("users/lists", [UserController::class, 'index'])->name('users.lists');
    Route::get('users/profile', [UserController::class, 'profile'])->name('users.profile');
    Route::put('users/profile', [UserController::class, 'update_profile'])->name('users.update-profile');
    Route::get('users/change-password', [UserController::class, 'change_password'])->name('users.change-password');
    Route::put('users/change-password', [UserController::class, 'update_password'])->name('users.update-password');

    //resources routes
    Route::resources([
        // 'companies' => CompanyController::class,
        'customer-accounts' => CustomerAccountController::class,
        'payment-channels' => PaymentChannelController::class,
        'roles' => RoleController::class,
        'users' => UserController::class,
    ]);

    //payment channel
    Route::get('payment-channels/{id}/delete', [PaymentChannelController::class, 'delete'])->name('payment-channels.delete');

    //payment mandate
    Route::prefix('payment-mandate')->group(function () {});

    //payment mandate
    Route::any('payment-mandate/lists', [PaymentMandateListController::class, 'lists'])->name('payment-mandate.lists');
    Route::get('payment-mandate/export-xls', [PaymentMandateListController::class, 'export_xls'])->name('payment-mandate.export-xls');
    Route::get('payment-mandate/export-pdf', [PaymentMandateListController::class, 'export_pdf'])->name('payment-mandate.export-pdf');

    //customer account 
    Route::get('payment-mandate/create-account/{ca_uuid?}/{do_uuid?}', [PaymentMandateCreateController::class, 'create_account'])->name('payment-mandate.create-account');
    Route::post('payment-mandate/store-account', [PaymentMandateCreateController::class, 'store_account'])->name('payment-mandate.store-account');

    //customer deduction
    Route::get('payment-mandate/create-deduction/{ca_uuid}/{do_uuid?}', [PaymentMandateCreateController::class, 'create_deduction'])->name('payment-mandate.create-deduction');
    Route::post('payment-mandate/store-deduction', [PaymentMandateCreateController::class, 'store_deduction'])->name('payment-mandate.store-deduction');

    //preview 
    Route::get('payment-mandate/preview/{do_uuid}', [PaymentMandateCreateController::class, 'preview'])->name('payment-mandate.preview');
    Route::get('payment-mandate/cancel/{do_uuid}', [PaymentMandateCreateController::class, 'cancel'])->name('payment-mandate.cancel');
    Route::post('payment-mandate/save', [PaymentMandateCreateController::class, 'save'])->name('payment-mandate.save');
    Route::post('payment-mandate/confirm', [PaymentMandateCreateController::class, 'confirm'])->name('payment-mandate.confirm');
    Route::post('payment-mandate/resend-otp', [PaymentMandateActionController::class, 'resend_otp'])->name('payment-mandate.resend-otp');
    Route::post('payment-mandate/verify-otp', [PaymentMandateActionController::class, 'verify_otp'])->name('payment-mandate.verify-otp');

    //details
    Route::get('payment-mandate/show/{do_uuid}', [PaymentMandateActionController::class, 'show'])->name('payment-mandate.show');
    Route::get('payment-mandate/edit/{do_uuid}', [PaymentMandateActionController::class, 'edit'])->name('payment-mandate.edit');
    Route::put('payment-mandate/{id}/update', [PaymentMandateActionController::class, 'update'])->name('payment-mandate.update');
    Route::get('payment-mandate/delete/{uuid}', [PaymentMandateActionController::class, 'destroy'])->name('payment-mandate.delete');
    Route::post('payment-mandate/fetch-status', [PaymentMandateActionController::class, 'fetch_status'])->name('payment-mandate.fetch-status');
    Route::post('payment-mandate/cancel-mandate', [PaymentMandateActionController::class, 'cancel_mandate'])->name('payment-mandate.cancel-mandate');

    Route::get('payment-mandate/unarchieve/{uuid}', [PaymentMandateActionController::class, 'unarchieve'])->name('payment-mandate.unarchieve');
    Route::get('payment-mandate/send-remainder/{uuid}', [PaymentMandateActionController::class, 'sendRemainder'])->name('payment-mandate.send-remainder');
    //Route::get('payment-mandate/update-mandates', [PaymentMandateController::class, 'update_mandates'])->name('payment-mandate.update-mandates');
    //Route::get('payment-mandate/update-installments', [PaymentMandateController::class, 'update_installments'])->name('payment-mandate.update-installments');

    //actions
    Route::any('payment-mandate/request-cancellation', [PaymentMandateApprovalController::class, 'request_cancellation'])->name('payment-mandate.request-cancellation');
    Route::get('payment-mandate/approvals/{tab?}', [PaymentMandateApprovalController::class, 'approvals'])->name('payment-mandate.approvals');
    Route::any('payment-mandate/approve-cancellation', [PaymentMandateApprovalController::class, 'approve_cancellation'])->name('payment-mandate.approve-cancellation');
    Route::any('payment-mandate/request-deletion', [PaymentMandateApprovalController::class, 'request_deletion'])->name('payment-mandate.request-deletion');
    Route::any('payment-mandate/approve-deletion', [PaymentMandateApprovalController::class, 'approve_deletion'])->name('payment-mandate.approve-deletion');
    Route::any('payment-mandate/request-closing', [PaymentMandateApprovalController::class, 'request_closing'])->name('payment-mandate.request-closing');
    Route::any('payment-mandate/approve-closing', [PaymentMandateApprovalController::class, 'approve_closing'])->name('payment-mandate.approve-closing');
    Route::get('payment-mandate/request-archive/{uuid}', [PaymentMandateApprovalController::class, 'request_archive'])->name('payment-mandate.request-archive');
    Route::any('payment-mandate/approve-archive', [PaymentMandateApprovalController::class, 'approve_archive'])->name('payment-mandate.approve-archive');

    //collection batches
    Route::prefix('collections')->group(function () {});

    Route::any('collections/batches', [CollectionController::class, 'batches'])->name('collections.batches');
    Route::any('collections/create', [CollectionController::class, 'create'])->name('collections.create');
    Route::post('collections/store', [CollectionController::class, 'store'])->name('collections.store');
    Route::get('collections/show/{id}', [CollectionController::class, 'show'])->name('collections.show');
    Route::get('collections/export-created/{id}', [CollectionController::class, 'export_created'])->name('collections.export-created');
    Route::get('collections/export-processed/{id}', [CollectionController::class, 'export_processed'])->name('collections.export-processed');
    Route::get('collections/create-failed/{id?}', [CollectionController::class, 'create_failed'])->name('collections.create-failed');
    Route::post('collections/store-failed', [CollectionController::class, 'store_failed'])->name('collections.store-failed');
    Route::get('collections/create-monthly-failed', [CollectionController::class, 'create_monthly_failed'])->name('collections.create-monthly-failed');
    Route::get('collections/archive/{id}', [CollectionController::class, 'archive'])->name('collections.archive');
    Route::post('collections/upload', [CollectionController::class, 'upload'])->name('collections.upload');
    Route::post('collections/fetch-status', [CollectionController::class, 'fetch_status'])->name('collections.fetch-status');

    Route::get('collections/create-debit-orders', [CollectionController::class, 'create_debit_orders'])->name('collections.create-debit-orders');
    Route::get('collections/update-debit-orders', [CollectionController::class, 'update_debit_orders'])->name('collections.update-debit-orders');
    Route::get('collections/update-collection-date', [CollectionController::class, 'update_collection_date'])->name('collections.update-collection-date');
    //Route::get('collections/update-collection-status', [CollectionController::class, 'update_collection_status'])->name('collections.update-collection-status');
    //Route::get('collections/create-collection-values', [CollectionController::class, 'create_collection_values'])->name('collections.create-collection-values');
    //Route::get('collections/update-collection-values', [CollectionController::class, 'update_collection_values'])->name('collections.update-collection-values');
    //Route::get('collections/delete-batch', [CollectionController::class, 'delete_collection_batch'])->name('collections.delete-batch');
    //Route::get('collections/reconciliation', [CollectionController::class, 'reconciliation'])->name('collections.reconciliation');
    Route::get('collections/reconciliation-crdb', [CollectionController::class, 'reconciliation_crdb'])->name('collections.reconciliation-crdb');

    Route::any('collections/expected/monthly', [CollectionController::class, 'expected_monthly'])->name('collections.expected-monthly');
    Route::get('collections/export-expected-monthly-summary', [CollectionController::class, 'export_expected_monthly_summary'])->name('collections.export-expected-monthly-summary');
    Route::get('collections/expected/monthly/{uuid}', [CollectionController::class, 'expected_monthly_detail'])->name('collections.expected-monthly-detail');
    Route::get('collections/export-expected-monthly/{uuid}', [CollectionController::class, 'export_expected_monthly'])->name('collections.export-expected-monthly');
    Route::any('collections/actual/monthly', [CollectionController::class, 'actual_monthly'])->name('collections.actual-monthly');
    Route::get('collections/export-actual-monthly-summary', [CollectionController::class, 'export_actual_monthly_summary'])->name('collections.export-actual-monthly-summary');
    Route::get('collections/actual/monthly/{uuid}', [CollectionController::class, 'actual_monthly_detail'])->name('collections.actual-monthly-detail');
    Route::get('collections/export-actual-monthly/{uuid}', [CollectionController::class, 'export_actual_monthly'])->name('collections.export-actual-monthly');

    Route::any('collections/expected/daily', [CollectionController::class, 'expected_daily'])->name('collections.expected-daily');
    Route::get('collections/export-expected-daily-summary', [CollectionController::class, 'export_expected_daily_summary'])->name('collections.export-expected-daily-summary');
    Route::get('collections/expected/daily/{uuid}', [CollectionController::class, 'expected_daily_detail'])->name('collections.expected-daily-detail');
    Route::get('collections/export-expected-daily/{uuid}', [CollectionController::class, 'export_expected_daily'])->name('collections.export-expected-daily');
    Route::any('collections/actual/daily', [CollectionController::class, 'actual_daily'])->name('collections.actual-daily');
    Route::get('collections/export-actual-daily-summary', [CollectionController::class, 'export_actual_daily_summary'])->name('collections.export-actual-daily-summary');
    Route::get('collections/actual/daily/{uuid}', [CollectionController::class, 'actual_daily_detail'])->name('collections.actual-daily-detail');
    Route::any('collections/export-actual-daily/{uuid}', [CollectionController::class, 'export_actual_daily'])->name('collections.export-actual-daily');

    //messaging
    Route::prefix('messaging')->group(function () {
        //sms templates
        Route::any('sms-templates/lists', [SmsTemplatesController::class, 'index'])->name('sms-templates');
        Route::get('sms-templates/all', [SmsTemplatesController::class, 'get_all'])->name('sms-templates.all');
        Route::get('sms-templates/{id}/get-by', [SmsTemplatesController::class, 'get_by'])->name('sms-templates.get-by');
        Route::get('sms-templates/{id}/delete', [SmsTemplatesController::class, 'delete'])->name('sms-templates.delete');
        Route::resource('sms-templates', SmsTemplatesController::class);

        //sms logs
        Route::any('sms-logs/lists', [SmsLogsController::class, 'index'])->name('sms-logs');

        //quick sms
        Route::get('sms-logs/quick-sms', [SmsLogsController::class, 'quick_sms'])->name('sms-logs.quick-sms');
        Route::post('sms-logs/send-quick-sms', [SmsLogsController::class, 'send_quick_sms'])->name('sms-logs.send-quick-sms');

        //file sms
        Route::get('sms-logs/file-sms', [SmsLogsController::class, 'file_sms'])->name('sms-logs.file-sms');
        Route::post('sms-logs/send-file-sms', [SmsLogsController::class, 'send_file_sms'])->name('sms-logs.send-file-sms');
    });

    //reports
    Route::any('reports/lists', [ReportController::class, 'lists'])->name('reports.lists');

    //tasks
    Route::get('tasks/lists', [TaskController::class, 'lists'])->name('tasks.lists');

    //users
    Route::prefix('users')->group(function () {});

    Route::get('users/{id}/change-status', [UserController::class, 'change_status'])->name('users.change-status');
    Route::put('users/{id}/store-status', [UserController::class, 'store_status'])->name('users.store-status');
    Route::get('users/{id}/delete', [UserController::class, 'delete'])->name('users.delete');
    Route::get('users/view/otp', [UserController::class, 'view_otp'])->name('users.view-otp');

    //roles
    Route::prefix('roles')->group(function () {});

    Route::get('roles/{id}/assign-perms', [RoleController::class, 'assign_perms'])->name('roles.assign-perms');
    Route::post('roles/{id}/store-perms', [RoleController::class, 'store_perms'])->name('roles.store-perms');
});
