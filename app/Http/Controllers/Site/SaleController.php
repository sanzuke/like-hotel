<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\SaleRequest;
use App\Models\SaleUnit;
use App\Services\SaleRequestService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index()
    {
        return view('site.sale-index', ['rows' => SaleUnit::active()->latest('id')->get()]);
    }

    public function show(SaleUnit $saleUnit)
    {
        abort_unless($saleUnit->status === 'aktif', 404, 'Unit dijual tidak ditemukan atau sudah tidak aktif.');

        return view('site.sale-show', ['r' => $saleUnit->load('images')]);
    }

    public function requestForm()
    {
        return view('site.sale-request');
    }

    public function requestStore(Request $request)
    {
        $data = $request->validate([
            'owner_name' => ['required', 'string', 'max:150'],
            'owner_phone' => ['required', 'string', 'max:50'],
            'apartment_name' => ['required', 'string', 'max:150'],
            'location' => ['required', 'string', 'max:150'],
            'unit_type' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'string', 'max:50'],
            'expected_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $lead = SaleRequest::create($data + ['status' => 'baru']);
        app(SaleRequestService::class)->intake($lead);

        return redirect()->route('sale.request')
            ->with('status', 'Pengajuan titip jual berhasil dikirim. Tim Like Hotel akan menghubungi Anda.');
    }
}
