<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLogin(): Response
    {
        if (Auth::check()) {
            return Inertia::render('Dashboard/Index');
        }

        return Inertia::render('Auth/Login');
    }

    /**
     * Handle staff login request
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password'], 'is_active' => true], $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang kembali, ' . (Auth::user()->name ?: Auth::user()->username) . '!');
        }

        return back()->withErrors([
            'username' => 'Username atau password tidak cocok, atau akun Anda nonaktif.',
        ])->onlyInput('username');
    }

    /**
     * Update the authenticated user's password
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return back()->with('success', 'Password berhasil diubah.');
    }
    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tracking.index')->with('info', 'Anda telah keluar dari sistem.');
    }
}
