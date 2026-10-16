<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Booking;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    public function home()
    {
        return view('site.home', [
            'apartments' => Apartment::active()->latest('id')->take(6)->get(),
        ]);
    }

    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:80',
            'checkin' => 'nullable|date_format:Y-m-d|after_or_equal:today',
            'checkout' => 'nullable|date_format:Y-m-d|required_with:checkin|after:checkin',
            'max_price' => 'nullable|integer|min:0',
            'sort' => 'nullable|in:terbaru,termurah,termahal',
        ]);

        $apartments = Apartment::active()
            ->when($f['q'] ?? null, function ($query, $q) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
                $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('location', 'like', $like));
            })
            ->when($f['type'] ?? null, fn ($query, $t) => $query->where('type', $t))
            ->when($f['max_price'] ?? null, fn ($query, $p) => $query->where('price_per_night', '<=', $p))
            // Hanya unit yang kosong di seluruh rentang tanggal yang dipilih.
            ->when(! empty($f['checkin']) && ! empty($f['checkout']), function ($query) use ($f) {
                $query->whereNotIn('id', Booking::blocking()
                    ->overlapping($f['checkin'], $f['checkout'])
                    ->select('apartment_id'));
            });

        match ($f['sort'] ?? 'terbaru') {
            'termurah' => $apartments->orderBy('price_per_night'),
            'termahal' => $apartments->orderByDesc('price_per_night'),
            default => $apartments->latest('id'),
        };

        return view('site.apartments', [
            'apartments' => $apartments->get(),
            'filters' => $f,
            'types' => Apartment::active()->distinct()->orderBy('type')->pluck('type'),
        ]);
    }

    public function show(Apartment $apartment)
    {
        $gallery = collect([$apartment->image])
            ->merge($apartment->images()->orderBy('id')->pluck('image'))
            ->filter()
            ->unique()
            ->values();

        return view('site.apartment-show', ['a' => $apartment, 'gallery' => $gallery]);
    }
}
