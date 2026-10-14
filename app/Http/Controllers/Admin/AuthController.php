<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['login' => ['required', 'string'], 'password' => ['required']]);

        $admin = Admin::where('email', $data['login'])->orWhere('username', $data['login'])->first();

        if (! $admin || $admin->status !== 'aktif' || ! Hash::check($data['password'], $admin->password)) {
            return back()->withInput($request->only('login'))->withErrors(['login' => 'Email/username atau password salah.']);
        }

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();
        $admin->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
