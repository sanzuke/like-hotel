<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaleCommission;
use App\Support\Audit;
use App\Support\TableFilter;
use Illuminate\Http\Request;

class SaleCommissionController extends Controller
{
    public function index(Request $request)
    {
        $statuses = SaleCommission::query()->whereNotNull('status')->distinct()->orderBy('status')->pluck('status')->all();
        $filters = TableFilter::active([
            'seller' => TableFilter::text($request, 'seller', 100),
            'apartment' => TableFilter::text($request, 'apartment', 100),
            'status' => TableFilter::enum($request, 'status', $statuses),
        ]);

        $rows = SaleCommission::query()
            ->when($filters['seller'] ?? null, fn ($q, $v) => $q->where('seller_name', 'like', '%'.$v.'%'))
            ->when($filters['apartment'] ?? null, fn ($q, $v) => $q->where('apartment_name', 'like', '%'.$v.'%'))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.sale-commissions.index', [
            'rows' => $rows,
            'filters' => $filters,
            'statuses' => $statuses,
            // Total dihitung di database agar mencakup seluruh data, bukan hanya halaman yang tampil.
            'totalDeal' => SaleCommission::sum('deal_price'),
            'totalCommission' => SaleCommission::sum('commission_amount'),
            'totalNet' => SaleCommission::sum('seller_net_amount'),
        ]);
    }

    public function store(Request $request)
    {
        $c = SaleCommission::create($this->payload($request) + ['status' => 'closing']);
        Audit::log('sale_commission.created', "Closing {$c->apartment_name} dicatat: deal Rp".number_format($c->deal_price, 0, ',', '.'), $c);

        return back()->with('success', 'Data closing disimpan.');
    }

    public function edit(SaleCommission $saleCommission)
    {
        return view('admin.sale-commissions.edit', ['r' => $saleCommission]);
    }

    public function update(Request $request, SaleCommission $saleCommission)
    {
        $status = $request->validate(['status' => 'required|string|max:30'])['status'];
        $saleCommission->update($this->payload($request) + ['status' => $status]);
        Audit::log('sale_commission.updated', "Data closing #{$saleCommission->id} diperbarui", $saleCommission);

        return redirect()->route('admin.sale-commissions.index')->with('success', 'Data closing diperbarui.');
    }

    public function destroy(SaleCommission $saleCommission)
    {
        Audit::log('sale_commission.deleted', "Data closing #{$saleCommission->id} dihapus", $saleCommission);
        $saleCommission->delete();

        return back()->with('success', 'Data closing dihapus.');
    }

    public function invoice(SaleCommission $saleCommission)
    {
        return view('admin.sale-commissions.invoice', ['r' => $saleCommission]);
    }

    private function payload(Request $request): array
    {
        $data = $request->validate([
            'seller_name' => 'nullable|string|max:150',
            'apartment_name' => 'required|string|max:150',
            'deal_price' => 'required|numeric|min:0',
            'commission_type' => 'required|in:percent,fixed',
            'commission_value' => 'required|numeric|min:0',
            'other_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $data['other_cost'] = (float) ($data['other_cost'] ?? 0);

        return $data + SaleCommission::compute(
            (float) $data['deal_price'], $data['commission_type'],
            (float) $data['commission_value'], $data['other_cost'],
        );
    }
}
