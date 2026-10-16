<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use App\Support\Audit;
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
            'code' => 'required|string|max:50|unique:promos,code',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|gt:0',
            'expired_at' => 'nullable|date',
        ]);

        if ($data['type'] === 'percent' && $data['value'] > 100) {
            return back()->withInput()->withErrors(['value' => 'Diskon persen maksimal 100.']);
        }

        $promo = Promo::create($data + ['status' => 'aktif']);
        Audit::log('promo.created', "Promo {$promo->code} dibuat", $promo);

        return back()->with('success', 'Promo disimpan.');
    }

    public function toggle(Promo $promo)
    {
        $promo->update(['status' => $promo->status === 'aktif' ? 'nonaktif' : 'aktif']);
        Audit::log('promo.toggled', "Promo {$promo->code} menjadi {$promo->status}", $promo);

        return back();
    }

    public function destroy(Promo $promo)
    {
        Audit::log('promo.deleted', "Promo {$promo->code} dihapus", $promo);
        $promo->delete();

        return back()->with('success', 'Promo dihapus.');
    }
}
