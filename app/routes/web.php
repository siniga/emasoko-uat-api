<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Backend\MediaBuyerController;
use App\Http\Controllers\Backend\MediaInquiryController;
use App\Http\Controllers\Backend\MediaSellerController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\Backend\RoleController;
// use App\Http\Controllers\Backend\Services\ProgramController;
use App\Http\Controllers\Backend\SetUp\MediaTypeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Media\AccountingController;
use App\Http\Controllers\Media\CampaignController;
use App\Http\Controllers\Media\DashboardController as MediaDashboardController;
use App\Http\Controllers\Media\MediaBookingController;
use App\Http\Controllers\Media\PRCoverageController;
use App\Http\Controllers\Media\ProfileController;
use App\Http\Controllers\Media\ProgramController;
use App\Http\Controllers\Media\RateCardController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\RegionController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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

Route::get('/', [HomeController::class, 'index']);
Route::get('home', [HomeController::class, 'index'])->name('home');

//registration of seller
Route::get('seller-registration', [RegisterController::class, 'sellerRegistrationForm'])->name('seller-registration');
Route::post('seller-registration', [RegisterController::class, 'authenticateSeller'])->name('seller-registration');

//registration of influencer
Route::get('influencer-registration', [RegisterController::class, 'influencerRegistrationForm'])->name('influncers.registration');
Route::post('influencer-registration', [RegisterController::class, 'authenticateInfluencer'])->name('influencers.registration');

//registration of buyer
Route::get('buyer-registration', [RegisterController::class, 'buyerRegistrationForm'])->name('buyer-registration');
Route::post('buyer-registration', [RegisterController::class, 'authenticateBuyer'])->name('buyer-registration');

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


//media
Route::get('media-type/{alias}', [MediaController::class, 'media_type'])->name('media-type');
Route::get('media', [MediaController::class, 'index'])->name('media.index');
//Route::get('media/{alias}/{m_alias}', [MediaController::class, 'show'])->name('media.show');

//cart
Route::get('cart/{id}/add', [CartController::class, 'add'])->name('cart.add');
Route::any('cart', [CartController::class, 'index'])->name('cart.index');
Route::get('cart/{id}/{quantity}/update', [CartController::class, 'update']);
Route::get('cart/{id}/delete', [CartController::class, 'delete']);

//book spots
Route::get('book-media-spots', [MediaController::class, 'book_media_spots'])->name('media.book-spots');

//api
Route::get('regions/districts/{id}', [RegionController::class, 'districts']);

/*===============================================================================
Media
===============================================================================*/
//resources routes
Route::resources([
    'bookings' => MediaBookingController::class,
    'pr-coverage' => PRCoverageController::class,
    'accounting' => AccountingController::class,
    'campaigns' => CampaignController::class,
    
]);

Route::get('media-dashboard', [MediaDashboardController::class, 'index'])->name('media.dashboard');

//profile
Route::get('profile', [ProfileController::class, 'index'])->name('media.profile.index');
Route::get('profile/change-password', [ProfileController::class, 'change_password'])->name('media.profile.change-password');
Route::post('profile/change-password', [ProfileController::class, 'store_password'])->name('media.profile.change-password');

Route::get('profile/update', [ProfileController::class, 'edit_seller'])->name('media.profile.edit-seller');
Route::post('profile/update', [ProfileController::class, 'update_seller'])->name('media.profile.update-seller');

//programs
Route::get('programs/lists', [ProgramController::class, 'lists'])->name('media.programs.lists');
Route::get('programs/create', [ProgramController::class, 'create'])->name('media.programs.create');
Route::post('programs/store', [ProgramController::class, 'store'])->name('media.programs.store');
Route::get('programs/{id}/show', [ProgramController::class, 'show'])->name('media.programs.show');
Route::get('programs/{id}/edit', [ProgramController::class, 'edit'])->name('media.programs.edit');
Route::put('programs/{id}/update', [ProgramController::class, 'update'])->name('media.programs.update');

//newspaper
Route::get('newspapers/lists', [ProgramController::class, 'newspapers'])->name('media.newspapers.lists');
Route::get('newspapers/create', [ProgramController::class, 'create_newspaper'])->name('media.newspapers.create');
Route::post('newspapers/store', [ProgramController::class, 'store_newspaper'])->name('media.newspapers.store');
Route::get('newspapers/{id}/show', [ProgramController::class, 'show_newspaper'])->name('media.newspapers.show');
Route::get('newspapers/{id}/edit', [ProgramController::class, 'edit_newspaper'])->name('media.newspapers.edit');
Route::put('newspapers/{id}/update', [ProgramController::class, 'update_newspaper'])->name('media.newspapers.update');

//events
Route::get('events/lists', [ProgramController::class, 'events'])->name('media.events.lists');
Route::get('events/create', [ProgramController::class, 'create_event'])->name('media.events.create');
Route::post('events/store', [ProgramController::class, 'store_event'])->name('media.events.store');
Route::get('events/{id}/show', [ProgramController::class, 'show_event'])->name('media.events.show');
Route::get('events/{id}/edit', [ProgramController::class, 'edit_event'])->name('media.events.edit');
Route::put('events/{id}/update', [ProgramController::class, 'update_event'])->name('media.events.update');

//rate cards => radio and tv
Route::any('programs/{id}/create-rate-card', [RateCardController::class, 'create'])->name('media.programs.create-rate-card');
Route::get('programs/{id}/import-rate-card', [RateCardController::class, 'import'])->name('media.programs.import-rate-card');
Route::post('programs/{id}/store-import-rate-card', [RateCardController::class, 'store_import'])->name('media.programs.store-import-rate-card');
Route::get('programs/export-rate-card', [RateCardController::class, 'exportXLS'])->name('media.programs.export-rate-card');

//rate cards => newspapers
Route::any('newspapers/{id}/create-rate-card', [RateCardController::class, 'create_newspaper'])->name('media.newspapers.create-rate-card');



//Route::get('programs/events', [MediaProgramController::class, 'events'])->name('media.programs.events');
///Route::get('programs/register-event', [MediaProgramController::class, 'create_event'])->name('media.programs.register-event');






Route::get('media/programs/influencer', [MediaProgramController::class, 'influencer'])->name('media.programs.influncer');

/*===============================================================================
Admin Panel
===============================================================================*/
//dashboard
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');

//resources routes
Route::resources([
    'roles' => RoleController::class,
    'users' => UserController::class,
    'media-types' => MediaTypeController::class,
    'media-sellers' => MediaSellerController::class,
    'media-buyers' => MediaBuyerController::class,
    // 'programs' => ProgramController::class,
    'inquiries' => MediaInquiryController::class
]);

//media sellers
Route::any('media-sellers/lists', [MediaSellerController::class, 'index'])->name('media-sellers.lists');
Route::get('media-sellers/{id}/delete', [MediaSellerController::class, 'delete'])->name('media-sellers.delete');
// Route::get('media-sellers/{id}/change-status', [MediaSellerController::class, 'change_status'])->name('media-sellers.change-status');
// Route::put('media-sellers/{id}/store-status', [MediaSellerController::class, 'store_status'])->name('media-sellers.store-status');

//media types
Route::get('media-types/{id}/delete', [MediaTypeController::class, 'delete'])->name('media-types.delete');
Route::get('media-types/media_own_types/{media_id}', [MediaTypeController::class, 'media_own_types']);

//media programs
// Route::get('programs/{id}/delete', [ProgramController::class, 'delete'])->name('programs.delete');

//users
Route::get('users/{id}/change-status', [UserController::class, 'change_status'])->name('users.change-status');
Route::put('users/{id}/store-status', [UserController::class, 'store_status'])->name('users.store-status');
Route::get('users/{id}/delete', [UserController::class, 'delete'])->name('users.delete');

// //roles
Route::get('roles/{id}/assign-perms', [RoleController::class, 'assign_perms'])->name('roles.assign-perms');
Route::post('roles/{id}/store-perms', [RoleController::class, 'store_perms'])->name('roles.store-perms');
