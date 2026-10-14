<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\Settlement;
use App\Services\SettlementService;

class SettlementController extends Controller
{
    public function __construct(private SettlementService $settlements) {}

    public function index()
    {
        $summary = Owner::all()->map(fn (Owner $o) => ['owner' => $o] + $this->settlements->balance($o->id))
            ->sortByDesc('total_hak');

        return view('admin.settlements', [
            'summary' => $summary,
            'rows' => Settlement::with('owner')->latest('id')->get(),
        ]);
    }

    public function payOwner(Owner $owner)
    {
        $s = $this->settlements->payOwnerDirect($owner);

        return back()->with($s ? 'status' : 'error', $s
            ? 'Pencairan '.rupiah($s->amount)." untuk {$owner->name} dicatat sebagai dibayar."
            : 'Tidak ada saldo yang bisa dicairkan.');
    }

    public function transition(Settlement $settlement, string $action)
    {
        $to = ['approve' => 'approved', 'paid' => 'paid', 'reject' => 'rejected'][$action];

        $ok = $this->settlements->transition($settlement, $to);

        return back()->with($ok ? 'status' : 'error', $ok
            ? 'Status pencairan diperbarui.'
            : 'Perubahan status tidak diizinkan dari status saat ini.');
    }
}
