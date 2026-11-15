<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\ApartmentBlock;
use App\Models\ApartmentImage;
use App\Support\Audit;
use App\Support\ImageStore;
use App\Support\TableFilter;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    public function index(Request $request)
    {
        $owner = auth('owner')->user();
        $types = $owner->apartments()->whereNotNull('type')->where('type', '!=', '')->distinct()->orderBy('type')->pluck('type')->all();

        $filters = TableFilter::active([
            'name' => TableFilter::text($request, 'name'),
            'location' => TableFilter::text($request, 'location'),
            'type' => TableFilter::enum($request, 'type', $types),
            'status' => TableFilter::enum($request, 'status', ['aktif', 'nonaktif', 'maintenance']),
        ]);

        $apartments = $owner->apartments()
            ->when($filters['name'] ?? null, fn ($q, $v) => $q->where('name', 'like', '%'.$v.'%'))
            ->when($filters['location'] ?? null, fn ($q, $v) => $q->where('location', 'like', '%'.$v.'%'))
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->latest('id')->paginate(10)->withQueryString();

        return view('owner.apartments', [
            'apartments' => $apartments,
            'filters' => $filters,
            'types' => $types,
        ]);
    }

    public function store(Request $request)
    {
        $owner = auth('owner')->user();

        $data = $request->validate([
            'name' => 'required|string|max:180',
            'location' => 'required|string|max:180',
            'lat' => 'nullable|numeric|between:-90,90|required_with:lng',
            'lng' => 'nullable|numeric|between:-180,180|required_with:lat',
            'category' => 'required|in:apartment,hotel',
            'type' => 'required|string|max:80',
            'price_per_night' => 'required|numeric|min:0',
            'price_per_month' => 'nullable|numeric|min:0',
            'price_per_year' => 'nullable|numeric|min:0',
            'facilities' => 'nullable|string',
            'description' => 'nullable|string',
            'images' => 'nullable|array|max:10',
            'images.*' => ImageStore::RULE,
        ]);
        unset($data['images']);

        // Unit baru dari owner menunggu verifikasi admin sebelum tampil di situs.
        $apartment = $owner->apartments()->create($data + ['owner_name' => $owner->name, 'status' => 'nonaktif']);

        foreach ($request->file('images', []) as $file) {
            $name = ImageStore::store($file, 'apartments', 'apt');
            $apartment->images()->create(['image' => $name]);

            if (! $apartment->image) {
                $apartment->update(['image' => $name]);
            }
        }

        return back()->with('success', 'Unit berhasil dikirim. Unit akan tampil setelah diaktifkan admin.');
    }

    public function edit(Apartment $apartment)
    {
        $this->authorizeUnit($apartment);

        return view('owner.apartment-edit', [
            'apt' => $apartment,
            'images' => $apartment->images()->latest('id')->get(),
            'blocks' => $apartment->blocks()->where('date_to', '>=', today())->orderBy('date_from')->get(),
            'bookings' => $apartment->bookings()->blocking()->where('checkout', '>=', today())->orderBy('checkin')->get(),
        ]);
    }

    public function update(Request $request, Apartment $apartment)
    {
        $this->authorizeUnit($apartment);

        $rules = [
            'name' => 'required|string|max:180',
            'location' => 'required|string|max:180',
            'lat' => 'nullable|numeric|between:-90,90|required_with:lng',
            'lng' => 'nullable|numeric|between:-180,180|required_with:lat',
            'category' => 'required|in:apartment,hotel',
            'type' => 'required|string|max:80',
            'price_per_night' => 'required|numeric|min:0',
            'price_per_month' => 'nullable|numeric|min:0',
            'price_per_year' => 'nullable|numeric|min:0',
            'facilities' => 'nullable|string',
            'description' => 'nullable|string',
            'images' => 'nullable|array|max:10',
            'images.*' => ImageStore::RULE,
        ];

        // Unit yang belum diverifikasi admin (nonaktif) tidak bisa diaktifkan sendiri oleh owner.
        if ($apartment->status !== 'nonaktif') {
            $rules['status'] = 'required|in:aktif,maintenance';
        }

        $data = $request->validate($rules);
        unset($data['images']);

        $apartment->update($data);
        $this->attachImages($request, $apartment);
        Audit::log('apartment.updated', "Unit \"{$apartment->name}\" diperbarui oleh owner", $apartment);

        return redirect()->route('owner.apartments.edit', $apartment)->with('success', 'Perubahan disimpan.');
    }

    public function storeBlock(Request $request, Apartment $apartment)
    {
        $this->authorizeUnit($apartment);

        $data = $request->validate([
            'date_from' => 'required|date_format:Y-m-d|after_or_equal:today',
            'date_to' => 'required|date_format:Y-m-d|after:date_from',
            'note' => 'nullable|string|max:150',
        ]);

        $clash = $apartment->bookings()->blocking()->overlapping($data['date_from'], $data['date_to'])->exists();

        if ($clash) {
            return back()->withInput()->withErrors(['date_from' => 'Rentang ini sudah memiliki booking aktif, tidak bisa ditutup.']);
        }

        $apartment->blocks()->create($data);

        return back()->with('success', 'Tanggal ditutup. Unit tidak bisa dibooking pada rentang tersebut.');
    }

    public function destroyBlock(Apartment $apartment, ApartmentBlock $block)
    {
        $this->authorizeUnit($apartment);
        abort_unless($block->apartment_id === $apartment->id, 404);
        $block->delete();

        return back()->with('success', 'Tanggal dibuka kembali.');
    }

    public function setCover(Apartment $apartment, ApartmentImage $image)
    {
        $this->authorizeUnit($apartment);
        abort_unless($image->apartment_id === $apartment->id, 404);
        $apartment->update(['image' => $image->image]);

        return back()->with('success', 'Cover diperbarui.');
    }

    public function destroyImage(Apartment $apartment, ApartmentImage $image)
    {
        $this->authorizeUnit($apartment);
        abort_unless($image->apartment_id === $apartment->id, 404);

        if ($apartment->image === $image->image) {
            $apartment->update(['image' => null]);
        }

        ImageStore::delete($image->image, 'apartments');
        $image->delete();

        return back()->with('success', 'Foto dihapus.');
    }

    private function authorizeUnit(Apartment $apartment): void
    {
        // Owner hanya boleh mengelola unit miliknya sendiri.
        abort_unless($apartment->owner_id === auth('owner')->id(), 404);
    }

    private function attachImages(Request $request, Apartment $apartment): void
    {
        foreach ($request->file('images', []) as $file) {
            $name = ImageStore::store($file, 'apartments', 'apt');
            $apartment->images()->create(['image' => $name]);

            if (! $apartment->image) {
                $apartment->update(['image' => $name]);
            }
        }
    }

    public function destroy(Apartment $apartment)
    {
        // Owner hanya boleh menghapus unit miliknya sendiri.
        abort_unless($apartment->owner_id === auth('owner')->id(), 404);

        // Riwayat booking, pembayaran, dan pencairan merujuk ke unit ini; jangan sampai ikut hilang.
        if ($apartment->bookings()->exists()) {
            return back()->with('error', "Unit \"{$apartment->name}\" tidak bisa dihapus karena sudah memiliki riwayat booking. Hubungi admin untuk menonaktifkannya.");
        }

        $files = $apartment->images->pluck('image')->push($apartment->image)->filter()->unique();
        Audit::log('apartment.deleted', "Apartment \"{$apartment->name}\" dihapus oleh owner", $apartment);
        $apartment->delete();

        foreach ($files as $file) {
            ImageStore::delete($file, 'apartments');
        }

        return redirect()->route('owner.apartments.index')->with('success', 'Unit dihapus.');
    }
}
