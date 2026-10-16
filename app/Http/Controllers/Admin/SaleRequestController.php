<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaleCommission;
use App\Models\SaleRequest;
use App\Models\SaleRequestNote;
use App\Models\SaleUnit;
use App\Support\Audit;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SaleRequestController extends Controller
{
    public function index()
    {
        $rows = SaleRequest::latest('id')->get();

        return view('admin.sale-requests', [
            'rows' => $rows,
            'stages' => SaleRequest::STAGES,
            'counts' => $rows->countBy('status'),
        ]);
    }

    public function show(SaleRequest $saleRequest)
    {
        return view('admin.sale-request-show', [
            'r' => $saleRequest->load(['timeline', 'saleUnit', 'commission']),
            'stages' => SaleRequest::STAGES,
            'noteTypes' => SaleRequestNote::TYPES,
        ]);
    }

    public function updateStatus(Request $request, SaleRequest $saleRequest)
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(SaleRequest::STAGES))]]);

        $this->changeStatus($saleRequest, $data['status']);

        return back()->with('success', 'Status pengajuan berhasil diperbarui.');
    }

    public function addNote(Request $request, SaleRequest $saleRequest)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(SaleRequestNote::TYPES))],
            'body' => 'required|string|max:2000',
            'agreed_price' => 'nullable|numeric|min:0',
        ]);

        $body = $data['body'];

        // Catatan negosiasi boleh sekaligus mencatat harga kesepakatan.
        if ($data['type'] === 'negosiasi' && ! empty($data['agreed_price'])) {
            $saleRequest->update(['agreed_price' => $data['agreed_price']]);
            $body .= "\n(Harga disepakati: ".Fmt::rupiah($data['agreed_price']).')';
        }

        $saleRequest->addNote($data['type'], $body);
        Audit::log('crm.note', "Catatan {$data['type']} pada lead {$saleRequest->owner_name} ({$saleRequest->apartment_name})", $saleRequest);

        return back()->with('success', 'Catatan ditambahkan ke timeline.');
    }

    public function scheduleSurvey(Request $request, SaleRequest $saleRequest)
    {
        $data = $request->validate(['survey_at' => 'required|date|after:now']);

        $saleRequest->update(['survey_at' => $data['survey_at']]);
        $saleRequest->addNote('survey', 'Survei dijadwalkan: '.$saleRequest->fresh()->survey_at->format('d-m-Y H:i'));
        Audit::log('crm.survey_scheduled', "Survei lead {$saleRequest->owner_name} dijadwalkan", $saleRequest);

        if (in_array($saleRequest->status, ['baru', 'follow up'], true)) {
            $this->changeStatus($saleRequest, 'survey');
        }

        return back()->with('success', 'Jadwal survei disimpan.');
    }

    /** Buat draf unit dijual dari data lead; foto & harga final diatur di halaman edit unit. */
    public function publish(SaleRequest $saleRequest)
    {
        if ($saleRequest->sale_unit_id) {
            return back()->with('error', 'Lead ini sudah dipublikasikan sebagai unit dijual.');
        }

        $unit = DB::transaction(function () use ($saleRequest) {
            $unit = SaleUnit::create([
                'title' => 'Dijual '.$saleRequest->apartment_name.($saleRequest->unit_type ? ' '.$saleRequest->unit_type : ''),
                'apartment_name' => $saleRequest->apartment_name,
                'location' => $saleRequest->location,
                'unit_type' => $saleRequest->unit_type,
                'size' => $saleRequest->size,
                'price' => $saleRequest->agreed_price ?? $saleRequest->expected_price ?? 0,
                'description' => $saleRequest->notes,
                'seller_name' => $saleRequest->owner_name,
                'seller_phone' => $saleRequest->owner_phone,
                'status' => 'nonaktif', // draf: aktifkan setelah foto & harga dicek
            ]);

            $saleRequest->update(['sale_unit_id' => $unit->id]);
            $saleRequest->addNote('catatan', "Draf unit dijual dibuat (#{$unit->id}). Lengkapi foto lalu aktifkan.");

            return $unit;
        });

        Audit::log('crm.published', "Lead {$saleRequest->owner_name} dipublikasikan sebagai unit dijual #{$unit->id} (draf)", $saleRequest);

        if ($saleRequest->status !== 'listing') {
            $this->changeStatus($saleRequest, 'listing');
        }

        return redirect()->route('admin.sale-units.edit', $unit)
            ->with('success', 'Draf unit dibuat dengan status nonaktif. Tambahkan foto, cek harga, lalu aktifkan.');
    }

    /** Catat closing: membuat data komisi penjualan dan menandai unit terjual. */
    public function close(Request $request, SaleRequest $saleRequest)
    {
        if ($saleRequest->commission()->exists()) {
            return back()->with('error', 'Closing untuk lead ini sudah dicatat.');
        }

        $data = $request->validate([
            'deal_price' => 'required|numeric|gt:0',
            'commission_type' => 'required|in:percent,fixed',
            'commission_value' => 'required|numeric|min:0',
            'other_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);
        $other = (float) ($data['other_cost'] ?? 0);

        $commission = DB::transaction(function () use ($saleRequest, $data, $other) {
            $commission = SaleCommission::create([
                'sale_request_id' => $saleRequest->id,
                'sale_unit_id' => $saleRequest->sale_unit_id,
                'seller_name' => $saleRequest->owner_name,
                'apartment_name' => $saleRequest->apartment_name,
                'deal_price' => $data['deal_price'],
                'commission_type' => $data['commission_type'],
                'commission_value' => $data['commission_value'],
                'other_cost' => $other,
                'notes' => $data['notes'] ?? null,
                'status' => 'closing',
            ] + SaleCommission::compute((float) $data['deal_price'], $data['commission_type'], (float) $data['commission_value'], $other));

            $saleRequest->update(['agreed_price' => $data['deal_price']]);
            $saleRequest->saleUnit?->update(['status' => 'terjual']);

            return $commission;
        });

        $this->changeStatus($saleRequest, 'closing');
        Audit::log('sale_commission.created', "Closing lead {$saleRequest->owner_name}: deal ".Fmt::rupiah($data['deal_price']).", komisi ".Fmt::rupiah($commission->commission_amount), $commission);

        return redirect()->route('admin.sale-requests.show', $saleRequest)
            ->with('success', 'Closing dicatat. Komisi Like Hotel: '.Fmt::rupiah($commission->commission_amount));
    }

    public function destroy(SaleRequest $saleRequest)
    {
        Audit::log('crm.deleted', "Lead {$saleRequest->owner_name} ({$saleRequest->apartment_name}) dihapus", $saleRequest);
        $saleRequest->delete();

        return redirect()->route('admin.sale-requests.index')->with('success', 'Pengajuan berhasil dihapus.');
    }

    private function changeStatus(SaleRequest $saleRequest, string $to): void
    {
        $from = $saleRequest->status;

        if ($from === $to) {
            return;
        }

        $saleRequest->update(['status' => $to]);
        $saleRequest->addNote('catatan', "Status: {$from} → {$to}");
        Audit::log('crm.status', "Lead {$saleRequest->owner_name}: {$from} → {$to}", $saleRequest, ['from' => $from, 'to' => $to]);
    }
}
