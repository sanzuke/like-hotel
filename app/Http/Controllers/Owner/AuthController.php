<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('owner.auth', ['mode' => 'login']);
    }

    public function login(Request $request)
    {
        $cred = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);

        $owner = Owner::where('email', $cred['email'])->first();

        if (! $owner || ! Hash::check($cred['password'], $owner->password)) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Email atau password salah.']);
        }

        if ($owner->status === 'pending') {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Akun Anda masih menunggu persetujuan admin.']);
        }

        if ($owner->status !== 'aktif') {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Akun Anda tidak aktif. Hubungi admin.']);
        }

        Auth::guard('owner')->login($owner);
        $request->session()->regenerate();

        return redirect()->intended(route('owner.dashboard'));
    }

    public function registerForm()
    {
        return view('owner.auth', ['mode' => 'register']);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:150', 'unique:owners,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], ['email.unique' => 'Email ini sudah terdaftar. Silakan login.']);

        $owner = Owner::create($data + ['status' => 'pending']);
        ActivityLog::record('owner.registered', $owner);

        return redirect()->route('owner.login')
            ->with('status', 'Pendaftaran berhasil. Akun Anda akan aktif setelah disetujui admin.');
    }

    public function logout(Request $request)
    {
        Auth::guard('owner')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('owner.login');
    }
}
