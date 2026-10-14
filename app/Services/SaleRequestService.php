<?php

namespace App\Services;

use App\Models\SaleCommission;
use App\Models\SaleRequest;
use App\Models\SaleRequestEvent;
use App\Models\SaleRequestPhoto;
use App\Models\SaleUnit;
use App\Support\ImageUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Alur CRM titip jual: baru → follow up → survey → foto unit → listing → negosiasi → closing → selesai. */
class SaleRequestService
{
    private const EARLY = ['baru', 'follow up', 'survey', 'foto unit', 'listing'];

    public function log(SaleRequest $r, string $type, ?string $body = null, ?float $amount = null): SaleRequestEvent
    {
        return $r->events()->create([
            'type' => $type, 'body' => $body, 'amount' => $amount, 'admin_id' => auth('admin')->id(),
        ]);
    }

    public function intake(SaleRequest $r): void
    {
        $this->log($r, 'masuk', 'Pengajuan titip jual masuk dari formulir publik.');
    }

    public function changeStatus(SaleRequest $r, string $to, ?string $note = null): void
    {
        $from = $r->status;

        if ($from !== $to) {
            $r->update(['status' => $to]);
        }

        if ($from !== $to || filled($note)) {
            $label = SaleRequest::STATUSES;
            $this->log($r, 'status', ($label[$from] ?? $from).' → '.($label[$to] ?? $to).(filled($note) ? "\n".$note : ''));
        }
    }

    private function advanceTo(SaleRequest $r, string $to, array $onlyFrom): void
    {
        if (in_array($r->status, $onlyFrom, true)) {
            $this->changeStatus($r, $to);
        }
    }

    public function addNote(SaleRequest $r, string $note): void
    {
        $this->log($r, 'catatan', $note);
    }

    public function scheduleSurvey(SaleRequest $r, Carbon $at, ?string $note = null): void
    {
        $r->update(['survey_at' => $at]);
        $this->log($r, 'survey', 'Survey dijadwalkan: '.$at->translatedFormat('d F Y, H:i').(filled($note) ? "\n".$note : ''));
        $this->advanceTo($r, 'survey', ['baru', 'follow up']);
    }

    public function addOffer(SaleRequest $r, float $amount, ?string $note = null): void
    {
        $r->update(['last_offer' => $amount]);
        $this->log($r, 'negosiasi', 'Penawaran pembeli: '.rupiah($amount).(filled($note) ? "\n".$note : ''), $amount);
        $this->advanceTo($r, 'negosiasi', self::EARLY);
    }

    /** @param  array<UploadedFile>  $files */
    public function addPhotos(SaleRequest $r, array $files): int
    {
        $names = ImageUploader::store($files, SaleRequestPhoto::FOLDER, 'lead');

        foreach ($names as $n) {
            $r->photos()->create(['image' => $n]);
        }

        if ($names) {
            $this->log($r, 'foto', count($names).' foto unit ditambahkan.');
            $this->advanceTo($r, 'foto unit', ['baru', 'follow up', 'survey']);
        }

        return count($names);
    }

    public function deletePhoto(SaleRequest $r, SaleRequestPhoto $photo): void
    {
        ImageUploader::delete(SaleRequestPhoto::FOLDER, $photo->image);
        $photo->delete();
        $this->log($r, 'foto', 'Satu foto unit dihapus.');
    }

    /** Terbitkan lead menjadi listing unit dijual (foto disalin ke folder sale_units). */
    public function publish(SaleRequest $r, float $price, ?string $title = null): SaleUnit
    {
        return DB::transaction(function () use ($r, $price, $title) {
            $r = SaleRequest::whereKey($r->id)->lockForUpdate()->firstOrFail();

            if ($r->sale_unit_id) {
                throw new BookingException('Pengajuan ini sudah dipublikasikan sebagai unit dijual.');
            }

            if ($r->isFinal()) {
                throw new BookingException('Pengajuan sudah selesai/batal, tidak bisa dipublikasikan.');
            }

            $unit = SaleUnit::create([
                'title' => $title ?: trim('Dijual '.($r->unit_type ?: 'Unit').' '.$r->apartment_name),
                'apartment_name' => $r->apartment_name,
                'location' => $r->location,
                'unit_type' => $r->unit_type,
                'size' => $r->size,
                'price' => $price,
                'description' => $r->notes,
                'seller_name' => $r->owner_name,
                'seller_phone' => $r->owner_phone,
                'status' => 'aktif',
            ]);

            foreach ($r->photos as $photo) {
                $ext = pathinfo($photo->image, PATHINFO_EXTENSION);
                $name = 'sale_'.time().'_'.strtolower(Str::random(8)).'.'.$ext;
                $from = SaleRequestPhoto::FOLDER.'/'.$photo->image;

                if (Storage::disk('public')->exists($from)) {
                    Storage::disk('public')->copy($from, SaleUnit::FOLDER.'/'.$name);
                    $unit->images()->create(['image' => $name]);
                    $unit->image ??= $name;
                }
            }

            $unit->save();

            $r->update(['sale_unit_id' => $unit->id]);
            $this->log($r, 'publish', 'Dipublikasikan sebagai unit dijual #'.$unit->id.' dengan harga '.rupiah($price).'.', $price);
            $this->advanceTo($r, 'listing', ['baru', 'follow up', 'survey', 'foto unit']);

            return $unit;
        });
    }

    /** Catat closing: membuat data komisi penjualan & menandai unit terjual. */
    public function close(SaleRequest $r, float $deal, string $type, float $value, float $otherCost, ?string $notes = null): SaleCommission
    {
        return DB::transaction(function () use ($r, $deal, $type, $value, $otherCost, $notes) {
            $r = SaleRequest::whereKey($r->id)->lockForUpdate()->firstOrFail();

            if ($r->sale_commission_id) {
                throw new BookingException('Closing untuk pengajuan ini sudah dicatat.');
            }

            if ($r->status === 'batal') {
                throw new BookingException('Pengajuan berstatus batal, tidak bisa di-closing.');
            }

            $c = SaleCommission::create([
                'sale_request_id' => $r->id,
                'sale_unit_id' => $r->sale_unit_id,
                'seller_name' => $r->owner_name,
                'apartment_name' => $r->apartment_name,
                'deal_price' => $deal,
                'commission_type' => $type,
                'commission_value' => $value,
                'other_cost' => $otherCost,
                'notes' => $notes,
                'status' => 'closing',
            ] + SaleCommission::compute($deal, $type, $value, $otherCost));

            $r->update(['sale_commission_id' => $c->id]);

            if ($r->sale_unit_id) {
                SaleUnit::whereKey($r->sale_unit_id)->update(['status' => 'terjual']);
            }

            $this->log($r, 'closing', 'Closing '.rupiah($deal).'; komisi '.rupiah($c->commission_amount).'; hak penjual '.rupiah($c->seller_net_amount).'. Invoice '.$c->number().'.', $deal);
            $this->changeStatus($r, 'closing');

            return $c;
        });
    }
}
