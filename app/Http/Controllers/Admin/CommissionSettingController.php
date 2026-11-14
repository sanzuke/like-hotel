<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use App\Support\Commission;
use Illuminate\Http\Request;

class CommissionSettingController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'rent_percent' => 'required|numeric|min:0|max:100',
            'sale_percent' => 'required|numeric|min:0|max:100',
        ]);

        $before = ['rent' => Commission::rentPercent(), 'sale' => Commission::salePercent()];

        \App\Models\Setting::write(Commission::RENT_KEY, (string) (float) $data['rent_percent']);
        \App\Models\Setting::write(Commission::SALE_KEY, (string) (float) $data['sale_percent']);

        Audit::log('commission.settings_updated',
            "Pengaturan komisi: sewa {$before['rent']}% → ".(float) $data['rent_percent']."%, jual/beli {$before['sale']}% → ".(float) $data['sale_percent'].'%',
            null, ['before' => $before, 'after' => ['rent' => (float) $data['rent_percent'], 'sale' => (float) $data['sale_percent']]]);

        return redirect()->route('admin.commission')->with('success', 'Pengaturan komisi disimpan. Berlaku untuk booking baru dan form closing berikutnya.');
    }
}
