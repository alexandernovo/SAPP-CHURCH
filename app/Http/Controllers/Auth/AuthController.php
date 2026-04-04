<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'userName' => 'required|string',
            'userPass' => 'required|string',
        ]);

        if (! Auth::attempt(
            [
                'userName' => $credentials['userName'],
                'password' => $credentials['userPass'],
            ],
            $request->boolean('remember')
        )) {
            return back()
                ->withErrors(['login' => 'Invalid username or password.'])
                ->withInput($request->only('userName'));
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landingPage');
    }
}
