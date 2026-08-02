<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Base;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;

Route::get('/', [Base\IndexController::class, 'index'])->name('index')->fallback()->withoutMiddleware(['auth.session', \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication::class]);
Route::get('/account', [Base\IndexController::class, 'index'])
    ->withoutMiddleware(RequireTwoFactorAuthentication::class)
    ->name('account');

// Wallet endpoints - the /account/wallet page itself is rendered by the
// React SPA catch-all below; these are the data/action endpoints it calls.
Route::get('/account/wallet/data', [\Pterodactyl\Http\Controllers\Client\WalletController::class, 'data'])->name('account.wallet.data');
Route::post('/account/wallet/topup', [\Pterodactyl\Http\Controllers\Client\WalletController::class, 'initialize'])->name('account.wallet.topup');
Route::get('/account/wallet/callback', [\Pterodactyl\Http\Controllers\Client\WalletController::class, 'callback'])->name('account.wallet.callback');

Route::get('/locales/locale.json', Base\LocaleController::class)
    ->withoutMiddleware(['auth', RequireTwoFactorAuthentication::class])
    ->where('namespace', '.*');

Route::get('/{react}', [Base\IndexController::class, 'index'])
    ->where('react', '^(?!(\/)?(api|auth|admin|daemon)).+');
