<?php

namespace Accusense\Cognito\Routes;

use Accusense\Cognito\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::controller(LoginController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/first-login', 'firstLogin');
    Route::post('/refresh-token', 'refresh');
    Route::post('/revoke-token', 'revoke');
    Route::get('/reset-code', 'sendResetCode');
    Route::post('/reset-password', 'ResetPassword');
});
