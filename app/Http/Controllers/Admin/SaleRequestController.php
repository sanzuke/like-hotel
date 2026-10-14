<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaleRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SaleRequestController extends Controller
{
    public function index()
    {
        $rows = SaleRequest::latest('id')->get();

        return view('admin.sale-requests', [
            'rows' => $rows,
            'counts' => $rows->countBy('status'),
        ]);
    }

    public function status(Request $request, SaleRequest $saleRequest)
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(SaleRequest::STATUSES))]]);
        $saleRequest->update($data);

        return back()->with('status', 'Status pengajuan berhasil diperbarui.');
    }

    public function destroy(SaleRequest $saleRequest)
    {
        $saleRequest->delete();

        return back()->with('status', 'Pengajuan berhasil dihapus.');
    }
}
