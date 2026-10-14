<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaleCommission;
use Illuminate\Http\Request;

class SaleCommissionController extends Controller
{
    private function validated(Request $request): array
    {
        $d = $request->validate([
            'seller_name' => ['required', 'string', 'max:150'],
            'apartment_name' => ['required', 'string', 'max:150'],
            'deal_price' => ['required', 'numeric', 'min:0'],
            'commission_type' => ['required', 'in:percent,fixed'],
            'commission_value' => ['required', 'numeric', 'min:0', $request->input('commission_type') === 'percent' ? 'max:100' : 'max:99999999999'],
            'other_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'in:closing,selesai,batal'],
        ]);

        $d['other_cost'] = (float) ($d['other_cost'] ?? 0);

        return $d + SaleCommission::compute((float) $d['deal_price'], $d['commission_type'], (float) $d['commission_value'], $d['other_cost']);
    }

    public function index()
    {
        $rows = SaleCommission::latest('id')->get();

        return view('admin.sale-commissions', [
            'rows' => $rows,
            'totalDeal' => $rows->sum('deal_price'),
            'totalCommission' => $rows->sum('commission_amount'),
            'totalSellerNet' => $rows->sum('seller_net_amount'),
        ]);
    }

    public function store(Request $request)
    {
        SaleCommission::create($this->validated($request) + ['status' => 'closing']);

        return redirect()->route('admin.sale-commissions')->with('status', 'Data closing disimpan.');
    }

    public function edit(SaleCommission $saleCommission)
    {
        return view('admin.sale-commission-edit', ['r' => $saleCommission]);
    }

    public function update(Request $request, SaleCommission $saleCommission)
    {
        $saleCommission->update($this->validated($request));

        return redirect()->route('admin.sale-commissions')->with('status', 'Data closing diperbarui.');
    }

    public function destroy(SaleCommission $saleCommission)
    {
        $saleCommission->delete();

        return back()->with('status', 'Data closing dihapus.');
    }

    public function invoice(SaleCommission $saleCommission)
    {
        return view('admin.sale-commission-invoice', ['r' => $saleCommission]);
    }
}
