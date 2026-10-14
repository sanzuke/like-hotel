<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaleCommission;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Rekap closing penjualan unit per periode (sumber: tabel sale_commissions). */
class SalesReportController extends Controller
{
    private function filters(Request $request): array
    {
        $v = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'include_cancelled' => ['nullable', 'boolean'],
        ]);

        return [
            $v['from'] ?? now()->startOfYear()->toDateString(),
            $v['to'] ?? now()->toDateString(),
            (bool) ($v['include_cancelled'] ?? false),
        ];
    }

    private function sum($rows): array
    {
        $deal = $rows->sum('deal_price');

        return [
            'count' => $rows->count(),
            'deal' => $deal,
            'commission' => $rows->sum('commission_amount'),
            'other' => $rows->sum('other_cost'),
            'net' => $rows->sum('seller_net_amount'),
            'rate' => $deal > 0 ? round($rows->sum('commission_amount') / $deal * 100, 2) : 0,
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
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['LAPORAN PENJUALAN UNIT - REKAP CLOSING']);
            fputcsv($out, ['Periode', "$from s/d $to"]);
            foreach (['Jumlah Closing' => 'count', 'Total Harga Deal' => 'deal', 'Total Komisi' => 'commission', 'Total Biaya Lain' => 'other', 'Total Hak Penjual' => 'net', 'Rata-rata Komisi (%)' => 'rate'] as $k => $f) {
                fputcsv($out, [$k, $r['total'][$f]]);
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
                fputcsv($out, [$c->number(), $c->created_at->format('d-m-Y'), $c->seller_name, $c->apartment_name, $c->deal_price,
                    $c->commission_type, $c->commission_value, $c->commission_amount, $c->other_cost, $c->seller_net_amount, $c->status, $c->notes]);
            }

            fclose($out);
        }, "laporan_penjualan_{$from}_sd_{$to}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
