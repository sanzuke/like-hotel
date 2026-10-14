<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\ApartmentImage;
use App\Models\Owner;
use App\Support\ImageUploader;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    private function rules(bool $creating): array
    {
        return [
            'owner_id' => ['nullable', 'exists:owners,id'],
            'name' => ['required', 'string', 'max:180'],
            'location' => ['required', 'string', 'max:180'],
            'type' => ['required', 'string', 'max:80'],
            'price_per_night' => ['required', 'numeric', 'min:1'],
            'facilities' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => $creating ? ['nullable'] : ['required', 'in:aktif,maintenance,nonaktif'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ImageUploader::rules(),
        ];
    }

    private function ownerName(?int $ownerId): string
    {
        return $ownerId ? (Owner::find($ownerId)?->name ?? 'Owner') : 'Like Hotel';
    }

    private function addImages(Apartment $apartment, Request $request): void
    {
        foreach (ImageUploader::store($request->file('images'), Apartment::FOLDER, 'apt') as $name) {
            $apartment->images()->create(['image' => $name]);

            if (! $apartment->image) {
                $apartment->update(['image' => $name]);
            }
        }
    }

    public function index()
    {
        return view('admin.apartments', [
            'owners' => Owner::orderBy('name')->get(),
            'apartments' => Apartment::latest('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(true));

        $apartment = Apartment::create([
            'owner_id' => $data['owner_id'] ?? null,
            'owner_name' => $this->ownerName($data['owner_id'] ?? null),
            'name' => $data['name'],
            'location' => $data['location'],
            'type' => $data['type'],
            'price_per_night' => $data['price_per_night'],
            'facilities' => $data['facilities'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => 'aktif',
        ]);

        $this->addImages($apartment, $request);

        return redirect()->route('admin.apartments')->with('status', 'Apartment berhasil ditambahkan.');
    }

    public function edit(Apartment $apartment)
    {
        return view('admin.apartment-edit', [
            'apt' => $apartment,
            'owners' => Owner::orderBy('name')->get(),
            'images' => $apartment->images()->reorder('id', 'desc')->get(),
        ]);
    }

    public function update(Request $request, Apartment $apartment)
    {
        $data = $request->validate($this->rules(false));

        $apartment->update([
            'owner_id' => $data['owner_id'] ?? null,
            'owner_name' => $this->ownerName($data['owner_id'] ?? null),
            'name' => $data['name'],
            'location' => $data['location'],
            'type' => $data['type'],
            'price_per_night' => $data['price_per_night'],
            'facilities' => $data['facilities'] ?? '',
            'description' => $data['description'] ?? '',
            'status' => $data['status'],
        ]);

        $this->addImages($apartment, $request);

        return redirect()->route('admin.apartments.edit', $apartment)->with('status', 'Apartment diperbarui.');
    }

    public function destroyImage(Apartment $apartment, ApartmentImage $image)
    {
        abort_unless($image->apartment_id === $apartment->id, 404);

        ImageUploader::delete(Apartment::FOLDER, $image->image);

        if ($apartment->image === $image->image) {
            $apartment->update(['image' => null]);
        }

        $image->delete();

        return back()->with('status', 'Foto dihapus.');
    }

    public function setCover(Apartment $apartment, ApartmentImage $image)
    {
        abort_unless($image->apartment_id === $apartment->id, 404);
        $apartment->update(['image' => $image->image]);

        return back()->with('status', 'Foto cover diperbarui.');
    }
}
