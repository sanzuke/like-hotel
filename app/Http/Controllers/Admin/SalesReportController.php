<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaleCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Rekap closing penjualan unit per periode (sumber: tabel sale_commissions). */
class SalesReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to, $withCancelled] = $this->filters($request);

        return view('admin.sales-report', $this->build($from, $to, $withCancelled) + compact('from', 'to', 'withCancelled'));
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to, $withCancelled] = $this->filters($request);
        $r = $this->build($from, $to, $withCancelled);

        return response()->streamDownload(function () use ($r, $from, $to) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

            fputcsv($out, ['LAPORAN PENJUALAN UNIT - REKAP CLOSING']);
            fputcsv($out, ['Periode', "$from s/d $to"]);
            foreach (['Jumlah Closing' => 'count', 'Total Harga Deal' => 'deal', 'Total Komisi' => 'commission', 'Total Biaya Lain' => 'other', 'Total Hak Penjual' => 'net', 'Rata-rata Komisi (%)' => 'rate'] as $label => $key) {
                fputcsv($out, [$label, $r['total'][$key]]);
            }

            foreach (['Rekap per Bulan' => 'byMonth', 'Rekap per Apartemen' => 'byApartment'] as $title => $key) {
                fputcsv($out, []);
                fputcsv($out, [$title]);
                fputcsv($out, ['Kelompok', 'Closing', 'Harga Deal', 'Komisi', 'Biaya Lain', 'Hak Penjual']);
                foreach ($r[$key] as $g) {
                    fputcsv($out, [$g['label'], $g['count'], $g['deal'], $g['commission'], $g['other'], $g['net']]);
                }
            }

            fputcsv($out, []);
            fputcsv($out, ['No', 'Tanggal', 'Penjual', 'Apartemen', 'Harga Deal', 'Tipe Komisi', 'Nilai Komisi', 'Komisi', 'Biaya Lain', 'Hak Penjual', 'Status', 'Catatan']);
            foreach ($r['rows'] as $c) {
                fputcsv($out, [
                    $c->number(), \App\Support\Fmt::date($c->created_at), $c->seller_name, $c->apartment_name, (float) $c->deal_price,
                    $c->commission_type, (float) $c->commission_value, (float) $c->commission_amount,
                    (float) $c->other_cost, (float) $c->seller_net_amount, $c->status, $c->notes,
                ]);
            }

            fclose($out);
        }, "laporan_penjualan_{$from}_sd_{$to}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $v = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'include_cancelled' => 'nullable|boolean',
        ]);

        return [
            $v['from'] ?? now()->startOfYear()->toDateString(),
            $v['to'] ?? now()->toDateString(),
            (bool) ($v['include_cancelled'] ?? false),
        ];
    }

    private function sum(Collection $rows): array
    {
        $deal = (float) $rows->sum('deal_price');
        $commission = (float) $rows->sum('commission_amount');

        return [
            'count' => $rows->count(),
            'deal' => $deal,
            'commission' => $commission,
            'other' => (float) $rows->sum('other_cost'),
            'net' => (float) $rows->sum('seller_net_amount'),
            'rate' => $deal > 0 ? round($commission / $deal * 100, 2) : 0,
        ];
    }

    private function build(string $from, string $to, bool $withCancelled): array
    {
        $rows = SaleCommission::whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
            ->when(! $withCancelled, fn ($q) => $q->where('status', '!=', 'batal'))
            ->orderBy('created_at')->orderBy('id')->get();

        $group = fn (callable $key) => $rows->groupBy($key)->map(fn ($g, $k) => ['label' => $k] + $this->sum($g))->values();

        return [
            'rows' => $rows,
            'total' => $this->sum($rows),
            'byMonth' => $group(fn ($r) => $r->created_at->format('Y-m')),
            'byApartment' => $group(fn ($r) => $r->apartment_name)->sortByDesc('deal')->values(),
        ];
    }
}
