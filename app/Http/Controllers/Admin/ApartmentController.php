<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\ApartmentImage;
use App\Models\Owner;
use App\Support\ImageStore;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    private const DIR = 'apartments';

    public function index()
    {
        return view('admin.apartments.index', [
            'apartments' => Apartment::latest('id')->get(),
            'owners' => Owner::where('status', 'aktif')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['owner_name'] = $this->ownerName($data['owner_id'] ?? null);
        $data['status'] = 'aktif';

        $apartment = Apartment::create($data);
        $this->attachImages($request, $apartment);

        return redirect()->route('admin.apartments.index')->with('success', 'Apartment berhasil ditambahkan.');
    }

    public function edit(Apartment $apartment)
    {
        return view('admin.apartments.edit', [
            'apt' => $apartment,
            'owners' => Owner::orderBy('name')->get(),
            'images' => $apartment->images()->latest('id')->get(),
        ]);
    }

    public function update(Request $request, Apartment $apartment)
    {
        $data = $this->validated($request);
        $data['owner_name'] = $this->ownerName($data['owner_id'] ?? null);
        $data['status'] = $request->validate(['status' => 'required|in:aktif,nonaktif,maintenance'])['status'];

        $apartment->update($data);
        $this->attachImages($request, $apartment);

        return redirect()->route('admin.apartments.edit', $apartment)->with('success', 'Perubahan disimpan.');
    }

    public function setCover(Apartment $apartment, ApartmentImage $image)
    {
        abort_unless($image->apartment_id === $apartment->id, 404);
        $apartment->update(['image' => $image->image]);

        return back()->with('success', 'Cover diperbarui.');
    }

    public function destroyImage(Apartment $apartment, ApartmentImage $image)
    {
        abort_unless($image->apartment_id === $apartment->id, 404);

        if ($apartment->image === $image->image) {
            $apartment->update(['image' => null]);
        }

        ImageStore::delete($image->image, self::DIR);
        $image->delete();

        return back()->with('success', 'Foto dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'owner_id' => 'nullable|exists:owners,id',
            'name' => 'required|string|max:180',
            'location' => 'required|string|max:180',
            'type' => 'required|string|max:80',
            'price_per_night' => 'required|numeric|min:0',
            'facilities' => 'nullable|string',
            'description' => 'nullable|string',
            'images' => 'nullable|array|max:10',
            'images.*' => ImageStore::RULE,
        ]);

        unset($data['images']);

        return $data + ['owner_id' => null];
    }

    private function ownerName(?int $ownerId): string
    {
        return $ownerId ? (Owner::find($ownerId)?->name ?? 'Owner') : 'Like Hotel';
    }

    private function attachImages(Request $request, Apartment $apartment): void
    {
        foreach ($request->file('images', []) as $file) {
            $name = ImageStore::store($file, self::DIR, 'apt');
            $apartment->images()->create(['image' => $name]);

            if (! $apartment->image) {
                $apartment->update(['image' => $name]);
            }
        }
    }
}
