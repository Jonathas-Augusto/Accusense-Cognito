<?php

namespace Accusense\Cognito\Controllers;

use Illuminate\Routing\Controller;

class LoginController extends Controller
{
    public function login()
    {
        return response()->json('login');
    }

    public function firstLogin()
    {
        return response()->json('firstLogin');
    }

    public function refresh()
    {
        return response()->json('refresh');
    }

    public function revoke()
    {
        return response()->json('revoke');
    }

    public function sendResetCode()
    {
        return response()->json('sendResetCode');
    }

    public function resetPassword()
    {
            return response()->json('resetPassword');
    }
}
