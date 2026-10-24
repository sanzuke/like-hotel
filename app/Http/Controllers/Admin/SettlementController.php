<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\Settlement;
use App\Support\Audit;
use App\Support\TableFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettlementController extends Controller
{
    public function index(Request $request)
    {
        $filters = TableFilter::active([
            'owner' => TableFilter::id($request, 'owner'),
            'bank' => TableFilter::text($request, 'bank'),
            'status' => TableFilter::enum($request, 'status', ['pending', 'approved', 'paid', 'rejected']),
        ]);

        return view('admin.settlements', [
            'owners' => Owner::orderBy('name')->get()->each(function (Owner $o) {
                $o->earnings = $o->totalEarnings();
                $o->requested = $o->totalRequested();
                $o->paid = $o->totalPaid();
                $o->balance = max(0, $o->earnings - $o->requested);
            })->sortByDesc('earnings'),
            'rows' => Settlement::with('owner')
                ->when($filters['owner'] ?? null, fn ($q, $v) => $q->where('owner_id', $v))
                ->when($filters['bank'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('bank_name', 'like', '%'.$v.'%')->orWhere('bank_account_name', 'like', '%'.$v.'%')->orWhere('bank_account_number', 'like', '%'.$v.'%')))
                ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
                ->latest('id')->paginate(10)->withQueryString(),
            'filters' => $filters,
            'filterOwners' => Owner::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function commission()
    {
        return view('admin.commission', [
            'rows' => Owner::orderBy('name')->get()->map(function (Owner $o) {
                $paid = $o->bookings()->where('booking_status', 'dibayar');

                return (object) [
                    'owner' => $o,
                    'bookings' => $o->bookings()->count(),
                    'gross' => (clone $paid)->sum('total_price'),
                    'commission' => (clone $paid)->sum('commission_amount'),
                    'earnings' => $o->totalEarnings(),
                    'requested' => $o->totalRequested(),
                    'balance' => $o->availableBalance(),
                ];
            })->sortByDesc('earnings'),
        ]);
    }

    /** Admin membayarkan seluruh saldo tersedia milik owner. */
    public function payOwner(Owner $owner)
    {
        $amount = DB::transaction(function () use ($owner) {
            // Kunci baris owner agar dua klik bersamaan tidak mencairkan dua kali.
            $locked = Owner::whereKey($owner->id)->lockForUpdate()->first();
            $balance = $locked->availableBalance();

            if ($balance <= 0) {
                return 0;
            }

            $settlement = Settlement::create([
                'owner_id' => $locked->id,
                'amount' => $balance,
                'bank_name' => '-',
                'bank_account_name' => $locked->name,
                'bank_account_number' => '-',
                'notes' => 'Pencairan saldo oleh admin',
                'admin_notes' => 'Dibayarkan langsung oleh admin',
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            Audit::log('settlement.paid_direct', "Saldo {$locked->name} dibayarkan langsung oleh admin: Rp".number_format($balance, 0, ',', '.'), $settlement, ['owner_id' => $locked->id, 'amount' => $balance]);

            return $balance;
        });

        return back()->with($amount > 0 ? 'success' : 'error', $amount > 0
            ? 'Saldo owner berhasil dibayarkan.'
            : 'Owner tidak memiliki saldo tersedia.');
    }

    public function approve(Settlement $settlement)
    {
        return $this->transition($settlement, ['pending'], [
            'status' => 'approved', 'admin_notes' => 'Disetujui admin',
        ]);
    }

    public function markPaid(Settlement $settlement)
    {
        return $this->transition($settlement, ['pending', 'approved'], [
            'status' => 'paid', 'paid_at' => now(), 'admin_notes' => 'Sudah ditransfer',
        ]);
    }

    public function reject(Settlement $settlement)
    {
        return $this->transition($settlement, ['pending'], [
            'status' => 'rejected', 'admin_notes' => 'Ditolak admin',
        ]);
    }

    /** Ubah status hanya jika status saat ini diizinkan (paid tidak bisa diubah lagi). */
    private function transition(Settlement $settlement, array $allowedFrom, array $changes)
    {
        $changed = DB::transaction(function () use ($settlement, $allowedFrom, $changes) {
            $locked = Settlement::whereKey($settlement->id)->lockForUpdate()->first();

            if (! in_array($locked->status, $allowedFrom, true)) {
                return false;
            }

            $from = $locked->status;
            $locked->update($changes);
            Audit::log('settlement.'.$changes['status'], "Pencairan #{$locked->id} {$from} → {$changes['status']} (Rp".number_format($locked->amount, 0, ',', '.').')', $locked, ['from' => $from, 'to' => $changes['status']]);

            return true;
        });

        return back()->with($changed ? 'success' : 'error', $changed
            ? 'Status pencairan diperbarui.'
            : 'Status pencairan saat ini tidak dapat diubah dengan aksi tersebut.');
    }
}
