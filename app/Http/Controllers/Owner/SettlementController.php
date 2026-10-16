<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettlementController extends Controller
{
    public function index()
    {
        $owner = auth('owner')->user();
        $earnings = $owner->totalEarnings();
        $requested = $owner->totalRequested();

        return view('owner.settlements', [
            'owner' => $owner,
            'earnings' => $earnings,
            'requested' => $requested,
            'paid' => $owner->totalPaid(),
            'available' => max(0, $earnings - $requested),
            'settlements' => $owner->settlements()->latest('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|gt:0',
            'bank_name' => 'required|string|max:100',
            'bank_account_name' => 'required|string|max:150',
            'bank_account_number' => 'required|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $ok = DB::transaction(function () use ($data) {
            // Kunci owner agar dua pengajuan bersamaan tidak melebihi saldo.
            $owner = Owner::whereKey(auth('owner')->id())->lockForUpdate()->first();

            if ((float) $data['amount'] > $owner->availableBalance()) {
                return false;
            }

            $settlement = $owner->settlements()->create($data + ['status' => 'pending']);
            Audit::log('settlement.requested', "{$owner->name} mengajukan pencairan Rp".number_format($settlement->amount, 0, ',', '.'), $settlement);

            return true;
        });

        if (! $ok) {
            return back()->withInput()->withErrors(['amount' => 'Nominal melebihi saldo tersedia.']);
        }

        return redirect()->route('owner.settlements.index')
            ->with('success', 'Pengajuan pencairan berhasil dikirim. Menunggu persetujuan admin.');
    }
}
