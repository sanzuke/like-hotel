<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Support\Geo;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    public function home()
    {
        return view('site.home', [
            'apartments' => Apartment::active()->ranked()->take(6)->get(),
        ]);
    }

    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => 'nullable|string|max:100',
            'category' => 'nullable|in:apartment,hotel',
            'type' => 'nullable|string|max:80',
            'checkin' => 'nullable|date_format:Y-m-d|after_or_equal:today',
            'checkout' => 'nullable|date_format:Y-m-d|required_with:checkin|after:checkin',
            'max_price' => 'nullable|integer|min:0',
            'sort' => 'nullable|in:terbaru,termurah,termahal,terdekat',
            'near' => 'nullable|string|in:'.implode(',', array_keys(config('likehotel.landmarks'))),
            'lat' => 'nullable|numeric|between:-90,90|required_with:lng',
            'lng' => 'nullable|numeric|between:-180,180|required_with:lat',
        ]);

        $apartments = Apartment::active()
            ->when($f['q'] ?? null, function ($query, $q) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
                $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('location', 'like', $like));
            })
            ->when($f['category'] ?? null, fn ($query, $c) => $query->where('category', $c))
            ->when($f['type'] ?? null, fn ($query, $t) => $query->where('type', $t))
            ->when($f['max_price'] ?? null, fn ($query, $p) => $query->where('price_per_night', '<=', $p))
            // Hanya unit yang kosong di seluruh rentang tanggal yang dipilih.
            ->when(! empty($f['checkin']) && ! empty($f['checkout']), fn ($query) => $query->freeBetween($f['checkin'], $f['checkout']));

        // Titik acuan jarak: lokasi pengguna (lat/lng) atau landmark pilihan.
        $origin = null;

        if (isset($f['lat'], $f['lng'])) {
            $origin = ['lat' => (float) $f['lat'], 'lng' => (float) $f['lng'], 'label' => 'lokasi Anda'];
        } elseif (! empty($f['near'])) {
            [$name, $lat, $lng] = config('likehotel.landmarks')[$f['near']];
            $origin = ['lat' => $lat, 'lng' => $lng, 'label' => $name];
        }

        $sort = $f['sort'] ?? ($origin ? 'terdekat' : 'terbaru');

        match ($sort) {
            'termurah' => $apartments->orderBy('price_per_night'),
            'termahal' => $apartments->orderByDesc('price_per_night'),
            default => $apartments->ranked(),
        };

        $list = $apartments->get();

        if ($origin) {
            $list->each(fn ($a) => $a->distance_km = ($a->lat !== null && $a->lng !== null)
                ? Geo::distanceKm($origin['lat'], $origin['lng'], $a->lat, $a->lng) : null);
        }

        if ($origin && $sort === 'terdekat') {
            // Unit beriklan aktif tetap di atas, lalu yang terdekat; unit tanpa koordinat paling akhir.
            $list = $list->sortBy([
                fn ($a, $b) => ($b->isSponsored() ? $b->ad_priority : 0) <=> ($a->isSponsored() ? $a->ad_priority : 0),
                fn ($a, $b) => ($a->distance_km ?? INF) <=> ($b->distance_km ?? INF),
            ])->values();
        }

        return view('site.apartments', [
            'origin' => $origin,
            'apartments' => $list,
            'filters' => $f,
            'types' => Apartment::active()->distinct()->orderBy('type')->pluck('type'),
            'categories' => Apartment::CATEGORIES,
        ]);
    }

    public function show(Request $request, Apartment $apartment)
    {
        abort_unless($apartment->status === 'aktif', 404);

        if ($request->route()->originalParameter('apartment') !== $apartment->getRouteKey()) {
            return redirect(route('apartments.show', $apartment).($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301);
        }

        $gallery = collect([$apartment->image])
            ->merge($apartment->images()->orderBy('id')->pluck('image'))
            ->filter()
            ->unique()
            ->values();

        return view('site.apartment-show', ['a' => $apartment, 'gallery' => $gallery]);
    }
}
