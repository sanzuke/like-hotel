<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function edit()
    {
        return view('admin.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(10)->letters()->numbers(), 'different:current_password'],
        ], [
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
            'password.min' => 'Password baru minimal 10 karakter.',
            'password.different' => 'Password baru harus berbeda dari password saat ini.',
            'password.letters' => 'Password baru harus mengandung huruf.',
            'password.numbers' => 'Password baru harus mengandung angka.',
        ]);

        $admin = Auth::guard('admin')->user();

        if (! Hash::check($data['current_password'], $admin->password)) {
            Audit::log('admin.password_change_failed', 'Gagal ganti password: password saat ini salah');

            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        $admin->update(['password' => $data['password']]);
        $request->session()->regenerate();
        Audit::log('admin.password_changed', 'Admin mengganti password sendiri');

        return redirect()->route('admin.password.edit')->with('success', 'Password berhasil diganti.');
    }
}
