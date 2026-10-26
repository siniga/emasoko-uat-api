<?php

use App\Http\Controllers\Wallet\WalletController;
use Illuminate\Support\Facades\Route;

// The legacy portal auth middleware logs out inactive users through a session-only
// method. Use Laravel's token authentication here; the controller checks active status.
Route::middleware(\Illuminate\Auth\Middleware\Authenticate::class . ':api')->group(function () {
    Route::get('context', [WalletController::class, 'context'])->name('context');
    Route::get('account', [WalletController::class, 'account'])->name('account');
});
