<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Check if user is active
            if (Auth::user()->status !== 'active') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Votre compte est désactivé.',
                ]);
            }

            // Log the login
            ActivityLog::log(
                'login',
                'User',
                ['user_id' => Auth::id(), 'name' => Auth::user()->name]
            );

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Les identifiants fournis ne correspondent pas à nos enregistrements.',
        ])->onlyInput('email');
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        // Log the logout
        if (Auth::check()) {
            ActivityLog::log(
                'logout',
                'User',
                ['user_id' => Auth::id(), 'name' => Auth::user()->name]
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Handle beacon logout (fermeture d'onglet)
     */
    public function beaconLogout(Request $request)
    {
        if (Auth::check()) {
            ActivityLog::log(
                'logout',
                'User',
                ['user_id' => Auth::id(), 'name' => Auth::user()->name, 'reason' => 'tab_closed']
            );

            Auth::logout();
            $request->session()->invalidate();
        }

        return response()->noContent();
    }
}
