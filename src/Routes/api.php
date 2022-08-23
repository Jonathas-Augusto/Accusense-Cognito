<?php

namespace Accusense\Cognito\Routes;

use Accusense\Cognito\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::controller(LoginController::class)->group(function () {
    Route::post('login');
    Route::post('first-login');
    Route::post('refresh-token');
    Route::post('revoke-token');
    Route::get('reset-code');
    Route::post('reset-password');
});
