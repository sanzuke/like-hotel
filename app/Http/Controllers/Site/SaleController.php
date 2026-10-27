<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\SaleRequest;
use App\Models\SaleUnit;
use App\Support\Audit;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    private const PRICE_RANGES = [
        'lt300' => ['< Rp 300 Juta', null, 300_000_000],
        '300-400' => ['Rp 300 - 400 Juta', 300_000_000, 400_000_000],
        'gt400' => ['> Rp 400 Juta', 400_000_000, null],
    ];

    public function index(Request $request)
    {
        $active = SaleUnit::active();
        $types = (clone $active)->whereNotNull('unit_type')->where('unit_type', '!=', '')->distinct()->orderBy('unit_type')->pluck('unit_type');
        $furnishings = (clone $active)->whereNotNull('furnishing')->where('furnishing', '!=', '')->distinct()->orderBy('furnishing')->pluck('furnishing');

        $f = [
            'q' => trim((string) $request->query('q', '')),
            'type' => (string) $request->query('type', ''),
            'furnishing' => (string) $request->query('furnishing', ''),
            'price' => array_key_exists((string) $request->query('price'), self::PRICE_RANGES) ? (string) $request->query('price') : '',
            'sort' => in_array($request->query('sort'), ['termurah', 'termahal'], true) ? $request->query('sort') : 'terbaru',
        ];
        [, $min, $max] = self::PRICE_RANGES[$f['price']] ?? [null, null, null];

        $rows = SaleUnit::active()
            ->when($f['q'] !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$f['q'].'%')
                ->orWhere('apartment_name', 'like', '%'.$f['q'].'%')
                ->orWhere('location', 'like', '%'.$f['q'].'%')))
            ->when($f['type'] !== '', fn ($q) => $q->where('unit_type', $f['type']))
            ->when($f['furnishing'] !== '', fn ($q) => $q->where('furnishing', $f['furnishing']))
            ->when($min, fn ($q, $v) => $q->where('price', '>=', $v))
            ->when($max, fn ($q, $v) => $q->where('price', '<', $v))
            ->when($f['sort'] === 'termurah', fn ($q) => $q->orderBy('price'), fn ($q) => $f['sort'] === 'termahal' ? $q->orderByDesc('price') : $q->latest('id'))
            ->get();

        return view('site.sale-units', [
            'rows' => $rows, 'filters' => $f, 'types' => $types, 'furnishings' => $furnishings,
            'priceRanges' => self::PRICE_RANGES,
            'hasFilter' => collect($f)->except('sort')->filter()->isNotEmpty() || $f['sort'] !== 'terbaru',
        ]);
    }

    public function show(SaleUnit $saleUnit)
    {
        abort_unless($saleUnit->status === 'aktif', 404, 'Unit dijual tidak ditemukan atau sudah tidak aktif.');

        $gallery = collect([$saleUnit->image])
            ->merge($saleUnit->images()->orderBy('id')->pluck('image'))
            ->filter()->unique()->values();

        return view('site.sale-show', ['r' => $saleUnit, 'gallery' => $gallery]);
    }

    public function requestForm()
    {
        return view('site.sale-request');
    }

    public function requestStore(Request $request)
    {
        $data = $request->validate([
            'owner_name' => 'required|string|max:150',
            'owner_phone' => ['required', 'string', 'max:50', 'regex:/^[0-9+\s\-]{8,}$/'],
            'apartment_name' => 'required|string|max:150',
            'location' => 'required|string|max:150',
            'unit_type' => 'nullable|string|max:100',
            'size' => 'nullable|string|max:50',
            'expected_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ], ['owner_phone.regex' => 'Nomor WhatsApp tidak valid.']);

        $lead = SaleRequest::create($data + ['status' => 'baru']);
        $lead->hasMany(\App\Models\SaleRequestNote::class)->create(['type' => 'catatan', 'body' => 'Pengajuan masuk dari formulir situs.', 'author' => 'Sistem']);
        Audit::log('crm.lead_created', "Lead titip jual baru: {$lead->owner_name} ({$lead->apartment_name})", $lead, [], ['type' => 'guest', 'name' => $lead->owner_name]);

        return redirect()->route('sale-request.create')
            ->with('success', 'Pengajuan titip jual berhasil dikirim. Tim Like Hotel akan menghubungi Anda.');
    }
}
