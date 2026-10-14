<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Owner;
use App\Models\Settlement;
use App\Services\SettlementService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private function filters(Request $request): array
    {
        $v = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'basis' => ['nullable', 'in:created_at,checkin'],
        ]);

        return [
            $v['from'] ?? now()->startOfMonth()->toDateString(),
            $v['to'] ?? now()->toDateString(),
            $v['basis'] ?? 'created_at',
        ];
    }

    private function build(string $from, string $to, string $basis): array
    {
        $rows = Booking::with('apartment')
            ->whereDate($basis, '>=', $from)->whereDate($basis, '<=', $to)
            ->latest('id')->get();

        $paid = $rows->filter(fn ($b) => $b->isPaid());

        // Pencairan dilaporkan berdasarkan tanggal dibayar (paid_at), bukan tanggal pengajuan.
        $settlementPaid = (float) Settlement::where('status', 'paid')
            ->whereDate('paid_at', '>=', $from)->whereDate('paid_at', '<=', $to)->sum('amount');

        $owner = $paid->sum('owner_amount');

        return [
            'rows' => $rows,
            'totalBooking' => $rows->count(),
            'totalLunas' => $paid->count(),
            'totalPending' => $rows->count() - $paid->count(),
            'totalMalam' => $rows->sum(fn ($b) => $b->nights()),
            'totalOmzet' => $paid->sum('total_price'),
            'totalKomisi' => $paid->sum('commission_amount'),
            'totalOwner' => $owner,
            'settlementPaid' => $settlementPaid,
            'sisaHakOwner' => max(0, $owner - $settlementPaid),
        ];
    }

    public function index(Request $request)
    {
        [$from, $to, $basis] = $this->filters($request);

        return view('admin.reports', $this->build($from, $to, $basis) + compact('from', 'to', 'basis'));
    }

    /** Ekspor CSV (UTF-8 BOM agar terbuka benar di Excel). */
    public function export(Request $request): StreamedResponse
    {
        [$from, $to, $basis] = $this->filters($request);
        $r = $this->build($from, $to, $basis);

        return response()->streamDownload(function () use ($r, $from, $to) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['LAPORAN KEUANGAN LIKE HOTEL']);
            fputcsv($out, ['Periode', "$from s/d $to"]);
            foreach ([
                'Total Booking' => $r['totalBooking'], 'Booking Lunas' => $r['totalLunas'], 'Belum Lunas' => $r['totalPending'],
                'Total Malam' => $r['totalMalam'], 'Omzet Lunas' => $r['totalOmzet'], 'Komisi Like Hotel' => $r['totalKomisi'],
                'Hak Owner' => $r['totalOwner'], 'Pencairan Owner (paid_at)' => $r['settlementPaid'], 'Sisa Hak Owner' => $r['sisaHakOwner'],
            ] as $k => $v) {
                fputcsv($out, [$k, $v]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Tanggal', 'Kode Booking', 'Invoice', 'Tamu', 'No WhatsApp', 'Apartment', 'Owner', 'Check-in', 'Check-out',
                'Malam', 'Harga/Malam', 'Total', 'Komisi', 'Hak Owner', 'Status Pembayaran', 'Status Booking']);

            foreach ($r['rows'] as $b) {
                $n = $b->nights();
                fputcsv($out, [
                    $b->created_at->format('d-m-Y H:i'), $b->booking_code, $b->invoice_number, $b->guest_name, $b->guest_phone,
                    $b->apartment->name, $b->apartment->owner_name, $b->checkin->toDateString(), $b->checkout->toDateString(),
                    $n, round($b->subtotal() / $n), $b->total_price, $b->commission_amount, $b->owner_amount,
                    $b->payment_status, $b->booking_status,
                ]);
            }

            fclose($out);
        }, "laporan_like_hotel_{$from}_sd_{$to}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function commission(SettlementService $settlements)
    {
        $rows = Owner::all()->map(function (Owner $o) use ($settlements) {
            $paid = Booking::where('owner_id', $o->id)->paid();
            $bal = $settlements->balance($o->id);

            return [
                'owner' => $o,
                'total_booking' => Booking::where('owner_id', $o->id)->count(),
                'gross' => (float) (clone $paid)->sum('total_price'),
                'commission' => (float) (clone $paid)->sum('commission_amount'),
                'owner_amount' => $bal['total_hak'],
                'requested' => $bal['requested'],
                'available' => $bal['available'],
            ];
        })->sortByDesc('owner_amount');

        return view('admin.commission', ['rows' => $rows]);
    }
}
