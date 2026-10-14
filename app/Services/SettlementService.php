<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Owner;
use App\Models\Settlement;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    /** Ringkasan saldo owner: hak (booking lunas) − pencairan pending/approved/paid. */
    public function balance(int $ownerId): array
    {
        $hak = (float) Booking::where('owner_id', $ownerId)->paid()->sum('owner_amount');
        $requested = (float) Settlement::where('owner_id', $ownerId)->whereIn('status', ['pending', 'approved', 'paid'])->sum('amount');
        $paid = (float) Settlement::where('owner_id', $ownerId)->where('status', 'paid')->sum('amount');

        return [
            'total_hak' => $hak,
            'requested' => $requested,
            'paid' => $paid,
            'available' => max(0, $hak - $requested),
        ];
    }

    /** Owner mengajukan pencairan. */
    public function request(Owner $owner, array $data): Settlement
    {
        return DB::transaction(function () use ($owner, $data) {
            Owner::whereKey($owner->id)->lockForUpdate()->first();

            $available = $this->balance($owner->id)['available'];

            if ((float) $data['amount'] > $available) {
                throw new BookingException('Nominal melebihi saldo tersedia.');
            }

            return Settlement::create([
                'owner_id' => $owner->id,
                'amount' => $data['amount'],
                'bank_name' => $data['bank_name'],
                'bank_account_name' => $data['bank_account_name'],
                'bank_account_number' => $data['bank_account_number'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
            ]);
        });
    }

    /** Admin mencairkan seluruh saldo owner secara langsung. */
    public function payOwnerDirect(Owner $owner): ?Settlement
    {
        return DB::transaction(function () use ($owner) {
            Owner::whereKey($owner->id)->lockForUpdate()->first();

            $saldo = $this->balance($owner->id)['available'];

            if ($saldo <= 0) {
                return null;
            }

            $s = Settlement::create([
                'owner_id' => $owner->id, 'amount' => $saldo, 'bank_name' => '-',
                'bank_account_name' => $owner->name, 'bank_account_number' => '-',
                'notes' => 'Pencairan saldo oleh admin', 'admin_notes' => 'Dibayarkan langsung oleh admin',
                'status' => 'paid', 'paid_at' => now(),
            ]);
            ActivityLog::record('settlement.pay_direct', $s, ['amount' => $saldo]);

            return $s;
        });
    }

    /** Transisi status; hanya dari status asal yang diizinkan (paid tidak bisa ditolak lagi). */
    public function transition(Settlement $s, string $to): bool
    {
        $map = [
            'approved' => [['pending'], ['admin_notes' => 'Disetujui admin']],
            'paid' => [['approved', 'pending'], ['admin_notes' => 'Sudah ditransfer', 'paid_at' => now()]],
            'rejected' => [['pending'], ['admin_notes' => 'Ditolak admin']],
        ];

        [$from, $extra] = $map[$to];

        $changed = Settlement::whereKey($s->id)->whereIn('status', $from)->update(['status' => $to] + $extra);

        if ($changed) {
            ActivityLog::record('settlement.'.$to, $s);
        }

        return (bool) $changed;
    }
}
