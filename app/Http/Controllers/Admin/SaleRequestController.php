<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaleCommission;
use App\Models\SaleRequest;
use App\Models\SaleRequestNote;
use App\Models\SaleRequestPhoto;
use App\Models\SaleUnit;
use App\Support\Audit;
use App\Support\Fmt;
use App\Support\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaleRequestController extends Controller
{
    /** Tahap awal: boleh otomatis maju saat ada penawaran / foto. */
    private const EARLY = ['baru', 'follow up', 'survey', 'foto unit', 'listing'];

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
            'r' => $saleRequest->load(['timeline', 'photos', 'saleUnit', 'commission']),
            'stages' => SaleRequest::STAGES,
            'noteTypes' => SaleRequestNote::TYPES,
            'labels' => SaleRequestNote::LABELS,
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

    /** Penawaran dari calon pembeli; otomatis memindahkan lead tahap awal ke Negosiasi. */
    public function addOffer(Request $request, SaleRequest $saleRequest)
    {
        $data = $request->validate(['amount' => 'required|numeric|gt:0', 'note' => 'nullable|string|max:1000']);

        $saleRequest->update(['last_offer' => $data['amount']]);
        $saleRequest->addNote('penawaran', 'Penawaran pembeli: '.Fmt::rupiah($data['amount']).(filled($data['note'] ?? null) ? "\n".$data['note'] : ''), (float) $data['amount']);
        Audit::log('crm.offer', "Penawaran {$saleRequest->owner_name}: ".Fmt::rupiah($data['amount']), $saleRequest, ['amount' => $data['amount']]);

        $this->advance($saleRequest, 'negosiasi', self::EARLY);

        return back()->with('success', 'Penawaran dicatat.');
    }

    public function scheduleSurvey(Request $request, SaleRequest $saleRequest)
    {
        $data = $request->validate(['survey_at' => 'required|date|after:now']);

        $saleRequest->update(['survey_at' => $data['survey_at']]);
        $saleRequest->addNote('survey', 'Survei dijadwalkan: '.$saleRequest->fresh()->survey_at->format('d-m-Y H:i'));
        Audit::log('crm.survey_scheduled', "Survei lead {$saleRequest->owner_name} dijadwalkan", $saleRequest);

        $this->advance($saleRequest, 'survey', ['baru', 'follow up']);

        return back()->with('success', 'Jadwal survei disimpan.');
    }

    public function addPhotos(Request $request, SaleRequest $saleRequest)
    {
        $request->validate([
            'photos' => 'required|array|max:20',
            'photos.*' => ImageStore::RULE,
        ], ['photos.required' => 'Pilih minimal satu foto.']);

        $count = 0;

        foreach ($request->file('photos') as $file) {
            $saleRequest->photos()->create(['image' => ImageStore::store($file, SaleRequestPhoto::FOLDER, 'lead')]);
            $count++;
        }

        $saleRequest->addNote('foto', "{$count} foto unit ditambahkan.");
        Audit::log('crm.photos_added', "{$count} foto ditambahkan pada lead {$saleRequest->owner_name}", $saleRequest);

        $this->advance($saleRequest, 'foto unit', ['baru', 'follow up', 'survey']);

        return back()->with('success', "{$count} foto ditambahkan.");
    }

    public function destroyPhoto(SaleRequest $saleRequest, SaleRequestPhoto $photo)
    {
        abort_unless($photo->sale_request_id === $saleRequest->id, 404);

        ImageStore::delete($photo->image, SaleRequestPhoto::FOLDER);
        $photo->delete();
        $saleRequest->addNote('foto', 'Satu foto unit dihapus.');

        return back()->with('success', 'Foto dihapus.');
    }

    /** Buat draf unit dijual dari data lead (foto disalin); harga & foto final diatur di halaman edit unit. */
    public function publish(SaleRequest $saleRequest)
    {
        $unit = DB::transaction(function () use ($saleRequest) {
            $locked = SaleRequest::whereKey($saleRequest->id)->lockForUpdate()->first();

            if ($locked->sale_unit_id) {
                return null;
            }

            $unit = SaleUnit::create([
                'title' => 'Dijual '.$locked->apartment_name.($locked->unit_type ? ' '.$locked->unit_type : ''),
                'apartment_name' => $locked->apartment_name,
                'location' => $locked->location,
                'unit_type' => $locked->unit_type,
                'size' => $locked->size,
                'price' => $locked->agreed_price ?? $locked->last_offer ?? $locked->expected_price ?? 0,
                'description' => $locked->notes,
                'seller_name' => $locked->owner_name,
                'seller_phone' => $locked->owner_phone,
                'status' => 'nonaktif', // draf: aktifkan setelah foto & harga dicek
            ]);

            foreach ($locked->photos as $photo) {
                $from = SaleRequestPhoto::FOLDER.'/'.$photo->image;

                if (! Storage::disk('public')->exists($from)) {
                    continue;
                }

                $name = 'sale_'.now()->timestamp.'_'.Str::lower(Str::random(8)).'.'.pathinfo($photo->image, PATHINFO_EXTENSION);
                Storage::disk('public')->copy($from, 'sale_units/'.$name);
                $unit->images()->create(['image' => $name]);
                $unit->image ??= $name;
            }

            $unit->save();
            $locked->update(['sale_unit_id' => $unit->id]);
            $locked->addNote('publish', "Draf unit dijual dibuat (#{$unit->id}) dengan {$unit->images()->count()} foto. Lengkapi lalu aktifkan.", (float) $unit->price);

            return $unit;
        });

        if (! $unit) {
            return back()->with('error', 'Lead ini sudah dipublikasikan sebagai unit dijual.');
        }

        Audit::log('crm.published', "Lead {$saleRequest->owner_name} dipublikasikan sebagai unit dijual #{$unit->id} (draf)", $saleRequest);
        $this->advance($saleRequest, 'listing', ['baru', 'follow up', 'survey', 'foto unit']);

        return redirect()->route('admin.sale-units.edit', $unit)
            ->with('success', 'Draf unit dibuat dengan status nonaktif. Cek foto & harga, lalu aktifkan.');
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
            'commission_value' => ['required', 'numeric', 'min:0', $request->input('commission_type') === 'percent' ? 'max:100' : 'max:99999999999'],
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
            $saleRequest->addNote('closing', 'Closing '.Fmt::rupiah($data['deal_price']).'; komisi '.Fmt::rupiah($commission->commission_amount).'; hak penjual '.Fmt::rupiah($commission->seller_net_amount).". Invoice {$commission->number()}.", (float) $data['deal_price']);

            return $commission;
        });

        $this->changeStatus($saleRequest, 'closing');
        Audit::log('sale_commission.created', 'Closing lead '.$saleRequest->owner_name.': deal '.Fmt::rupiah($data['deal_price']).', komisi '.Fmt::rupiah($commission->commission_amount), $commission);

        return redirect()->route('admin.sale-requests.show', $saleRequest)
            ->with('success', 'Closing dicatat. Komisi Like Hotel: '.Fmt::rupiah($commission->commission_amount));
    }

    public function destroy(SaleRequest $saleRequest)
    {
        foreach ($saleRequest->photos as $photo) {
            ImageStore::delete($photo->image, SaleRequestPhoto::FOLDER);
        }

        Audit::log('crm.deleted', "Lead {$saleRequest->owner_name} ({$saleRequest->apartment_name}) dihapus", $saleRequest);
        $saleRequest->delete();

        return redirect()->route('admin.sale-requests.index')->with('success', 'Pengajuan berhasil dihapus.');
    }

    /** Majukan status hanya jika saat ini masih di salah satu tahap $onlyFrom. */
    private function advance(SaleRequest $saleRequest, string $to, array $onlyFrom): void
    {
        if (in_array($saleRequest->fresh()->status, $onlyFrom, true)) {
            $this->changeStatus($saleRequest->fresh(), $to);
        }
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
