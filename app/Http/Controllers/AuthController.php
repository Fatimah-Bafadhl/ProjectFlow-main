<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Client;
use App\Enums\Role;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

    
            return match ($user->role) {
                Role::Admin => redirect()->intended('dashboard'),
                Role::Manager => redirect()->route('dashboard'),
                Role::Employee => redirect()->route('tasks.index'),
                Role::Client => redirect()->route('projects.index'),
            };
        }

        return back()->withErrors([
            'email' => 'البيانات المدخلة غير مطابقة لطبيعة الحساب.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}