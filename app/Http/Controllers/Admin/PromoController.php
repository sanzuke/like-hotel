<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function index()
    {
        return view('admin.promos', ['promos' => Promo::latest('id')->get()]);
    }

    public function store(Request $request)
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:promos,code'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'gt:0', $request->input('type') === 'percent' ? 'max:100' : 'max:99999999'],
            'expired_at' => ['nullable', 'date'],
        ]);

        Promo::create($data + ['status' => 'aktif']);

        return back()->with('status', 'Promo ditambahkan.');
    }

    public function toggle(Promo $promo)
    {
        $promo->update(['status' => $promo->status === 'aktif' ? 'nonaktif' : 'aktif']);

        return back();
    }

    public function destroy(Promo $promo)
    {
        $promo->delete();

        return back()->with('status', 'Promo dihapus.');
    }
}
