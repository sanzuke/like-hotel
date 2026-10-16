<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\SaleRequest;
use App\Models\SaleUnit;
use App\Support\Audit;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index()
    {
        return view('site.sale-units', ['rows' => SaleUnit::active()->latest('id')->get()]);
    }

    public function show(SaleUnit $saleUnit)
    {
        abort_unless($saleUnit->status === 'aktif', 404, 'Unit dijual tidak ditemukan atau sudah tidak aktif.');

        $gallery = collect([$saleUnit->image])
            ->merge($saleUnit->images()->orderBy('id')->pluck('image'))
            ->filter()->unique()->values();

        return view('site.sale-show', ['r' => $saleUnit, 'gallery' => $gallery]);
    }

    public function requestForm()
    {
        return view('site.sale-request');
    }

    public function requestStore(Request $request)
    {
        $data = $request->validate([
            'owner_name' => 'required|string|max:150',
            'owner_phone' => ['required', 'string', 'max:50', 'regex:/^[0-9+\s\-]{8,}$/'],
            'apartment_name' => 'required|string|max:150',
            'location' => 'required|string|max:150',
            'unit_type' => 'nullable|string|max:100',
            'size' => 'nullable|string|max:50',
            'expected_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ], ['owner_phone.regex' => 'Nomor WhatsApp tidak valid.']);

        $lead = SaleRequest::create($data + ['status' => 'baru']);
        $lead->hasMany(\App\Models\SaleRequestNote::class)->create(['type' => 'catatan', 'body' => 'Pengajuan masuk dari formulir situs.', 'author' => 'Sistem']);
        Audit::log('crm.lead_created', "Lead titip jual baru: {$lead->owner_name} ({$lead->apartment_name})", $lead, [], ['type' => 'guest', 'name' => $lead->owner_name]);

        return redirect()->route('sale-request.create')
            ->with('success', 'Pengajuan titip jual berhasil dikirim. Tim Like Hotel akan menghubungi Anda.');
    }
}
