<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Settlement;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /** Kolom tanggal yang dipakai memfilter: tanggal booking, pembayaran, atau check-in. */
    private const BASIS = [
        'created_at' => 'Tanggal booking',
        'paid_at' => 'Tanggal pembayaran',
        'checkin' => 'Tanggal check-in',
    ];

    public function index(Request $request)
    {
        [$from, $to, $basis, $rows] = $this->query($request);

        $paid = $rows->where('booking_status', 'dibayar');
        $owner = (float) $paid->sum('owner_amount');
        // Laporan kas: pencairan dihitung dari tanggal dibayar (paid_at), bukan tanggal pengajuan.
        $settlementPaid = (float) Settlement::where('status', 'paid')
            ->whereBetween('paid_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->sum('amount');

        return view('admin.reports', [
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'basis' => $basis,
            'bases' => self::BASIS,
            'total' => $rows->count(),
            'lunas' => $paid->count(),
            'belum' => $rows->count() - $paid->count(),
            'omzet' => (float) $paid->sum('total_price'),
            'komisi' => (float) $paid->sum('commission_amount'),
            'owner' => $owner,
            'settlementPaid' => $settlementPaid,
        ]);
    }

    /** Ekspor CSV (dapat dibuka langsung di Excel). */
    public function export(Request $request): StreamedResponse
    {
        [$from, $to, , $rows] = $this->query($request);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, ['Tanggal', 'Kode', 'Tamu', 'Apartment', 'Owner', 'Check-in', 'Check-out', 'Total', 'Komisi', 'Hak Owner', 'Status Bayar', 'Status Booking']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->created_at->format('Y-m-d H:i'), $r->booking_code, $r->guest_name,
                    $r->apartment->name, $r->apartment->owner_name,
                    $r->checkin->toDateString(), $r->checkout->toDateString(),
                    (int) $r->total_price, (int) $r->commission_amount, (int) $r->owner_amount,
                    $r->payment_status, $r->booking_status,
                ]);
            }

            fclose($out);
        }, "laporan-likehotel-{$from}_{$to}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function query(Request $request): array
    {
        $data = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'basis' => 'nullable|in:'.implode(',', array_keys(self::BASIS)),
        ]);

        $from = $data['from'] ?? now()->startOfMonth()->toDateString();
        $to = $data['to'] ?? now()->toDateString();
        $basis = $data['basis'] ?? 'created_at';

        $rows = Booking::with('apartment')
            ->when($basis === 'checkin',
                fn ($q) => $q->whereBetween('checkin', [$from, $to]),
                fn ($q) => $q->whereBetween($basis, [$from.' 00:00:00', $to.' 23:59:59']))
            ->latest('id')
            ->get();

        return [$from, $to, $basis, $rows];
    }
}
